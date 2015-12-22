<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ElasticSearch;

use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\QueryBuilder\Query;
use Conjecto\Nemrod\ResourceManager\FiliationBuilder;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use EasyRdf\RdfNamespace;
use Elastica\Type;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Populator
 * @package Conjecto\Nemrod\ElasticSearch
 */
class Populator
{
    /** @var  Manager */
    protected $resourceManager;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var  IndexRegistry */
    protected $indexRegistry;

    /** @var  Resetter */
    protected $resetter;

    /** @var  TypeMapperRegistry */
    protected $typeMapperRegistry;

    /** @var JsonLdSerializer */
    protected $jsonLdSerializer;

    /** @var SerializerHelper */
    protected $serializerHelper;

    /** @var FiliationBuilder */
    protected $filiationBuilder;

    /**
     * @param Manager $resourceManager
     * @param ConfigManager $configManager
     * @param IndexRegistry $indexManager
     * @param Resetter $resetter
     * @param TypeMapperRegistry $typeMapperRegistry
     * @param SerializerHelper $serializerHelper
     * @param JsonLdSerializer $jsonLdSerializer
     * @param FiliationBuilder $filiationBuilder
     */
    public function __construct(Manager $resourceManager, ConfigManager $configManager, IndexRegistry $indexManager,  Resetter $resetter,
                                TypeMapperRegistry $typeMapperRegistry, SerializerHelper $serializerHelper, JsonLdSerializer $jsonLdSerializer,
                                FiliationBuilder $filiationBuilder)
    {
        $this->resourceManager = $resourceManager;
        $this->indexRegistry = $indexManager;
        $this->configManager = $configManager;
        $this->resetter = $resetter;
        $this->typeMapperRegistry = $typeMapperRegistry;
        $this->serializerHelper = $serializerHelper;
        $this->jsonLdSerializer = $jsonLdSerializer;
        $this->filiationBuilder = $filiationBuilder;
    }

    /**
     * @param Type $type
     * @param bool $reset
     * @param array $options
     * @param ConsoleOutput $output
     * @param bool $showProgress
     */
    public function populate($index, $type = null, $reset = true, $options = array(), $output, $showProgress = true)
    {
        if($reset && !$type) {
            $this->resetter->reset($index, null, $output);
            $reset = false;
        }

        // check index existence
        $indexObj = $this->indexRegistry->getIndex($index);
        if(!$indexObj->exists()) {
            $this->resetter->resetIndex($index);
        }

        // get types to populate
        $types = $this->getTypesToPopulate($index, $type);
        $trans = new ResourceToDocumentTransformer($this->serializerHelper, $this->configManager, $this->typeMapperRegistry, $this->jsonLdSerializer);

        $options['limit'] = $options['slice'];
        $options['orderBy'] = 'uri';

        foreach($types as $type) {
            $this->populateType($index, $type, $options, $trans, $output, $reset, $showProgress);
        }
    }

    /**
     * @param $index
     * @param $key
     * @param $typ
     * @param $type
     * @param $options
     * @param $trans
     * @param $output
     * @param $reset
     * @param $showProgress
     */
    protected function populateType($index, $type, $options, $trans, $output, $reset, $showProgress)
    {
        if($reset) {
            $this->resetter->reset($index, $type, $output);
        }
        $output->writeln("Populating " . $type);

        $this->jsonLdSerializer->getJsonLdFrameLoader()->setEsIndex($index);
        $class = $this->configManager->getIndexConfiguration($index)->getType($type)->getType();
        $typeEs = $this->indexRegistry->getIndex($index)->getType($class);
        $size = $this->getSize($class);

        // no object in triplestore
        if (!current($size)) {
            return;
        }

        $size = current($size)['count'];
        $progress = $this->displayInitialAvancement($size, $options, $showProgress, $output);
        $done = 0;
        while ($done < $size) {
            $resources = $this->getResources($class, $options, $done);
            $uris = array();
            $docs = array();

            // build uris
            foreach ($resources as $resource) {
                $types = $resource->all('rdf:type');
                $mostAccurateType = $this->getMostAccurateType($types, $resource, $output, $class);
                // index only the current resource if the most accurate type is the key populating
                if ($class === $mostAccurateType) {
                    $uris[] = $resource->getUri();
                }
            }

            // transform uris
            if(count($uris)) {
                $docs = $trans->transform($uris, $index, $mostAccurateType);
            }

            // send documents to elasticsearch
            if (count($docs)) {
                $typeEs->addDocuments($docs);
            }

            $diff = count($uris) - count($docs);
            if($diff > 0) {
                $output->writeln(sprintf("%s : %d/%d skipped resources", $type, $diff, count($uris)));
            }

            $done = $this->displayAvancement($options, $done, $size, $showProgress, $output, $progress);

            //flushing manager for mem usage
            $this->resourceManager->flush();
        }
        $progress->finish();
    }

