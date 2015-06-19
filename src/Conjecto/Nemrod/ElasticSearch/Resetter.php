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

use Elastica\Index;
use Elastica\Type;

/**
 * Class Resetter: resets all.
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

    private $typeRegistry;

    /**
     * @param $configManager
     * @param $mappingBuilder
     */
    public function __construct($configManager, $mappingBuilder, $typeRegistry)
    {
        $this->configManager = $configManager;
        $this->mappingBuilder = $mappingBuilder;
        $this->typeRegistry = $typeRegistry;
    }

    /**
     *
     */
    public function reset($type = null, $output = null)
    {

        if (!$type) {
            $types = $this->configManager->getTypes();
        } else {
            $types = array($type);
        }
        /** @var Type $type */
        foreach ($types as $type) {
            if ($output) {
                $output->writeln("resetting ".$type);
            }
            //creating index if not exists
            $this->mappingBuilder->createIndexIfNotExists($type);

            //building type mapping
            $this->mappingBuilder->buildMapping($type);
        }
    }

    /**
     * @param $index
     * @param null $output
     */
    public function resetIndex($index, $output = null)
    {
        /** @var Index $index */
        $indexObj = $this->typeRegistry->getIndex($index);


        $indexObj->close();

        if (!$indexObj->exists()) {
            $indexObj->create();
        } else {
            $indexObj->close();
        }

        $config = $this->configManager->getIndexConfig($index);
        $indexObj->setSettings($config);

        $indexObj->open();
    }
}
