<?php

namespace Conjecto\RAL\ElasticSearch;

/**
 * Class TypeRegistry stores and serves all known elastica types
 * @package Conjecto\RAL\ElasticSearch
 */
class TypeRegistry
{
    private $types = array();

    /**
     * @param $name
     * @param $type
     */
    public function registerType($name, $type)
    {
        $this->types[$name] = $type;
    }

    /**
     * @param $type
     * @return null
     */
    public function getType($type)
    {
        if (!isset($this->types[$type])) return null;
        return $this->types[$type];
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }
} 