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
     * all types configs.
     *
     * @var array
     */
    private $config;

    /**
     * @param $type
     * @param $data
     */
    public function setConfig($type, $data)
    {
        $properties = array();
        $this->parseFrame($data['frame'], $properties);
        unset($data['frame']);
        $data['properties'] = $properties;
        $this->config[$type] = $data;
    }

    function parseFrame($frame, &$properties) {
        foreach ($frame as $key => $property) {
            if (substr($key, 0, 1) !== '@') {
                if (!isset($property['@mapping'])) {
                    if (isset($property['@type'])) {
                        $properties[$key] = array('type' => 'object');
                    }
                    else {
                        if (is_array($property)) {
                            foreach ($property as $prop => $val) {
                                if (substr($prop, 0, 1) == '@') {
                                    unset($property[$prop]);
                                }
                            }
                            if (empty($property)) {
                                $property = "";
                            }
                        }
                        if (is_array($property)) {
                            $properties[$key] = array("type" => "array");
                        }
                        else {
                            $properties[$key] = array("type" => "string");
                        }
                    }
                }
                else {
                    $properties[$key] = $property['@mapping'];
                }
            }
            if (substr($key, 0, 1) !== '@' && is_array($property)) {
                $this->parseFrame($property, $properties[$key]);
            }
        }
    }

    /**
     * returns the [section (if provided) of a] config for a given type,.
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
                return;
            }

            return $this->config[$type];
        }
        if (!isset($this->config[$type][$section])) {
            return;
        }

        return $this->config[$type][$section];
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return array_keys($this->config);
    }
}
