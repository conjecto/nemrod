<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 18/02/2015
 * Time: 15:17.
 */

namespace Conjecto\RAL\ElasticSearch;

use Elastica\Exception\ResponseException;
use Elastica\Type;
use Elastica\Type\Mapping;

/**
 * Class MappingBuilder builds a mapping for a given type.
 */
class MappingBuilder
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var  TypeRegistry */
    protected $typeRegistry;

    /**
     * @param $configManager
     * @param $typeRegistry
     */
    public function __construct($configManager, $typeRegistry)
    {
        $this->configManager = $configManager;
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * @param $type
     *
     * @return array
     *
     * @throws \Exception
     */
    public function buildMapping($type)
    {
        $mappingData = $this->configManager->getConfig($type, 'properties');
        if (!$mappingData) {
            throw new \Exception("no mapping for type");
        }

        /** @var Type $typeObj */
        $typeObj = $this->typeRegistry->getType($type);

        try {
            $typeObj->delete();
        } catch (ResponseException $e) {
        }

        $typeObj->setMapping($mappingData);

        return $typeObj->getMapping();
    }
}
