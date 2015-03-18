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
    use MetadataTrait;

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
