<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 03/04/2015
 * Time: 10:52
 */

namespace Conjecto\Nemrod\ElasticSearch;


use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\QueryBuilder;
use EasyRdf\RdfNamespace;
use EasyRdf\Resource;
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
     * @param Manager $rm
     */
    public function search($uri, $resourceType, $propertiesUpdated, $resourceToDocumentTransformer, $rm, $resourcesModified)
    {
        $qb = $rm->getQueryBuilder();
        $frames = $this->getAllFrames($resourceType, $propertiesUpdated);
        $frames = $this->getOnlyFramesWithResourceType($frames, $resourceType, $propertiesUpdated, $qb, $uri);
        $this->updateDocuments($uri, $resourceType, $frames, $resourceToDocumentTransformer, $propertiesUpdated, $rm, $resourcesModified);
    }

    /**
     * @param string $uri
     * @param string $resourceType
     * @param array $frames
     * @param ResourceToDocumentTransformer $resourceToDocumentTransformer
     * @param Manager $rm
     */
    protected function updateDocuments($uriResourceUpdated, $updatedResourceType, $frames, $resourceToDocumentTransformer, $propertiesUpdated, $rm, $resourcesModified)
    {
        /**
         * @var QueryBuilder $qb
         */
        foreach ($frames as $index => $qb) {
            $res = $qb->getQuery()->execute();
            foreach ($res as $result) {
                $uri = $result->uri->getUri();
                $typeName = RdfNamespace::shorten($result->typeName->getUri());
                if (!array_key_exists($uri, $resourcesModified[$index])) {
                    /**
                     * @var Type $esType
                     **/
                    $esType = $this->container->get('nemrod.elastica.type.' . $index . '.' . $this->serializerHelper->getTypeName($index, $typeName));
                    $document = $resourceToDocumentTransformer->transform($uri, $typeName);
                    if ($document) {
                        $esType->addDocument($document);
                    }
                }
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param array $values
     */
    protected function constructQuery($qb, $values)
    {
        foreach ($values as $key => $value) {

        }
    }

    protected function getArrayToUpdate($frame, $propertiesUpdated)
    {
        $array = array();

        if (is_array($frame)) {
            foreach ($frame as $key => $value) {
                if (isset($value["properties"]) && count($value["properties"]) > 0) {
                    $array[$key] = $this->getArrayPropertiesToUpdate($value['properties'], $propertiesUpdated);
                }
                else if (isset($value['resources']) && count($value["resources"]) > 0) {
                    $array[$key] = $this->getArrayToUpdate($value['resources'], $propertiesUpdated);
                }
            }
        }

        return $array;
    }

    protected function getArrayPropertiesToUpdate($properties, $propertiesUpdated)
    {
        $array = array();
        foreach ($properties as $property) {
            if (in_array($property, array_keys($propertiesUpdated))) {
                $value = $propertiesUpdated[$property];
                $arrayValue = array();
                if (is_array($value)) {
                    foreach ($value as $val) {
                        if (isset($val['value'])) {
                            $arrayValue[] = $val['value'];
                        }
                    }

                    if (count($arrayValue) == 1) {
                        $array[$property] = $arrayValue[0];
                    }
                    else {
                        $array[$property] = $arrayValue;
                    }
                }
                else {
                    $array[$property] = $propertiesUpdated[$property];
                }
            }
        }

        return $array;
    }

    /**
     * @param $frames
     * @param $resourceType
     * @param $propertiesUpdated
     * @param QueryBuilder $qb
     * @return array
     */
    protected function getOnlyFramesWithResourceType($frames, $resourceType, $propertiesUpdated, $qb, $uriResourceUpdated)
    {
        $arrayOfTypes = array();
        foreach ($frames as $index => $types) {
            $arrayUnion = array();
            foreach ($types as $type => $frame) {
                $stringWhere = "?uri a ?typeName;";
                $arrayWhere = array();
                if ($this->checkIfFrameHasResourceType($frame, $resourceType, $propertiesUpdated, $arrayWhere)) {
//                    $arrayOfTypes[$index][$type] = $frame;
                    $arrayWhere = array_reverse($arrayWhere);
                    $i = 0;
                    foreach ($arrayWhere as $key) {
                        if ($i == 0) {
                            $stringWhere .= ' ' . $key;
                        }
                        else {
                            $stringWhere .= ' / ' . $key;
                        }
                        $i++;
                    }
                    $stringWhere .= ' <' . $uriResourceUpdated . '>';
                    $stringWhere .= " . VALUES ?typeName { $type }";
                    $arrayUnion[] = $stringWhere;
                }
            }
            $_qb = clone $qb->reset()->select('?uri ?typeName')->setDistinct(true);
            if (count($arrayUnion) > 0) {
                if (count($arrayUnion) > 1) {
                    $_qb->addUnion($arrayUnion);
                } else {
                    $_qb->where($arrayUnion[0]);
                }
            }
            $arrayOfTypes[$index] = $_qb;
        }

        return $arrayOfTypes;
    }

    /**
     * @param $frames
     * @param $resourceType
     * @param $propertiesUpdated
     * @param QueryBuilder $qb
     * @return bool
     */
    protected function checkIfFrameHasResourceType($frames, $resourceType, $propertiesUpdated, &$arrayWhere)
    {
        foreach ($frames as $key => $values) {
            if (isset($values['resources']) && $this->checkIfFrameHasResourceType($values['resources'], $resourceType, $propertiesUpdated, $arrayWhere)) {
               $arrayWhere[] = $key;
                return true;
            }
            if (isset($values['type']) && $values['type'] == $resourceType && isset($values['properties'])) {
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
     * @param $frame
     * @param $resourceType
     * @param $propertiesUpdated
     * @param QueryBuilder $qb
     * @return array
     */
    protected function getFrameResources($frame, $resourceType, $propertiesUpdated)
    {
        $types = array();
        foreach ($frame as $key => $value) {
            if (!strstr($key, '@') && isset($value['@type'])) {
                if ($value['@type'] == $resourceType) {
                    foreach ($this->serializerHelper->getProperties($value) as $property) {
                        if (in_array($property, array_keys($propertiesUpdated))) {
                            $types[$key]['type'] = $value['@type'];
//                            $qb->andWhere("?uri has $key");
                            $types[$key]['properties'][] = $property;
                        }
                    }
                }
                else {
                    $types[$key]['properties'] = array();
                }
                $types[$key]['type'] = $value['@type'];
                $types[$key]['resources'] = $this->getFrameResources($value, $resourceType, $propertiesUpdated);
            }
        }

        return $types;
    }

    /**
     * @param $resourceType
     * @param $propertiesUpdated
     * @param QueryBuilder $qb
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