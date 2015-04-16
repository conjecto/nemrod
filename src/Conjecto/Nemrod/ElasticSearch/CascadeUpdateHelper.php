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

use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\QueryBuilder;
use EasyRdf\RdfNamespace;
use Elastica\Type;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class CascadeUpdateHelper.
 */
class CascadeUpdateHelper
{
    /**
     * @var SerializerHelper
     */
    protected $serializerHelper;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param SerializerHelper $serializerHelper
     * @param Container        $container
     */
    public function __construct(SerializerHelper $serializerHelper, Container $container)
    {
        $this->serializerHelper = $serializerHelper;
        $this->container = $container;
    }

    /**
     * @param string                        $uri
     * @param string                        $resourceType
     * @param array                         $propertiesUpdated
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     * @param Manager                       $rm
     * @param array                         $resourcesModified
     */
    public function cascadeUpdate($uri, $resourceType, $propertiesUpdated, $resourceToDocumentTransformer, $rm, $resourcesModified)
    {
        $qbByIndex = $this->search($uri, $resourceType, $propertiesUpdated, $rm, $resourcesModified);
        $this->updateDocuments($qbByIndex, $resourceToDocumentTransformer, $resourcesModified);
    }

    /**
     * @param array   $arrayResourcesDeleted
     * @param Manager $rm
     *
     * @return array
     */
    public function searchResourcesToCascadeRemove($arrayResourcesDeleted, $rm)
    {
        $arrayResult = array();
        foreach ($arrayResourcesDeleted as $uri => $resourceType) {
            $allProperties = $this->serializerHelper->getAllPropertiesFromAllIndexesFromResourceType($resourceType);
            $qbByIndex = $this->search($uri, $resourceType, $allProperties, $rm);
            $arrayResult = array_merge($this->executeSearchResourcesToCascadeRemoveRequest($uri, $qbByIndex), $arrayResult);
        }

        return $arrayResult;
    }

    /**
     * @param string                        $uri
     * @param string                        $typeName
     * @param string                        $index
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     */
    public function updateDocument($uri, $typeName, $index, $resourceToDocumentTransformer)
    {
        $esType = $this->container->get('nemrod.elastica.type.'.$index.'.'.$this->serializerHelper->getTypeName($index, $typeName));
        $document = $resourceToDocumentTransformer->transform($uri, $typeName);
        if ($document) {
            $esType->addDocument($document);
        }
    }

    /**
     * @param string $uri
     * @param array  $qbByIndex
     *
     * @return array
     */
    protected function executeSearchResourcesToCascadeRemoveRequest($uri, $qbByIndex)
    {
        $arrayResult = array();

        foreach ($qbByIndex as $index => $qb) {
            if ($qb !== null) {
                $res = $qb->getQuery()->execute();
                foreach ($res as $result) {
                    $uri = $result->uri->getUri();
                    $typeName = RdfNamespace::shorten($result->typeName->getUri());
                    $arrayResult[$uri] = $typeName;
                }
            }
        }

        return $arrayResult;
    }

    /**
     * @param string  $uri
     * @param string  $resourceType
     * @param array   $propertiesUpdated
     * @param Manager $rm
     *
     * @return array
     */
    protected function search($uri, $resourceType, $propertiesUpdated, Manager $rm)
    {
        $qb = $rm->getQueryBuilder();
        $frames = $this->getAllFrames($resourceType, $propertiesUpdated);
        $qbByIndex = $this->getQueryBuilderByIndex($frames, $resourceType, $propertiesUpdated, $qb, $uri);

        return $qbByIndex;
    }

