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
        $this->config[$type] = $data;
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
