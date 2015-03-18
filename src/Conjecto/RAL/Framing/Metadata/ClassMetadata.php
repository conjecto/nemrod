<?php
namespace Conjecto\RAL\Framing\Metadata;

use Metadata\MergeableClassMetadata;
use Metadata\MergeableInterface;

/**
 * Extend Serializer ClassMetadata to handle extra options
 *
 * @package Conjecto\RAL\Bundle\Serializer\Metadata;
 */
class ClassMetadata extends MergeableClassMetadata
{
    /**
     * JsonLD : frame
     * @var
     */
    public $frame;

    /**
     * JsonLD : options
     * @var
     */
    public $options = array();

    /**
     * @return mixed
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * @param mixed $frame
     */
    public function setFrame($frame)
    {
        $this->frame = $frame;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Merge metadata
     * @param MergeableInterface $object
     */
    public function merge(MergeableInterface $object)
    {
        parent::merge($object);
        $this->frame =  $object->frame;
        $this->options = $object->options;
    }
}
