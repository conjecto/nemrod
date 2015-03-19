<?php

namespace Conjecto\RAL\ElasticSearch;

/**
 * Class TypeRegistry stores and serves all known elastica types.
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
     */
    public function getType($type)
    {
        if (!isset($this->types[$type])) {
            return;
        }

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
