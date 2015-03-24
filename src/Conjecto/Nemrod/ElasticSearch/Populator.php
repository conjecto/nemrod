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
     * Populates elastica index for a specific type.
     *
     * @param $type
     */
    public function populate($type = null, $reset = true)
    {
        if ($type) {
            $types = array($type => $this->typeRegistry->getType($type));
        } else {
            $types = $this->typeRegistry->getTypes();
        }

        /** @var Type $typ */
        foreach ($types as $key => $typ) {
            if ($reset) {
                $this->resetter->reset($key);
            }
            echo $key;
            $result = $this->resourceManager->getRepository($key)->getQueryBuilder()->reset()->construct("?s a ".$key)->where("?s a ".$key)->getQuery()
                ->execute();

            $trans = new ResourceToDocumentTransformer($this->esCache, $this->typeRegistry, $this->typeMapperRegistry);

            /* @var Resource $add */
            foreach ($result->resources() as $res) {
                //echo $res->getUri();
                //echo "DEBUT";
                $doc = $trans->transform($res->getUri(), $key);
                if ($doc) {
                    //var_dump($doc);
                    $this->typeRegistry->getType($key)->addDocument($doc, $key);
                }
            }
        }
    }
}
