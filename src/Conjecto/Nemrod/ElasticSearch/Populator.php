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
        if ($type) {
            $typeObj = $this->typeRegistry->getType($type);
            $types = array($type => $typeObj);

            if (!$typeObj) {
                throw new \Exception("The index $type is not defined");
            }

            //creating index if not exists
            if (!$typeObj->getIndex()->exists()){
                $this->resetter->resetIndex($typeObj->getIndex()->getName());
            }
        } else {
            $this->resetter->reset();
            $types = $this->typeRegistry->getTypes();
        }

        $trans = new ResourceToDocumentTransformer($this->serializerHelper, $this->typeRegistry, $this->typeMapperRegistry, $this->jsonLdSerializer);

        $options['limit'] = $options['slice'];
        $options['orderBy'] = 'uri';
        /** @var Type $typ */
        foreach ($types as $key => $typ) {
            $output->writeln("populating " . $key);

            if ($reset & $type) {
                $this->resetter->reset(null, $key, $output);
            }

            $this->jsonLdSerializer->getJsonLdFrameLoader()->setEsIndex($typ->getIndex()->getName());
            $size = $this->resourceManager->getRepository($key)
                ->getQueryBuilder()->reset()->select('(COUNT(DISTINCT ?instance) AS ?count)')->where('?instance a ' . $key)->getQuery()
                ->execute();

            // no object in triplestore
            if (!current($size)) {
                continue;
            }

            $size = current($size)->count->getValue();
            $output->writeln($size . " entries");
            if ($showProgress) {
                $progress = new ProgressBar($output, ceil($size / $options['slice']));
                $progress->start();
                $progress->setFormat('debug');
            }
            $done = 0;
            while ($done < $size) {
                $options['offset'] = $done;
                $select = $this->resourceManager->getQueryBuilder()->select('?uri')->where('?uri a ' . $key);
                $select->orderBy('?uri');
                $select->setOffset($done);
                $select->setMaxResults($options['slice']);
                $select = $select->setMaxResults(isset($options['slice']) ? $options['slice'] : null)->getQuery();
                $selectStr = $select->getCompleteSparqlQuery();
                $result = $this->resourceManager->getRepository($key)
                    ->getQueryBuilder()
                    ->reset()
                    ->construct("?uri a $key")
                    ->addConstruct('?uri rdf:type ?type')
                    ->where('?uri a ' . $key)
                    ->andWhere('?uri rdf:type ?type')
                    ->andWhere('{' . $selectStr . '}')
                    ->getQuery()
                    ->execute(Query::HYDRATE_COLLECTION, array('rdf:type' => $key));

                $docs = array();
                /* @var Resource $add */
                foreach ($result as $res) {
                    $types = $res->all('rdf:type');
                    $mostAccurateType = $key;
                    $mostAccurateTypes = $this->filiationBuilder->getMostAccurateType($types);
                    // not specified in project ontology description
                    if ($mostAccurateTypes === null) {
                        // keep the current $key
                    } else if (count($mostAccurateTypes) == 1) {
                        $mostAccurateType = $mostAccurateTypes[0];
                    } else {
                        $output->writeln("The most accurate type for " . $res->getUri() . " has not be found. The type $key is used");
                        var_dump($mostAccurateTypes);
                        die;
                    }
                    $doc = $trans->transform($res->getUri(), $mostAccurateType);
                    if ($doc) {
                        $docs[$mostAccurateType][] = $doc;
                    }
                }

                foreach ($docs as $type => $docs) {
                    if ($type === $key) {
                        if (count($docs)) {
                            $this->typeRegistry->getType($type)->addDocuments($docs);
                        } else {
                            $output->writeln("");
                            $output->writeln("nothing to index");
                        }
                    }
                }

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
                //flushing manager for mem usage
                $this->resourceManager->flush();
            }
            $progress->finish();
        }
    }
}
