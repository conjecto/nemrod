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
use Conjecto\Nemrod\ElasticSearch\JsonLdFrameLoader;
use EasyRdf\RdfNamespace;
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
     * @var JsonLdFrameLoader
     */
    protected $jsonLdFrameLoader;

    public function setConfig($config)
    {
        $this->config = $config;
        $this->guessRequests();
    }

    /**
     * @param ConstructedGraphProvider $cgp
     */
    public function setConstructedGraphProvider(ConstructedGraphProvider $cgp)
    {
        $this->cgp = $cgp;
    }

    public function setJsonLdFrameLoader(JsonLdFrameLoader $jsonLdFrameLoader)
    {
        $this->jsonLdFrameLoader = $jsonLdFrameLoader;
    }

    public function getGraph($index, $uri, $type)
    {
        if (!$this->cgp) {
            throw new \Exception('The constructed graph provider is not setted');
        }
        return $this->cgp->getGraph(new Resource($uri), $this->getTypeFrame($index, $type));
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

    public function getIndexedTypes($types)
    {
        $indexedTypes = array();
        foreach ($types as $type) {
            foreach ($this->requests as $index => $indexTypes) {
                if ($this->isTypeIndexed($index, $type)) {
                    $indexedTypes[] = $type;
                }
            }
        }

        return $indexedTypes;
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

    public function getTypeFramePath($index, $type)
    {
        return $this->getTypeKey($index, $type, 'frame');
    }

    public function getAllTypes()
    {
        $array = array();
        foreach ($this->requests as $index => $types) {
            foreach ($types as $typeName => $type) {
                $array[] = $type['type'];
            }
        }

        return $array;
    }

    public function getAllFrames()
    {
        $array = array();
        foreach ($this->requests as $index => $types) {
            foreach ($types as $typeName => $type) {
                $array[$index][$typeName] = $this->getTypeFrame($index, $typeName);
            }
        }

        return $array;
    }

    public function getTypeFrame($index, $type)
    {
        $this->jsonLdFrameLoader->setEsIndex($index);
        return $this->jsonLdFrameLoader->load($this->getTypeFramePath($index, $type), $type);
    }

    public function getTypeName($index, $type)
    {
        return $this->getTypeKey($index, $type, 'name');
    }

    public function getAllPropertiesFromAllIndexesFromResourceType($resourceType)
    {
        $allProperties = array();
        foreach ($this->requests as $index => $types) {
            foreach ($types as $type) {
                if ($type['type'] === $resourceType) {
                    $allProperties = array_merge($allProperties, $type['properties']);
                }
            }
        }

        return array_unique($allProperties);
    }

    protected function getTypeKey($index, $type, $key)
    {
        if (isset($this->requests[$index][$type][$key])) {
            return $this->requests[$index][$type][$key];
        }

        return null;
    }

    protected function guessRequests()
    {
        foreach ($this->config['indexes'] as $index => $types) {
            if (!isset($types['types'])) {
                throw new \Exception('You have to specify types');
            }
            foreach ($types['types'] as $type => $settings) {
                if (!isset($settings['frame']) || empty($settings['frame'])) {
                    throw new \Exception('You have to specify a frame for ' . $type);
                }
                $this->fillTypeRequests($index, $type, $settings);
            }
        }
    }

    protected function fillTypeRequests($index, $typeName, $settings)
    {
        $type = null;
        $this->jsonLdFrameLoader->setEsIndex($index);
        $frame = $this->jsonLdFrameLoader->load($settings['frame'], $type, false);

        if (isset($settings['type'])) {
            $type = $settings['type'];
        } else if (isset($frame['@type'])) {
            $type = $frame['@type'];
        }

        if (!$type) {
            throw new \Exception("You have to specify a type in your config or in the jsonLdFrame " . $settings['frame']);
        }

        $frame = $this->jsonLdFrameLoader->load($settings['frame'], $type);
        $this->requests[$index][$type]['name'] = $typeName;
        $this->requests[$index][$type]['type'] = $type;
        $this->requests[$index][$type]['frame'] = $settings['frame'];
        $this->requests[$index][$type]['properties'] = $this->getProperties($frame);
    }

    public function getProperties($frame)
    {
        $properties = array();
        foreach ($frame as $key => $property) {
            if (!strstr($key, '@')) {
                $properties[] = $key;
            }
        }

        return $properties;
    }
}