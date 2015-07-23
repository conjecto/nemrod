<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $this->parentClass =  $object->parentClass;
        $this->options = $object->options;
        $this->types = $object->types;
    }
}
