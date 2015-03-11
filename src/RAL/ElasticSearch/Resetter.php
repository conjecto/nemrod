<?php

namespace Conjecto\RAL\ElasticSearch;
use Elastica\Type;

/**
 * Class Resetter: resets all
 * @package Conjecto\RAL\ElasticSearch
 */
class Resetter
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var MappingBuilder
     */
    private $mappingBuilder;

    /**
     * @param $configManager
     * @param $mappingBuilder
     */
    public function __construct($configManager, $mappingBuilder)
    {
        $this->configManager = $configManager;
        $this->mappingBuilder = $mappingBuilder;
    }

    /**
     *
     */
    public function reset($type = null)
    {
        if (!$type) {
            $types = $this->configManager->getTypes();

            /** @var Type $type */
            foreach ($types as $type) {
                $this->mappingBuilder->buildMapping($type);
            }
        } else {
            $this->mappingBuilder->buildMapping($type);
        }
    }

} 