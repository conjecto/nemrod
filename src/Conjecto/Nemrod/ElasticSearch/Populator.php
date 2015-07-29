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

    /** @var  IndexRegistry */
    protected $indexRegistry;

    /** @var  TypeRegistry */
    protected $typeRegistry;

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
     * @param $resourceManager
     * @param $indexManager
     * @param $typeRegistry
     * @param $resetter
     */
    public function __construct(Manager $resourceManager, IndexRegistry $indexManager, TypeRegistry $typeRegistry, Resetter $resetter,
                                TypeMapperRegistry $typeMapperRegistry, SerializerHelper $serializerHelper, JsonLdSerializer $jsonLdSerializer,
                                FiliationBuilder $filiationBuilder)
    {
        $this->resourceManager = $resourceManager;
        $this->indexRegistry = $indexManager;
        $this->typeRegistry = $typeRegistry;
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
    public function populate($type = null, $reset = true, $options = array(), $output, $showProgress = true)
    {
        $types = $this->getTypesToPopulate($type);
        $trans = new ResourceToDocumentTransformer($this->serializerHelper, $this->typeRegistry, $this->typeMapperRegistry, $this->jsonLdSerializer);

        $options['limit'] = $options['slice'];
        $options['orderBy'] = 'uri';

        /** @var Type $typ */
        foreach ($types as $key => $typ) {
            $this->populateType($key, $typ, $type, $options, $trans, $output, $reset, $showProgress);
        }
    }

    protected function populateType($key, $typ, $type, $options, $trans, $output, $reset, $showProgress)
    {
        $output->writeln("populating " . $key);
        if ($reset & $type) {
            $this->resetter->reset(null, $key, $output);
        }
        $this->jsonLdSerializer->getJsonLdFrameLoader()->setEsIndex($typ->getIndex()->getName());
        $size = $this->getSize($key);

        // no object in triplestore
        if (!current($size)) {
            continue;
        }

        $size = current($size)->count->getValue();
        $progress = $this->displayInitialAvancement($size, $options, $showProgress, $output);
        $done = 0;
        while ($done < $size) {
            $resources = $this->getResources($key, $options, $done);
            $docs = array();
            /* @var Resource $add */
            foreach ($resources as $resource) {
                $types = $resource->all('rdf:type');
                $mostAccurateType = $this->getMostAccurateType($types, $resource, $output, $key);

                // index only the current resource if the most accurate type is the key populating
                if ($key === $mostAccurateType) {
                    $doc = $trans->transform($resource->getUri(), $mostAccurateType);
                    if ($doc) {
                        $docs[] = $doc;
                    }
                }
            }

            // send documents to elasticsearch
            if (count($docs)) {
                $this->typeRegistry->getType($key)->addDocuments($docs);
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

    protected function getMostAccurateType($types, $resource, $output, $key)
    {
        $mostAccurateType = null;
        $mostAccurateTypes = $this->filiationBuilder->getMostAccurateType($types, $this->serializerHelper->getAllTypes());
        // not specified in project ontology description
        if (count($mostAccurateTypes) == 1) {
            $mostAccurateType = $mostAccurateTypes[0];
        }
        else if ($mostAccurateTypes === null) {
            $output->writeln("No accurate type found for " . $resource->getUri() . ". The type $key will be used.");
            $mostAccurateType = $key;
        }
        else {
            $output->writeln("The most accurate type for " . $resource->getUri() . " has not be found. The resource will not be indexed.");
        }

        return $mostAccurateType;
    }

    protected function getResources($key, $options, $done)
    {
        $options['offset'] = $done;
        $select = $this->resourceManager->getQueryBuilder()->select('?uri')->where('?uri a ' . $key);
        $select->orderBy('?uri');
        $select->setOffset($done);
        $select->setMaxResults($options['slice']);
        $select = $select->setMaxResults(isset($options['slice']) ? $options['slice'] : null)->getQuery();
        $selectStr = $select->getCompleteSparqlQuery();
        $qb = $this->resourceManager->getRepository($key)->getQueryBuilder();
        $qb->reset()
            ->construct("?uri a $key")
            ->addConstruct('?uri rdf:type ?type')
            ->where('?uri a ' . $key)
            ->andWhere('?uri rdf:type ?type')
            ->andWhere('{' . $selectStr . '}');

        return $qb->getQuery()->execute(Query::HYDRATE_COLLECTION, array('rdf:type' => $key));
    }

    protected function getTypesToPopulate($type)
    {
        if ($type) {
            $typeObj = $this->typeRegistry->getType($type);
            $types = array($type => $typeObj);

            if (!$typeObj) {
                throw new \Exception("The index $type is not defined");
            }

            //creating index if not exists
            if (!$typeObj->getIndex()->exists()) {
                $this->resetter->resetIndex($typeObj->getIndex()->getName());
            }
        } else {
            $this->resetter->reset();
            $types = $this->typeRegistry->getTypes();
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
