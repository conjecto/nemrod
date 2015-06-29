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

use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
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

    /** @var  @var JsonLdSerializer */
    protected $jsonLdSerializer;

    /**
     * @param $resourceManager
     * @param $indexManager
     * @param $typeRegistry
     * @param $resetter
     */
    public function __construct($resourceManager, $indexManager, $typeRegistry, $resetter, $typeMapperRegistry, $serializerHelper, $jsonLdSerializer)
    {
        $this->resourceManager = $resourceManager;
        $this->indexRegistry = $indexManager;
        $this->typeRegistry = $typeRegistry;
        $this->resetter = $resetter;
        $this->typeMapperRegistry = $typeMapperRegistry;
        $this->serializerHelper = $serializerHelper;
        $this->jsonLdSerializer = $jsonLdSerializer;
    }

    /**
     * @param null $type
     * @param bool $reset
     * @param array $options
     * @param ConsoleOutput $output
     * @param bool $showProgress
     */
    public function populate($type = null, $reset = true, $options = array(), $output, $showProgress = true)
    {
        if ($type) {
            $types = array($type => $this->typeRegistry->getType($type));
        } else {
            $types = $this->typeRegistry->getTypes();
        }

        $trans = new ResourceToDocumentTransformer($this->serializerHelper, $this->typeRegistry, $this->typeMapperRegistry, $this->jsonLdSerializer);
        $options['limit'] = $options['slice'];
        $options['orderBy'] = 'uri';
        /** @var Type $typ */
        foreach ($types as $key => $typ) {
            $output->writeln("populating " . $key);

            if ($reset) {
                $this->resetter->reset($key);
            }

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
                $progress  = new ProgressBar($output, ceil($size/$options['slice']));
                $progress->start();
                $progress->setFormat('debug');
            }
            $done = 0;
            while ($done < $size) {
                $options['offset'] = $done;
                //$result = $this->resourceManager->getRepository($key)->findBy(array(), $options);
                $result = $this->resourceManager->getRepository($key)
                    ->getQueryBuilder()
                    ->reset()
                    ->select('?uri')->where('?uri a ' . $key)
                    ->orderBy('?uri')
                    ->setOffset($done)
                    ->setMaxResults($options['slice'])
                    ->getQuery()
                    ->execute();

                $docs = array();
                /* @var Resource $add */
                foreach ($result as $res) {
                    //echo $res->uri->getUri();
                    $doc = $trans->transform($res->uri->getUri(), $key);

                    if ($doc) {
                        $docs[] = $doc;
                    }
                }
                $this->typeRegistry->getType($key)->addDocuments($docs);
                //advance
                $done += $options['slice'];

                //showing where we're at.
                if ($showProgress) {
                    if ($output->isDecorated() ) {
                        $progress->advance();
                    } else {
                        $output->writeln("did ".$done." over (".$size.") memory: ". Helper::formatMemory(memory_get_usage(true)) );
                    }
                }
                //flushing manager for mem usage
                $this->resourceManager->flush();
            }
            $progress->finish();
        }
    }
}