    /**
     * @param $types
     * @param $resource
     * @param $output
     * @param $class
     * @return null
     */
    protected function getMostAccurateType($types, $resource, $output, $class)
    {
        $mostAccurateType = null;
        $mostAccurateTypes = $this->filiationBuilder->getMostAccurateType($types, $this->serializerHelper->getAllTypes());
        // not specified in project ontology description
        if (count($mostAccurateTypes) == 1) {
            $mostAccurateType = $mostAccurateTypes[0];
        }
        else if ($mostAccurateTypes === null) {
            $output->writeln("No accurate type found for " . $resource->getUri() . ". The type $class will be used.");
            $mostAccurateType = $class;
        }
        else {
            $output->writeln("The most accurate type for " . $resource->getUri() . " has not be found. The resource will not be indexed.");
        }

        return $mostAccurateType;
    }

    /**
     * @param $class
     * @param $options
     * @param $done
     * @return mixed
     */
    protected function getResources($class, $options, $done)
    {
        $options['offset'] = $done;
        $select = $this->resourceManager->getQueryBuilder()->select('?uri')->where('?uri a ' . $class);
        $select->orderBy('?uri');
        $select->setOffset($done);
        $select->setMaxResults($options['slice']);
        $select = $select->setMaxResults(isset($options['slice']) ? $options['slice'] : null)->getQuery();
        $selectStr = $select->getCompleteSparqlQuery();
        $qb = $this->resourceManager->getRepository($class)->getQueryBuilder();
        $qb->reset()
            ->construct("?uri a $class")
            ->addConstruct('?uri rdf:type ?type')
            ->where('?uri a ' . $class)
            ->andWhere('?uri rdf:type ?type')
            ->andWhere('{' . $selectStr . '}');

        return $qb->getQuery()->execute(Query::HYDRATE_COLLECTION, array('rdf:type' => $class));
    }

    /**
     * @param $index
     * @param $type
     * @return array
     * @throws \Exception
     */
    protected function getTypesToPopulate($index, $type = null)
    {
        $typesConfig = $this->configManager->getIndexConfiguration($index)->getTypes();
        $types = array_keys($typesConfig);
        if($type) {
            if(!in_array($type, $types)) {
                throw new \Exception("The type $type is not defined");
            }
            return array($type);
        }
        return $types;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getSize($key)
    {
        $qb = $this->resourceManager->getRepository($key) ->getQueryBuilder();
        $qb->reset()
            ->select('(COUNT(DISTINCT ?instance) AS ?count)')
            ->where('?instance a ' . $key);

        return $qb->getQuery()->execute();
    }

    /**
     * @param $size
     * @param $options
     * @param $showProgress
     * @param $output
     * @return null|ProgressBar
     */
    protected function displayInitialAvancement($size, $options, $showProgress, $output)
    {
        $progress = null;
        $output->writeln($size . " entries");
        if ($showProgress) {
            $progress = new ProgressBar($output, ceil($size / $options['slice']));
            $progress->start();
            $progress->setFormat('debug');
        }

        return $progress;
    }

    /**
     * @param $options
     * @param $done
     * @param $size
     * @param $showProgress
     * @param $output
     * @param $progress
     * @return mixed
     */
    protected function displayAvancement($options, $done, $size, $showProgress, $output, $progress)
    {
        //advance
        $done += $options['slice'];
        if ($done > $size) {
            $done = $size;
        }

        //showing where we're at.
        if ($showProgress) {
            if ($output->isDecorated()) {
                $progress->advance();
            } else {
                $output->writeln("did " . $done . " over (" . $size . ") memory: " . Helper::formatMemory(memory_get_usage(true)));
            }
        }

        return $done;
    }
}
