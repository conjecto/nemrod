<?php
namespace Devyn\Component\RAL\Mapping;

//use \JMS\Serializer\Metadata\ ClassMetadata as BaseClassMetadata;
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

    public function setUriPattern($up)
    {
        $this->uriPattern = $up;
    }

    public function merge(MergeableInterface $object)
    {
        parent::merge($object);

        $this->types = array_merge($this->types, $object->types);
        $this->uriPattern = $object->uriPattern;
    }

}
