<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/02/2015
 * Time: 17:04
 */

namespace Devyn\Bridge\Elastica;

use Devyn\Component\ESIndexing\ResourceToDocumentTransformer;
use Devyn\Component\RAL\Manager\Manager;
use Devyn\Component\RAL\Registry\TypeMapperRegistry;
use Elastica\Document;
use Elastica\Type;

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

    /**
     * @param $resourceManager
     * @param $indexManager
     * @param $typeRegistry
     * @param $resetter
     */
    public function __construct($resourceManager, $indexManager, $typeRegistry, $resetter, $typeMapperRegistry, $esCache)
    {
        $this->resourceManager = $resourceManager;
        $this->indexRegistry = $indexManager;
        $this->typeRegistry = $typeRegistry;
        $this->resetter = $resetter;
        $this->typeMapperRegistry = $typeMapperRegistry;
        $this->esCache = $esCache;
    }

    /**
     * Populates elastica index for a specific type
     * @param $type
     */
    public function populate($type = null)
    {
        $types = $this->typeRegistry->getTypes();

        /** @var Type $typ */
        foreach($types as $key => $typ) {
            echo $key;
            $result = $this->resourceManager->getRepository($key)->getQueryBuilder()->getQuery()->execute();

            $trans = new ResourceToDocumentTransformer($this->esCache, $this->typeRegistry, $this->typeMapperRegistry);

            /** @var Resource $add */
            foreach($result as $res ) {
                echo $res->getUri();
                $this->typeRegistry->getType($key)->addDocument($trans->transform($res->getUri(), $key));

            }
        }
    }
}