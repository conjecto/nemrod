<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/02/2015
 * Time: 17:04
 */

namespace Devyn\Bridge\Elastica;

use Devyn\Component\RAL\Manager\Manager;
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

    /**
     * @param $resourceManager
     * @param $indexManager
     * @param $typeRegistry
     * @param $resetter
     */
    public function __construct($resourceManager, $indexManager, $typeRegistry, $resetter)
    {
        $this->resourceManager = $resourceManager;
        $this->indexRegistry = $indexManager;
        $this->typeRegistry = $typeRegistry;
        $this->resetter = $resetter;
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
            $repo = $this->resourceManager->getRepository($key)->findAll();
            echo count($repo);
            //$typ->;
        }
    }
}