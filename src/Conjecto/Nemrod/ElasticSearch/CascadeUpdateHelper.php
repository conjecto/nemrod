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
use Conjecto\Nemrod\ResourceManager\FiliationBuilder;
use EasyRdf\RdfNamespace;
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
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var IndexRegistry
     */
    protected $indexRegistry;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param SerializerHelper $serializerHelper
     * @param Container        $container
     */
    public function __construct(SerializerHelper $serializerHelper, ConfigManager $configManager, IndexRegistry $indexRegistry,Container $container)
    {
        $this->serializerHelper = $serializerHelper;
        $this->configManager = $configManager;
        $this->indexRegistry = $indexRegistry;
        $this->container = $container;
    }

    /**
     * Search all documents containing the updated resource and update these documents
     * @param string                        $uri
     * @param string                        $resourceType
     * @param array                         $propertiesUpdated
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     * @param Manager                       $rm
     * @param array                         $resourcesModified
     */
    public function cascadeUpdate($uri, $resourceType, $propertiesUpdated, FiliationBuilder $filiationBuilder, ResourceToDocumentTransformer $resourceToDocumentTransformer, $rm, $resourcesModified)
    {
        $qbByIndex = $this->search($uri, $resourceType, $propertiesUpdated, $rm);
        $this->updateDocuments($qbByIndex, $resourceToDocumentTransformer, $resourcesModified, $filiationBuilder);
    }

    /**
     * @param array   $arrayResourcesDeleted
     * @param Manager $rm
     *
     * @return array
     */
    public function searchResourcesToCascadeRemove($arrayResourcesDeleted, $filiationBuilder, $rm)
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
     * Update the elasticsearch document
     * @param string                        $uri
     * @param array                         $types
     * @param string                        $index
     * @param FiliationBuilder              $filiationBuilder
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     */
    public function updateDocument($uri, $types, $filiationBuilder, ResourceToDocumentTransformer $resourceToDocumentTransformer)
    {
        // find the finest type of the resource in order to index the resource ony once
        $typeName = $filiationBuilder->getMostAccurateType($types, $this->serializerHelper->getAllTypes());
        // not specified in project ontology description
        if ($typeName === null) {
            throw new \Exception('No type found to update the ES document ' .$uri);
        } else if (count($typeName) == 1) {
            $typeName = $typeName[0];
        } else {
            throw new \Exception("The most accurate type for " . $uri . " has not be found.");
        }

        $typesConfig = $this->configManager->getTypesConfigurationByClass($typeName);
        foreach($typesConfig as $typeConfig) {
            $indexConfig = $typeConfig->getIndex();
            $index = $indexConfig->getName();
            $esType = $this->indexRegistry->getIndex($index)->getType($typeConfig->getType());
            $document = $resourceToDocumentTransformer->transform($uri, $index, $typeName);
            if ($document) {
                $esType->addDocument($document);
            }
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
                    $typeName = $result->typeName->getUri();
                    $arrayResult[$uri] = $typeName;
                }
            }
        }

        return $arrayResult;
    }

    /**
     * Search all documents containing the updated resource
     * @param $uri
     * @param $resourceType
     * @param $propertiesUpdated
     * @param Manager $rm
     * @return array
     */
    protected function search($uri, $resourceType, $propertiesUpdated, Manager $rm)
    {
        $qb = $rm->getQueryBuilder();
        $typesToReIndex = $this->getAllResourceTypesIndexingThisResourceType($resourceType, $propertiesUpdated);
        $qbByIndex = $this->getQueryBuilderByIndex($typesToReIndex, $resourceType, $propertiesUpdated, $qb, $uri);

        return $qbByIndex;
    }

    /**
     * update documents with resource's document to update
     * @param $qbByIndex
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     * @param $resourcesModified
     * @param FiliationBuilder $filiationBuilder
     * @throws \Exception
     */
    protected function updateDocuments($qbByIndex, ResourceToDocumentTransformer $resourceToDocumentTransformer, $resourcesModified, FiliationBuilder $filiationBuilder)
    {
        foreach ($qbByIndex as $index => $qb) {
            if ($qb !== null) {
                $res = $qb->getQuery()->execute();
                $res = $this->groupTypesByUri($res);
                foreach ($res as $uri => $types) {
                    // not reindex a resource to times
                    if (!array_key_exists($uri, $resourcesModified)) {
                        $this->updateDocument($uri, $types, $filiationBuilder, $resourceToDocumentTransformer);
                    }
                }
            }
        }
    }

    /**
     * @param $resources
     * @return array
     */
    protected function groupTypesByUri($resources)
    {
        $arrayUris = array();
        foreach ($resources as $resource) {
            if (!isset($arrayUris[$resource->uri->getUri()])) {
                $arrayUris[$resource->uri->getUri()] = array();
            }
            $arrayUris[$resource->uri->getUri()][] = $resource->allTypes->getUri();
        }

        return $arrayUris;
    }

    /**
     * With correspondant frames found, contruct a query to find resources that contains the updated resource
     * @param $typesToReIndex
     * @param $resourceType
     * @param $propertiesUpdated
     * @param QueryBuilder $qb
     * @param $uriResourceUpdated
     * @return array
     */
    protected function getQueryBuilderByIndex($typesToReIndex, $resourceType, $propertiesUpdated, QueryBuilder $qb, $uriResourceUpdated)
    {
        $arrayOfTypes = array();
        foreach ($typesToReIndex as $index => $types) {
            $arrayUnion = array();
            foreach ($types as $type => $pathToResourceType) {
                $stringWhere = '?uri a ?typeName; rdf:type ?allTypes;';
                $arrayWhere = array();
                $this->fillPathToResource($pathToResourceType, $resourceType, $propertiesUpdated, $arrayWhere);
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
            if (count($arrayUnion) > 0) {
                $_qb = clone $qb->reset()->select('?uri ?allTypes')->setDistinct(true);
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
     * Get a array with path access to a property and fill the $arrayWhere with this path
     * @param $pathToResourceType
     * @param $resourceType
     * @param $propertiesUpdated
     * @param $arrayWhere
     */
    protected function fillPathToResource($pathToResourceType, $resourceType, $propertiesUpdated, &$arrayWhere)
    {
        foreach ($pathToResourceType as $key => $values) {
            if ($key === 'resources') {
                foreach ($values as $propertyRelation => $resource) {
                    $arrayWhere[] = $propertyRelation;
                    if (isset($resource['resources'])) {
                        $this->fillPathToResource($resource['resources'], $resourceType, $propertiesUpdated, $arrayWhere);
                    }
                }
            }
            else {
                $arrayWhere[] = $key;
            }
        }
    }

    /**
     * Search all frames containing the type of the resource updated and verify that frame properties contain the resource properties updated
     * @param $frame
     * @param $resourceType
     * @param $propertiesUpdated
     * @param null $parentProperty
     * @return array
     */
    protected function getResourceTypesIndexingPropertiesOfRootResourceType($frame, $resourceType, $propertiesUpdated, $parentProperty = null)
    {
        $pathToResourceType = array();
        foreach ($frame as $key => $value) {
            if ($key === "@type" && $value === $resourceType) {
                foreach ($this->serializerHelper->getProperties($frame) as $property) {
                    if (in_array($property, $propertiesUpdated)) {
                        $pathToResourceType[$parentProperty]['type'] = $value;
                        $pathToResourceType[$parentProperty]['properties'][] = $property;
                    }
                }
            }
            else if (is_array($value)) {
                $subTypes = $this->getResourceTypesIndexingPropertiesOfRootResourceType($value, $resourceType, $propertiesUpdated, $key);
                if (!empty($subTypes)) {
                    // no parentProperty for first passage in the function
                    if ($parentProperty) {
                        $pathToResourceType[$parentProperty]['resources'] = $subTypes;
                    }
                    else {
                        $pathToResourceType['resources'] = $subTypes;
                    }
                }
            }
        }

        return $pathToResourceType;
    }

    /**
     * Search correspondant frames and return only ones corresponding to the searched mapping
     * @param string $resourceType
     * @param array  $propertiesUpdated
     * @return array
     */
    protected function getAllResourceTypesIndexingThisResourceType($resourceType, $propertiesUpdated)
    {
        $frames = $this->serializerHelper->getAllFrames();
        $resourceTypesIndexingThisResourceType = array();

        foreach ($frames as $index => $types) {
            foreach ($types as $typeName => $frame) {
                if ($typeName !== $resourceType) {
                    $pathToResourceType = $this->getResourceTypesIndexingPropertiesOfRootResourceType($frame, $resourceType, $propertiesUpdated);
                    if (!empty($pathToResourceType)) {
                        $resourceTypesIndexingThisResourceType[$index][$typeName] = $pathToResourceType;
                    }
                }
            }
        }

        return $resourceTypesIndexingThisResourceType;
    }
}
