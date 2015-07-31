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

use Elastica\Exception\ResponseException;
use Elastica\Type;

/**
 * Class MappingBuilder builds a mapping for a given type.
 */
class MappingBuilder
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var  IndexRegistry */
    protected $indexRegistry;

    /**
     * @param $configManager
     * @param $typeRegistry
     */
    public function __construct($configManager, $indexRegistry)
    {
        $this->configManager = $configManager;
        $this->indexRegistry = $indexRegistry;
    }

    /**
     * @param $type
     *
     * @return array
     *
     * @throws \Exception
     */
    public function buildMapping($index, $type)
    {
        $typeConfig = $this->configManager->getTypeConfiguration($index, $type);
        $mappingData = $typeConfig->getProperties();
        if (!$mappingData) {
            throw new \Exception('no mapping for type');
        }

        /** @var Type $typeObj */
        $typeObj = $this->indexRegistry->getIndex($index)->getType($type);

        try {
            $typeObj->delete();
        } catch (ResponseException $e) {
        }

        $typeObj->setMapping($mappingData);

        return $typeObj->getMapping();
    }

    /**
     * @param $type
     */
    public function createIndexIfNotExists($index)
    {
        //$indexConfig = $this->configManager->getIndexConfiguration($index);
        $index = $this->indexRegistry->getIndex($index);
        if (!$index->exists()) {
            $index->create();
        }
    }

}
