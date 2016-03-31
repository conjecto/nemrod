<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 31/03/2016
 * Time: 15:45
 */

namespace Conjecto\Nemrod\ResourceManager\Registry;

/**
 * Class CascadePropertyRegistry
 * @package Conjecto\Nemrod\ResourceManager\Registry
 */
class CascadePropertyRegistry
{
    /** @var array */
    protected $cascadePropertyRegistries = array();

    /**
     * @param array $cascadePropertyRegistries
     */
    public function addCascadePropertyRegistry($types, $cascadePropertyRegistries)
    {
        foreach ($types as $type) {
            foreach ($cascadePropertyRegistries as $property => $cascadeProperties) {
                $this->cascadePropertyRegistries[$type] = array($property => $cascadeProperties);
            }
        }
    }

    /**
     * @param $types
     * @return array
     */
    public function getTypeCascadeProperties($types)
    {
        $properties = array();
        foreach ($types as $type) {
            if (isset($this->cascadePropertyRegistries[$type])) {
                $properties = array_merge($this->cascadePropertyRegistries[$type]);
            }
        }

        return $properties;
    }
}