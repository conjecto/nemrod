<?php

namespace Conjecto\RAL\ElasticSearch;

/**
 * Class IndexRegistry
 * @package Conjecto\RAL\ElasticSearch
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
     * @return null
     */
    public function getIndex($name)
    {
        if (!isset($this->indexes[$name])) return null;
        return $this->indexes[$name];
    }
} 