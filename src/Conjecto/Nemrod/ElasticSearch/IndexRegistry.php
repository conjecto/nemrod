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

/**
 * Class IndexRegistry.
 */
class IndexRegistry
{
    /**
     * @var array
     */
    private $indexes = array();

    /**
     * @param $name
     * @param $index
     */
    public function registerIndex($name, $index)
    {
        $this->indexes[$name] = $index;
    }

    /**
     * @param $name
     * @return Index
     */
    public function getIndex($name)
    {
        if (!isset($this->indexes[$name])) {
            return;
        }

        return $this->indexes[$name];
    }
}
