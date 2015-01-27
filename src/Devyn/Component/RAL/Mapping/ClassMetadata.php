<?php
namespace Devyn\Component\RAL\Mapping;

/**
 * Class ClassMetadata
 * @package Devyn\Component\RAL\Mapping
 */
class ClassMetadata {
    /**
     * @var array
     */
    public $types = array();

    public $properties = array();

    public $cascadeStrategy = array();

    /**
     * @return array
     */
    public function getCascadeStrategy()
    {
        return $this->cascadeStrategy;
    }

    /**
     * @param array $cascadeStrategy
     */
    public function setCascadeStrategy($cascadeStrategy)
    {
        $this->cascadeStrategy = $cascadeStrategy;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }
}
