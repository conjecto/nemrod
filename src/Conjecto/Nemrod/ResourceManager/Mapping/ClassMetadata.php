<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Mapping;

use Metadata\MergeableClassMetadata;
use Metadata\MergeableInterface;

/**
 * Class ClassMetadata.
 */
class ClassMetadata extends MergeableClassMetadata
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
     * Sets the uri patten for class.
     *
     * @param $up
     */
    public function setUriPattern($up)
    {
        $this->uriPattern = $up;
    }

    /**
     * Merge metadata.
     *
     * @param MergeableInterface $object
     */
    public function merge(MergeableInterface $object)
    {
        parent::merge($object);
        $this->types = array_merge($this->types, $object->types);
        $this->uriPattern = $object->uriPattern;
    }
}
