<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 03/04/2015
 * Time: 10:52
 */

namespace Conjecto\Nemrod\ElasticSearch;


use Elastica\Document;
use Elastica\Type;
use Symfony\Component\DependencyInjection\Container;

class CascadeUpdateSearch
{
    /**
     * @var SerializerHelper $serializerHelper
     */
    protected $serializerHelper;

    /**
     * @var Container $container
     */
    protected $container;

    function __construct(SerializerHelper $serializerHelper, Container $container)
    {
        $this->serializerHelper = $serializerHelper;
        $this->container = $container;
    }

    /**
     * @param string $uri
     * @param string $resourceType
     * @param array $propertiesUpdated
     * @param Type $esType
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     */
    public function search($uri, $resourceType, $propertiesUpdated, $resourceToDocumentTransformer)
    {
        $frames = $this->getAllFrames($resourceType, $propertiesUpdated);
        $frames = $this->getOnlyFramesWithResourceType($frames, $resourceType, $propertiesUpdated);
        $this->updateDocuments($uri, $resourceType, $frames, $resourceToDocumentTransformer);
    }

    /**
     * @param string $uri
     * @param string $resourceType
     * @param array $frames
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     */
    protected function updateDocuments($uriResourceUpdated, $resourceType, $frames, $resourceToDocumentTransformer)
    {
        foreach ($frames as $index => $types) {
            foreach ($types as $type => $values) {
                /**
                 * @var Type $esType
                 **/
                $esType = $this->container->get('nemrod.elastica.type.' . $index . '.' . $this->serializerHelper->getTypeName($index, $type));
            }
        }
    }

    protected function getOnlyFramesWithResourceType($frames, $resourceType, $propertiesUpdated)
    {
        $arrayOfTypes = array();
        foreach ($frames as $index => $types) {
            foreach ($types as $type => $frame) {
                if ($this->checkIfFrameHasResourceType($frame, $resourceType, $propertiesUpdated)) {
                    $arrayOfTypes[$index][$type] = $frame;
                }
            }
        }

        return $arrayOfTypes;
    }

    protected function checkIfFrameHasResourceType($frames, $resourceType, $propertiesUpdated)
    {
        foreach ($frames as $type => $values) {
            if ($this->checkIfFrameHasResourceType($values['resources'], $resourceType, $propertiesUpdated)) {
                return true;
            }
            if ($type == $resourceType && isset($values['properties'])) {
                foreach ($values['properties'] as $property) {
                    if (in_array($property, $propertiesUpdated)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function getFrameResources($frame, $resourceType, $propertiesUpdated)
    {
        $types = array();
        foreach ($frame as $key => $value) {
            if (!strstr($key, '@') && isset($value['@type'])) {
                if ($value['@type'] == $resourceType) {
                    foreach ($this->serializerHelper->getProperties($value) as $property) {
                        if (in_array($property, $propertiesUpdated)) {
                            $types[$value['@type']]['properties'][] = $property;
                        }
                    }
                }
                else {
                    $types[$value['@type']]['properties'] = array();
                }
                $types[$value['@type']]['resources'] = $this->getFrameResources($value, $resourceType, $propertiesUpdated);
            }
        }

        return $types;
    }

    protected function getAllFrames($resourceType, $propertiesUpdated)
    {
        $frames = $this->serializerHelper->getAllFrames();
        $resourceFrames = array();

        foreach ($frames as $index => $types) {
            foreach ($types as $typeName => $type) {
                $frame = $frames[$index][$typeName];
                $resourceFrames[$index][$typeName] = $this->getFrameResources($frame, $resourceType, $propertiesUpdated);
            }
        }

        return $resourceFrames;
    }

    /**
     * @param array $frame
     * @param string $resourceType
     * @param array $propertiesUpdated
     */
    protected function checkFrameContainesResourceType($frame, $resourceType, $propertiesUpdated)
    {
        $properties = $this->serializerHelper->getProperties($frame);
        foreach ($properties as $property) {
            if (isset($frame[$property]) && isset($frame[$property]['@type']) && $frame[$property]['@type'] == $resourceType) {
                return $property;
            }
        }

        return null;
    }
}