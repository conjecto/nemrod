<?php
namespace Devyn\Component\RAL\Mapping;

use Metadata\MergeableClassMetadata as BaseClassMetadata;
use Metadata\MergeableInterface;

/**
 * Class ClassMetadata
 * @package Devyn\Component\RAL\Mapping
 */
class ClassMetadata extends BaseClassMetadata
{
    /**
     * @var array
     */
    public $types = array();

    public $uriPattern = "";

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

    /**
     * Sets the uri patten for class
     * @param $up
     */
    public function setUriPattern($up)
    {
        $this->uriPattern = $up;
    }

    /**
     * Merge metadata
     * @param MergeableInterface $object
     */
    public function merge(MergeableInterface $object)
    {
        parent::merge($object);

        $this->types = array_merge($this->types, $object->types);
        $this->uriPattern = $object->uriPattern;
    }

}
