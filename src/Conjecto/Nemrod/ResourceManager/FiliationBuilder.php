<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

        $arrayAccurateOnes = array();
        if (count($definedOntoTypes) == 0) {
            return null;
        }
        else if (count($definedOntoTypes) == 1) {
            $arrayAccurateOnes = $definedOntoTypes;
        }
        else {
            // try to find the most accurate type
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
     * Return all children types from a type
     * @param $type
     * @param bool $recursive
     * @param bool $justNewTypes
     * @return array
     * @throws \Exception
     */
    public function getChildrenClasses($type, $recursive = true, $justNewTypes = false)
    {
        if (!is_string($type)) {
            throw new \Exception('A string is attempted');
        }

        return $this->getRecursiveChildrenTypes(array($type), $recursive, $justNewTypes);
    }

    /**
     * @param $types
     * @param $recursive
     * @param $justNewTypes
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
     * @param $recursive
     * @param $justNewTypes
     * @return array
     */
    protected function getRecursiveChildrenTypes($types, $recursive, $justNewTypes)
    {
        $newTypes = array();
        foreach ($types as $type) {
            if (isset($this->rdfFiliation[$type]['parentClassOf']) && !empty($this->rdfFiliation[$type]['parentClassOf'])) {
                $newTypes = array_merge($newTypes, $this->rdfFiliation[$type]['parentClassOf']);
            }
        }

        if (!empty($newTypes) && $recursive) {
            return array_merge($types, $this->getRecursiveChildrenTypes($newTypes, $recursive, $justNewTypes));
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
        foreach ($types as $type) {
            $this->addEmptyType($type, null);
        }
    }

    protected function addEmptyType($type, $parentClass)
    {
        if ($type && !isset($this->rdfFiliation[$type])) {
            if (!isset($this->rdfFiliation[$type]['subClassOf'])) {
                $this->rdfFiliation[$type]['subClassOf'] = array();
            }
            if (!isset($this->rdfFiliation[$type]['parentClassOf'])) {
                $this->rdfFiliation[$type]['parentClassOf'] = array();
            }
        }
        if ($parentClass && !isset($this->rdfFiliation[$parentClass])) {
            if (!isset($this->rdfFiliation[$parentClass]['subClassOf'])) {
                $this->rdfFiliation[$parentClass]['subClassOf'] = array();
            }
            if (!isset($this->rdfFiliation[$parentClass]['parentClassOf'])) {
                $this->rdfFiliation[$parentClass]['parentClassOf'] = array();
            }
        }
    }
}