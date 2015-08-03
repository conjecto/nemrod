<?php

/**
 * This file is part of the FOSElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ElasticSearch;

class TypeConfig
{
    /**
     * @var IndexConfig
     */
    private $index;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $frame;

    /**
     * @var array
     */
    private $properties;


    public function __construct( $name, $type, array $frame)
    {
        $this->name = $name;
        $this->type = $type;
        $this->frame = $frame;

        $properties = array();
        $this->parseFrame($frame, $properties);
        $this->fixProperties($properties);
        $this->properties = $properties;
    }

    /**
     * @return IndexConfig
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param IndexConfig $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * @param array $frame
     */
    public function setFrame($frame)
    {
        $this->frame = $frame;
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
     * Get properties mapping
     * @param $frame
     * @param $properties
     */
    protected function parseFrame($frame, &$mapping) {
        foreach ($frame as $key => $property) {
            if (substr($key, 0, 1) !== '@') {
                $mapping[$key] = isset($property['@mapping']) ? $property['@mapping'] : array();

                if (is_array($property)) {
                    $this->parseFrame($property, $mapping[$key]['properties']);
                }
            }
        }
    }

    /**
     * Fixes any properties and applies basic defaults for any field that does not have
     * required options.
     *
     * @param $properties
     */
    protected function fixProperties(&$properties, $top = true)
    {
        foreach ($properties as $name => &$property) {
            if (!isset($property['type'])) {
                if(isset($property['properties'])) {
                    $property['type'] = 'object';
                    $property['properties']['_id'] = array("store" => true, "type" => "string", "index" => "not_analyzed");
                } else {
                    $property['type'] = 'string';
                }
            }
            if (isset($property['properties'])) {
                $this->fixProperties($property['properties'], false);
            }
//            if (in_array($property['type'], $this->skipTypes)) {
//                continue;
//            }
            if (!isset($property['store'])) {
                $property['store'] = true;
            }
        }
    }
}
