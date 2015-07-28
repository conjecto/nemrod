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
    public function reset($index = null, $type = null, $output = null, $force = false)
    {
        if (null !== $type) {
            $output->writeln(sprintf('<info>Resetting</info> <comment>%s/%s</comment>', $index, $type));
            $this->resetType($type);
        } else {

            $types = $this->configManager->getTypes();

            $indexes = null === $index
                ? array_keys($this->configManager->getIndexes())
                : array($index)
            ;

            //var_dump($indexes);
            foreach ($indexes as $index) {
                if ($output) {
                    $output->writeln(sprintf('<info>Resetting</info> <comment>%s</comment>', $index));
                }
                //var_dump($index);
                $this->resetIndex($index, false, $force);
            }

            /** @var Type $type */
            foreach ($types as $type) {
                $this->resetType($type, $output);
            }
        }
    }

    /**
     * @param $index
     * @param $type
     */
    public function resetType($type)
    {
        //creating index if not exists
        $this->mappingBuilder->createIndexIfNotExists($type);

        //building type mapping
        $this->mappingBuilder->buildMapping($type);
    }

    /**
     * @param $index
     * @param null $output
     */
    public function resetIndex($index, $output = null)
    {
        /** @var Index $index */
        $indexObj = $this->typeRegistry->getIndex($index);

        $config = $this->configManager->getIndexConfig($index);

        if (!$indexObj->exists()) {
            $indexObj->create($config);
        } else {
            $indexObj->close();
            $indexObj->setSettings($config);
            $indexObj->open();
        }
    }
}
