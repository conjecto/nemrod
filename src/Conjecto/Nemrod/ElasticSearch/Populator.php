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
        $types = $this->getTypesToPopulate($index, $type);
        $trans = new ResourceToDocumentTransformer($this->serializerHelper, $this->configManager, $this->typeMapperRegistry, $this->jsonLdSerializer);

        $options['limit'] = $options['slice'];
        $options['orderBy'] = 'uri';

        /** @var Type $typ */
        foreach ($types as $key => $typ) {
            $this->populateType($index, $key, $typ, $type, $options, $trans, $output, $reset, $showProgress);
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
    protected function populateType($index, $key, Type $typ, $type, $options, $trans, $output, $reset, $showProgress)
    {
        if ($reset & $type) {
            $this->resetter->reset($index, $type, $key, $output);
        }
        $output->writeln("Populating " . $key);

        $this->jsonLdSerializer->getJsonLdFrameLoader()->setEsIndex($index);
        $class = $this->configManager->getIndexConfiguration($index)->getType($key)->getType();
        $size = $this->getSize($class);

        // no object in triplestore
        if (!current($size)) {
            return;
        }

        $size = current($size)->count->getValue();
        $progress = $this->displayInitialAvancement($size, $options, $showProgress, $output);
        $done = 0;
        while ($done < $size) {
            $resources = $this->getResources($class, $options, $done);
            $docs = array();
            /* @var Resource $add */
            foreach ($resources as $resource) {
                $types = $resource->all('rdf:type');
                $mostAccurateType = $this->getMostAccurateType($types, $resource, $output, $class);
                // index only the current resource if the most accurate type is the key populating
                if ($class === $mostAccurateType) {
                    $doc = $trans->transform($resource->getUri(), $index,  $mostAccurateType);
                    if ($doc) {
                        $docs[] = $doc;
                    }
                }
            }

            // send documents to elasticsearch
            if (count($docs)) {
                $typ->addDocuments($docs);
            } else {
                $output->writeln("");
                $output->writeln("nothing to index");
            }

            $done = $this->displayAvancement($options, $done, $size, $showProgress, $output, $progress);

            //flushing manager for mem usage
            $this->resourceManager->flush();
        }
        $progress->finish();
    }

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
        $indexObj = $this->indexRegistry->getIndex($index);
        if (!$indexObj) {
            throw new \Exception("The index $index is not defined");
        }

        // creating index if not exists
        if (!$indexObj->exists()) {
            $this->resetter->resetIndex($index);
        }

        $typesConfig = $this->configManager->getIndexConfiguration($index)->getTypes();
        if ($type) {
            if(!isset($typesConfig[$type])) {
                throw new \Exception("The type $type is not defined in this index.");
            }
            $typeConfig = $typesConfig[$type];
            $types = array($type => $indexObj->getType($typeConfig->getType()));
        } else {
            $this->resetter->reset($index);
            $types = array_combine(array_keys($typesConfig), array_map(function(TypeConfig $typeConfig) use ($indexObj) {
                return $indexObj->getType($typeConfig->getType());
            }, $typesConfig));
        }

        return $types;
    }

    protected function getSize($key)
    {
        $qb = $this->resourceManager->getRepository($key) ->getQueryBuilder();
        $qb->reset()
            ->select('(COUNT(DISTINCT ?instance) AS ?count)')
            ->where('?instance a ' . $key);

        return $qb->getQuery()->execute();
    }

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
