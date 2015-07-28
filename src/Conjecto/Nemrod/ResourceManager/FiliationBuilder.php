<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 27/07/2015
 * Time: 09:29
 */

namespace Conjecto\Nemrod\ResourceManager;

use EasyRdf\RdfNamespace;
use EasyRdf\TypeMapper;
use Metadata\MetadataFactory;

class FiliationBuilder
{
    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var array
     */
    public $rdfFiliation;

    /**
     * @param MetadataFactory $metadataFactory
     */
    function __construct(MetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->rdfFiliation = array();
    }

    /**
     * @param $classes
     * @return array
     */
    public function guessRdfClassFiliation($classes)
    {
        foreach ($classes as $class) {
            $metadata = $this->metadataFactory->getMetadataForClass($class);
            $types = $metadata->getTypes();
            if (!empty($types)) {
                $parentClasses = $metadata->getParentClasses();
                $this->addParentClass($types, $parentClasses);
            }
        }
        return $this->rdfFiliation;
    }

    /**
     * @param $types
     * @return array|null
     */
    public function getMostAccurateType($types, $knowTypesForExternService = array())
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

        if (count($definedOntoTypes) == 0) {
            return null;
        }

        // if only one result then return it
        if (count($definedOntoTypes) == 1) {
            return $definedOntoTypes;
        }

        // try to find the most accurate type
        $arrayAccurateOnes = array();
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
                $arrayAccurateOnes[] = $currentType;
            }
        }

        if (!empty($knowTypesForExternService)) {
            return $this->findMostAccurateTypesInKnowTypesByExternalService($arrayAccurateOnes, $knowTypesForExternService);
        }

        return $arrayAccurateOnes;
    }

    /**
     * Find the most accurate type based of one founds but also with the array of types knows by the external service
     * @param $arrayAccurateOnes
     * @param $knowTypesForExternService
     * @return array
     * @throws \Exception
     */
    protected function findMostAccurateTypesInKnowTypesByExternalService($arrayAccurateOnes, $knowTypesForExternService)
    {
        $mostAccurateTypeInKnowsExternalServiceTypes = array();
        foreach ($arrayAccurateOnes as $type) {
            if (!in_array($type, $knowTypesForExternService)) {
                $allParentTypes = $this->getParentTypes($type, false, true);
                foreach ($allParentTypes as $parentType) {
                    if (in_array($parentType, $knowTypesForExternService)) {
                        $mostAccurateTypeInKnowsExternalServiceTypes[] = $parentType;
                    }
                    else {
                        $mostAccurateTypeInKnowsExternalServiceTypes = $this->findMostAccurateTypesInKnowTypesByExternalService(array($parentType), $knowTypesForExternService);
                    }
                }
            }
            else {
                $mostAccurateTypeInKnowsExternalServiceTypes[] = $type;
            }
        }
        return $mostAccurateTypeInKnowsExternalServiceTypes;
    }

    /**
     * Return all parent types from a type
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function getParentTypes($type, $recursive = true, $justNewTypes = false)
    {
        $types = array();

        if (is_string($type)) {
            $types[] = $type;
        }
        else if (is_array($type)) {
            $types = $type;
        }
        else {
            throw new \Exception('A string or an array is attempted');
        }

        $types = $this->getRecursiveParentTypes(array($type), $recursive, $justNewTypes);
        return $types;
    }

    /**
     * @param $types
     * @return array
     */
    protected function getRecursiveParentTypes($types, $recursive, $justNewTypes)
    {
        $newTypes = array();
        foreach ($types as $type) {
            if (isset($this->rdfFiliation[$type]['subClassOf']) && !empty($this->rdfFiliation[$type]['subClassOf'])) {
                $newTypes = array_merge($newTypes, $this->rdfFiliation[$type]['subClassOf']);
            }
        }

        if (!empty($newTypes) && $recursive) {
            return array_merge($types, $this->getRecursiveParentTypes($newTypes, $recursive, $justNewTypes));
        }

        if ($justNewTypes) {
            return $newTypes;
        }
        return array_merge($types, $newTypes);
    }

    /**
     * @param $types
     * @param $parentClasses
     */
    protected function addParentClass($types, $parentClasses)
    {
        if ($parentClasses) {
            foreach ($parentClasses as $parentClass) {
                foreach ($types as $type) {
                    $this->addEmptyType($type, $parentClass);
                    if (!in_array($parentClass, $this->rdfFiliation[$type]['subClassOf'])) {
                        $this->rdfFiliation[$type]['subClassOf'][] = $parentClass;
                    }
                    if (!in_array($type, $this->rdfFiliation[$parentClass]['parentClassOf'])) {
                        $this->rdfFiliation[$parentClass]['parentClassOf'][] = $type;
                    }
                }
            }
        }
    }

    protected function addEmptyType($type, $parentClass)
    {
        if (!isset($this->rdfFiliation[$type])) {
            $this->rdfFiliation[$type]['subClassOf'] = array();
            $this->rdfFiliation[$type]['parentClassOf'] = array();
        }
        if (!isset($this->rdfFiliation[$parentClass])) {
            $this->rdfFiliation[$parentClass]['subClassOf'] = array();
            $this->rdfFiliation[$parentClass]['parentClassOf'] = array();
        }
    }
}