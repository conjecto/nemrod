<?php

namespace Devyn\Bridge\Elastica;
use Elastica\Type;

/**
 * Class Resetter: resets all
 * @package Devyn\Bridge\Elastica
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

    public function __construct($configManager, $mappingBuilder)
    {
        $this->configManager = $configManager;
        $this->mappingBuilder = $mappingBuilder;
    }

    /**
     *
     */
    public function reset()
    {
        $types = $this->configManager->getTypes();

        /** @var Type $type */
        foreach($types as $type) {
            $this->mappingBuilder->buildMapping($type);
        }
    }

} 