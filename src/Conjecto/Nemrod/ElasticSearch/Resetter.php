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

    /**
     * @var IndexRegistry
     */
    private $indexRegistry;

    /**
     * @param $configManager
     * @param $mappingBuilder
     */
    public function __construct(ConfigManager$configManager, MappingBuilder $mappingBuilder, IndexRegistry $indexRegistry)
    {
        $this->configManager = $configManager;
        $this->mappingBuilder = $mappingBuilder;
        $this->indexRegistry = $indexRegistry;
    }

    /**
     *
     */
    public function reset($index, $type = null, $output = null, $force = false)
    {
        if (null !== $type) {
            $output->writeln(sprintf('<info>Resetting</info> <comment>%s/%s</comment>', $index, $type));
            $this->resetType($index, $type);
        } else {
            if ($output) {
                $output->writeln(sprintf('<info>Resetting</info> <comment>%s</comment>', $index));
            }
            $this->resetIndex($index, false, $force);
            $indexConfig = $this->configManager->getIndexConfiguration($index);
            foreach($indexConfig->getTypes() as $type => $typeConfig) {
                $this->resetType($index, $type, $output);
            }
        }
    }

    /**
     * @param $index
     * @param $type
     */
    public function resetType($index, $type)
    {
        //creating index if not exists
        $this->mappingBuilder->createIndexIfNotExists($index);

        //building type mapping
        $this->mappingBuilder->buildMapping($index, $type);
    }

    /**
     * @param $index
     * @param null $output
     */
    public function resetIndex($index, $output = null)
    {
        $indexObj = $this->indexRegistry->getIndex($index);
        $config = $this->configManager->getIndexConfiguration($index)->getSettings();

        if (!$indexObj->exists()) {
            $indexObj->create($config);
        } else {
            $indexObj->close();
            $indexObj->setSettings($config);
            $indexObj->open();
        }
    }
}
