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

use Conjecto\Nemrod\Framing\Provider\ConstructedGraphProvider;
use Conjecto\Nemrod\QueryBuilder;
use Conjecto\Nemrod\Manager;
use EasyRdf\Resource;

/**
 * Class SerializerHelper.
 */
class SerializerHelper
{
    /**
     * @var array
     */
    protected $requests;

    /**
     * @var ConstructedGraphProvider
     */
    protected $cgp;

    /**
     * @param ConstructedGraphProvider $cgp
     */
    public function setConstructedGraphProvider(ConstructedGraphProvider $cgp)
    {
        $this->cgp = $cgp;
    }

    public function setConfig($config)
    {
        $this->config = $config;
        $this->guessRequests();
    }

    protected function guessRequests()
    {
        foreach ($this->config['indexes'] as $index => $types) {
            foreach ($types['types'] as $type => $settings) {
                if (!isset($settings['type']) || empty($settings['type'])) {
                    throw new \Exception('You have to specify a type for '.$type);
                }
                if (!isset($settings['frame']) || empty($settings['frame'])) {
                    throw new \Exception('You have to specify a frame for '.$type);
                }
//                if (!isset($settings['properties']) || empty($settings['properties'])) {
//                    throw new \Exception('You have to specify properties for '.$type);
//                }
                $this->fillTypeRequests($index, $type, $settings);
            }
        }
    }

    protected function fillTypeRequests($index, $type, $settings)
    {
        $this->requests[$index][$settings['type']]['name'] = $type;
        $this->requests[$index][$settings['type']]['frame'] = $settings['frame'];
//        $this->requests[$index][$settings['type']]['properties'] = array();
    }

    public function getRequest($index, $uri, $type)
    {
        return $this->cgp->getGraph(new Resource($uri), $this->getTypeFrame($index, $type));
//        if (isset($this->requests[$index][$type]['guessTypeRequest'])) {
//            return $this->requests[$index][$type]['guessTypeRequest']->bind("<$uri>", '?uri');
//        }
//        throw new \Exception('No matching found for index '.$index.' and type '.$type);
    }

    public function isPropertyTypeExist($index, $type, $properties)
    {
        if (is_array($properties)) {
            foreach ($properties as $property) {
                if ($this->isPropertyTypeExist($index, $type, $property)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($properties, $this->requests[$index][$type]['properties']);
    }

    public function isTypeIndexed($index, $type, $properties = array())
    {
        if (empty($properties)) {
            return isset($this->requests[$index][$type]);
        }

        foreach ($properties as $property) {
            if (in_array($property, $this->requests[$index][$type]['properties'])) {
                return true;
            }
        }

        return false;
    }

    public function getTypeFrame($index, $type)
    {
        return $this->getTypeKey($index, $type, 'frame');
    }

    public function getTypeName($index, $type)
    {
        return $this->getTypeKey($index, $type, 'name');
    }

    protected function getTypeKey($index, $type, $key)
    {
        if (isset($this->requests[$index][$type][$key])) {
            return $this->requests[$index][$type][$key];
        }

        throw new \Exception('No matching found for index ' . $index . ' and type ' . $type);
    }
}
