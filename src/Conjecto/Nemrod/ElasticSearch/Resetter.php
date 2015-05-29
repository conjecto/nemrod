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
}
