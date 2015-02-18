<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 18/02/2015
 * Time: 15:17
 */

namespace Devyn\Bridge\Elastica;

use Elastica\Type\Mapping;

/**
 * Class MappingBuilder builds a mapping for a given type
 * @package Devyn\Bridge\Elastica
 */
class MappingBuilder
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var  TypeRegistry */
    protected $typeRegistry;

    public function __construct($configManager, $typeRegistry)
    {
        $this->configManager = $configManager;
        $this->typeRegistry = $typeRegistry;
    }

    public function buildMapping($type){

        $mappingData = $this->configManager->getConfig($type, 'properties');
        if (!$mappingData) {
            throw new \Exception("no mapping for type");
        }
        $mapping = new Mapping($this->typeRegistry->getType($type));

        //@todo build mapping
        //$mapping->

        return $mapping;
    }
}