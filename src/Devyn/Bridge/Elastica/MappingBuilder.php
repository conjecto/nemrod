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

    public function __construct($configManager)
    {
        $this->configManager = $configManager;
    }

    public function buildMapping($type){

        $mappingData = $this->configManager->getConfig($type, 'properties');
        if (!$mappingData) {
            throw new \Exception("no mapping for type");
        }
        $mapping = new Mapping($type);

        //@todo build mapping
        //$mapping->

        return $mapping;
    }
}