<?php

namespace Devyn\Component\RAL\Manager;
use Devyn\Component\RAL\Resource\Resource;
use EasyRdf\Exception;

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
     * @return Resource
     */
    public function find($uri)
    {

        /** @var \EasyRdf_Sparql_Result $result */
        $result = $this->_rm->find($this->className, $uri);

        if(empty($result)) {
            throw new Exception("Resource was not found");
        }

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
            $criterias['rdf:type'][] = $this->className;
        } else {
            $criterias['rdf:type'] = array ($criterias['rdf:type'], $this->className);
        }

        return $this->_rm->getUnitOfWork()->findBy($criterias, $options);
    }

    /**
     * Create a new entity.
     */
    public function create()
    {
        return $this->_rm->getUnitOfWork()->create($this->className);
    }

    /**
     * Create a new entity.
     */
    public function delete(Resource $resource)
    {
        return $this->_rm->getUnitOfWork()->delete($resource);
    }

    /**
     * Save a newly created resource
     * @param Resource $resource
     */
    public function save(Resource $resource)
    {
        return $this->_rm->getUnitOfWork()->save($this->className, $resource);
    }
}