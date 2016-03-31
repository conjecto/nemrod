<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 31/03/2016
 * Time: 09:50
 */

namespace Conjecto\Nemrod\ResourceManager;


use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use EasyRdf\RdfNamespace;
use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;

class UriPatternStore
{
    /** @var array */
    protected $uriPatternTypedResources = array();

    /** @var FiliationBuilder $filiationBuilder */
    protected $filiationBuilder;

    /**
     * @param FiliationBuilder $filiationBuilder
     */
    public function setFiliationBuilder(FiliationBuilder $filiationBuilder)
    {
        $this->filiationBuilder = $filiationBuilder;
    }

    /**
     * @param $types
     * @param $uriPattern
     */
    public function addUriPattern($types, $uriPattern)
    {
        if (!is_array($types)) {
            throw new UnexpectedTypeException($types, 'array');
        }
        foreach ($types as $type) {
            $this->uriPatternTypedResources[$type] = $uriPattern;
        }
    }

    /**
     * @param $type
     * @return string
     */
    public function getUriPattern($types)
    {
        $type = null;
        if (is_string($types)) {
            $type = $types;
        }
        else if (is_array($types) && count($types) === 1) {
            $type = $types[0];
        }
        else if (is_array($types) && empty($types)) {
            throw new \InvalidArgumentException('The types array is empty');
        }
        else if (is_array($types) && count($types) > 1){
            $mostAccurateTypes = $this->filiationBuilder->getMostAccurateType($types);
            if ($mostAccurateTypes || is_array($mostAccurateTypes)) {
                if (count($mostAccurateTypes) == 1) {
                    $type = $mostAccurateTypes[0];
                } else {
                    throw new \InvalidArgumentException('The most accurate type has not been found');
                }
            }
        }
        else {
            throw new UnexpectedTypeException($types, 'array');
        }

        if (isset($this->uriPatternTypedResources[$type])) {
            return $this->uriPatternTypedResources[$type];
        }

        return 'nemrod_resource:';
    }
}