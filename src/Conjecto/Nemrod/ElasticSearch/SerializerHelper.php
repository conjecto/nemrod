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
use EasyRdf\TypeMapper;
use Metadata\MetadataFactory;
use Symfony\Component\Finder\Finder;

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

    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    protected $kernelBundles;

    function __construct(MetadataFactory $metadataFactory, $kernelBundles)
    {
        $this->metadataFactory = $metadataFactory;
        $this->kernelBundles = $kernelBundles;
        $this->rdfFiliation = array();
    }

    public function setConfig($config)
    {
        $this->config = $config;
        $this->guessRequests();
        $this->guessRdfClassFiliation();
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
        return $this->jsonLdFrameLoader->load($this->getTypeFramePath($index, $type));
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

        throw new \Exception('No matching found for index '.$index.' and type '.$type);
    }

    protected function guessRequests()
    {
        foreach ($this->config['indexes'] as $index => $types) {
            if (!isset($types['types'])) {
                throw new \Exception('You have to specify types');
            }
            foreach ($types['types'] as $type => $settings) {
                if (!isset($settings['frame']) || empty($settings['frame'])) {
                    throw new \Exception('You have to specify a frame for '.$type);
                }
                $this->fillTypeRequests($index, $type, $settings);
            }
        }
    }

    protected function fillTypeRequests($index, $typeName, $settings)
    {
        $type = null;
        $frame = $this->jsonLdFrameLoader->load($settings['frame'], null, true, true, true);

        if (isset($settings['type'])) {
            $type = $settings['type'];
        }
        else if (isset($frame['@type'])) {
            $type = $frame['@type'];
        }

        if (!$type) {
            throw new \Exception("You have to specify a type in your config or in the jsonLdFrame " . $settings['frame']);
        }

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

    protected function guessRdfClassFiliation()
    {
        $finder = new Finder();
        foreach ($this->kernelBundles as $bundle => $class) {
            // in bundle
            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()).'/RdfResource')) {
                foreach($finder->in($dir) as $file) {
                    if(is_file($file)) {
                        $classBundlePath = $this->getClassRelativePath($file->getPathName());
                        $metadata = $this->metadataFactory->getMetadataForClass($classBundlePath);
                        $types = $metadata->getTypes();
                        $types = $this->getIndexedTypes($types);
                        if (!empty($types)) {
                            $parentClass = $metadata->getParentClass();
                            $this->addParentClass($types, $parentClass);
                        }
                    }
                }
            }
        }
    }

    public function getMostAccurateType($types)
    {
        // filter types to have only types filiation defined with subClassOf annotation
        $definedOntoTypes = array();
        foreach ($types as $type) {
            $type = (string)$type;
            if ($type && !empty($type)) {
                $shortenType = RdfNamespace::shorten($type);
                if (isset($this->rdfFiliation[$shortenType])) {
                    $definedOntoTypes[] = $shortenType;
                }
            }
        }

        // if only one result then return it
        if (count($definedOntoTypes) == 1) {
            return $definedOntoTypes[0];
        }

        // try to find the most accurate type
        $arrayNoParentClassOf = array();
        foreach ($definedOntoTypes as $currentType) {
            $mostAccurate = true;
            if (isset($this->rdfFiliation[$currentType]['parentClassOf'])) {
                $subClassTypes = $this->rdfFiliation[$currentType]['parentClassOf'];
                // in class children types, look if one of them is defined with subClassOf annotation
                foreach ($definedOntoTypes as $type) {
                    if (in_array($type, $subClassTypes)) {
                        $mostAccurate = false;
                        break;
                    }
                }
            }
            if ($mostAccurate) {
                $arrayNoParentClassOf[] = $currentType;
            }
        }

        if (count($arrayNoParentClassOf) == 1) {
            return $arrayNoParentClassOf[0];
        }
        else {
            return null;
        }
    }

    protected function getClassRelativePath($filePath)
    {
        $cutName = strstr($filePath, '\\src\\');
        $cutName = substr($cutName, 5);
        $name = substr($cutName, 0, strlen($cutName) - 4);
        return str_replace('/', '\\', $name);
    }

    protected function addParentClass($types, $parentClass)
    {
        if ($parentClass) {
            foreach ($types as $type) {
                if (!(isset($this->rdfFiliation[$type])) || (isset($this->rdfFiliation[$type]['subClassOf']) && !in_array($parentClass, $this->rdfFiliation[$type]['subClassOf']))) {
                    $this->rdfFiliation[$type]['subClassOf'][] = $parentClass;
                }
                if (!(isset($this->rdfFiliation[$parentClass])) || (isset($this->rdfFiliation[$parentClass]['parentClassOf']) && !in_array($type, $this->rdfFiliation[$parentClass]['parentClassOf']))) {
                    $this->rdfFiliation[$parentClass]['parentClassOf'][] = $type;
                }
            }
        }
    }
}
