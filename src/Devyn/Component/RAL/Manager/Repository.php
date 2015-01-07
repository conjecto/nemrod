<?php

namespace Devyn\Component\RAL\Manager;
use Devyn\Bridge\EasyRdf\Resource\Resource;
use EasyRdf\Graph;
use EasyRdf\Sparql\Result;
use EasyRdf\TypeMapper;

/**
 * Class ResourceRepository
 * @package Devyn\Component\RAL\Manager
 */
class Repository
{
    /** @var  string $className */
    protected $className;

    /** @var Manager */
    protected $_rm;

    /**
     * @param $className
     * @param $resourceManager
     */
    public function __construct($className, $resourceManager)
    {
        $this->className = $className;
        $this->_rm = $resourceManager;
    }

    /**
     * @param $uri
     * @return \EasyRdf_Resource
     */
    public function find($uri)
    {

        /** @var \EasyRdf_Sparql_Result $result */
        $result = $this->_rm->find($this->className, $uri);

        return $result;
    }

    /**
     * find a set of resources according to criterias.
     * @param array $criterias
     * @param array $options
     * @return Collection|void
     */
    public function findBy(array $criterias, array $options = array())
    {
        //first add a type criteria if not found
        if (empty($criterias['rdf:type'])) {
            $criterias['rdf:type'] = $this->className;
        } else if (is_array ($criterias['rdfs:Class'])) {
            $criterias['rdf:type'] []= $this->className;
        } else {
            $criterias['rdf:type'] = array ($criterias['rdf:type'], $this->className);
        }

        return $this->_rm->getUnitOfWork()->findBy($criterias, $options);
    }
}