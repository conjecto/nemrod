<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ElasticSearch;

/**
 * Class ConfigManager stores and manages mainly (elastica) type mappings.
 */
class ConfigManager
{
    /**
     * all types configs
     * @var array
     */
    private $config;

    /**
     * Skip adding default information to certain fields.
     * @var array
     */
    private $skipTypes = array('completion');

    /**
     * Set a mapping config for a type
     * @param $type
     * @param $data
     */
    public function setConfig($type, $data)
    {
        $properties = array();
        $this->parseFrame($data['frame'], $properties);
        $this->fixProperties($properties);
        unset($data['frame']);
        $data['properties'] = $properties;
        $this->config[$type] = $data;
    }

    /**
     * returns the [section (if provided) of a] config for a given type
     *
     * @param $type
     * @param null $section
     *
     * @return $array|null
     */
    public function getConfig($type, $section = null)
    {
        if (!$section) {
            if (!isset($this->config[$type])) {
                return null;
            }

            return $this->config[$type];
        }
        if (!isset($this->config[$type][$section])) {
            return null;
        }

        return $this->config[$type][$section];
    }

    /**
     * Get mapped types
     * @return array
     */
    public function getTypes()
    {
        return array_keys($this->config);
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
    protected function fixProperties(&$properties)
    {
        foreach ($properties as $name => &$property) {
            if (!isset($property['type'])) {
                $property['type'] = 'string';
            }
            if (isset($property['properties'])) {
                $this->fixProperties($property['properties']);
            }
            if (in_array($property['type'], $this->skipTypes)) {
                continue;
            }
            if (!isset($property['store'])) {
                $property['store'] = true;
            }
        }
    }
}