    /**
     * @param array                         $frames
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     * @param array                         $resourcesModified
     */
    protected function updateDocuments($qbByIndex, ResourceToDocumentTransformer $resourceToDocumentTransformer, $resourcesModified)
    {
        foreach ($qbByIndex as $index => $qb) {
            if ($qb !== null) {
                $res = $qb->getQuery()->execute();
                foreach ($res as $result) {
                    $uri = $result->uri->getUri();
                    $typeName = RdfNamespace::shorten($result->typeName->getUri());
                    if (!array_key_exists($uri, $resourcesModified)) {
                        $this->updateDocument($uri, $typeName, $index, $resourceToDocumentTransformer);
                    }
                }
            }
        }
    }

    /**
     * @param array        $frames
     * @param string       $resourceType
     * @param array        $propertiesUpdated
     * @param QueryBuilder $qb
     * @param string       $uriResourceUpdated
     *
     * @return array
     */
    protected function getQueryBuilderByIndex($frames, $resourceType, $propertiesUpdated, QueryBuilder $qb, $uriResourceUpdated)
    {
        $arrayOfTypes = array();
        foreach ($frames as $index => $types) {
            $arrayUnion = array();
            foreach ($types as $type => $frame) {
                $stringWhere = '?uri a ?typeName;';
                $arrayWhere = array();
                if ($this->checkIfFrameHasSearchedResourceType($frame, $resourceType, $propertiesUpdated, $arrayWhere)) {
                    $arrayWhere = array_reverse($arrayWhere);
                    $i = 0;
                    foreach ($arrayWhere as $key) {
                        if ($i === 0) {
                            $stringWhere .= ' '.$key;
                        } else {
                            $stringWhere .= ' / '.$key;
                        }
                        $i++;
                    }
                    $stringWhere .= ' <'.$uriResourceUpdated.'>';
                    $stringWhere .= " . VALUES ?typeName { $type }";
                    $arrayUnion[] = $stringWhere;
                }
            }
            if (count($arrayUnion) > 0) {
                $_qb = clone $qb->reset()->select('?uri ?typeName')->setDistinct(true);
                if (count($arrayUnion) > 1) {
                    $_qb->addUnion($arrayUnion);
                } else {
                    $_qb->where($arrayUnion[0]);
                }
                $arrayOfTypes[$index] = $_qb;
            } else {
                $arrayOfTypes[$index] = null;
            }
        }

        return $arrayOfTypes;
    }

    /**
     * @param array  $frames
     * @param string $resourceType
     * @param array  $propertiesUpdated
     * @param array  $arrayWhere
     *
     * @return bool
     */
    protected function checkIfFrameHasSearchedResourceType($frames, $resourceType, $propertiesUpdated, &$arrayWhere)
    {
        foreach ($frames as $key => $values) {
            if (isset($values['resources']) && $this->checkIfFrameHasSearchedResourceType($values['resources'], $resourceType, $propertiesUpdated, $arrayWhere)) {
                $arrayWhere[] = $key;

                return true;
            }
            if (isset($values['type']) && $values['type'] === $resourceType && isset($values['properties'])) {
                foreach ($values['properties'] as $property) {
                    if (in_array($property, array_keys($propertiesUpdated))) {
                        $arrayWhere[] = $key;

                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array  $frame
     * @param string $resourceType
     * @param array  $propertiesUpdated
     *
     * @return array
     */
    protected function getFrameResources($frame, $resourceType, $propertiesUpdated)
    {
        $types = array();
        foreach ($frame as $key => $value) {
            if (!strstr($key, '@') && isset($value['@type'])) {
                if ($value['@type'] === $resourceType) {
                    foreach ($this->serializerHelper->getProperties($value) as $property) {
                        if (in_array($property, array_keys($propertiesUpdated))) {
                            $types[$key]['type'] = $value['@type'];
                            $types[$key]['properties'][] = $property;
                        }
                    }
                } else {
                    $types[$key]['properties'] = array();
                }
                $types[$key]['type'] = $value['@type'];
                $types[$key]['resources'] = $this->getFrameResources($value, $resourceType, $propertiesUpdated);
            }
        }

        return $types;
    }

    /**
     * @param string $resourceType
     * @param array  $propertiesUpdated
     *
     * @return array
     */
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
}
