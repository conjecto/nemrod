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

trait MetadataTrait
{
    /**
     * JsonLD : frame.
     *
     * @var
     */
    public $frame;

    /**
     * JsonLD: subClassOf
     *
     * @var array
     */
    public $parentClasses;

    /**
     * JsonLD : options.
     *
     * @var
     */
    public $options = array();

    /**
     * Rdf types
     * @var array
     */
    public $types;

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
     * @return array
     */
    public function getParentClasses()
    {
        return $this->parentClasses;
    }

    /**
     * @param string $parentClasses
     */
    public function setParentClasses($parentClasses)
    {
        $this->parentClasses = $parentClasses;
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
