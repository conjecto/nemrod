<?php
namespace Conjecto\Nemrod\Framing\Metadata;

use Metadata\MergeableClassMetadata;
use Metadata\MergeableInterface;

/**
 * Extend Serializer ClassMetadata to handle extra options.
 */
class ClassMetadata extends MergeableClassMetadata
{
    use MetadataTrait;

    /**
     * Merge metadata.
     *
     * @param MergeableInterface $object
     */
    public function merge(MergeableInterface $object)
    {
        parent::merge($object);
        $this->frame =  $object->frame;
        $this->options = $object->options;
    }
}
