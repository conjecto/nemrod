<?php

namespace Devyn\Bridge\Elastica;

/**
 * Class IndexRegistry
 * @package Devyn\Bridge\Elastica
 */
class IndexRegistry
{
    private $indexes = array();

    public function registerIndex($name, $index)
    {
        $this->indexes[$name] = $index;
    }

    public function getIndex($name)
    {
        if (!isset($this->indexes[$name])) return null;
        return $this->indexes[$name];
    }
} 