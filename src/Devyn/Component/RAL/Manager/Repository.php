<?php

namespace Devyn\Component\RAL\Manager;
use Devyn\Component\QueryBuilder\QueryBuilder;
use Devyn\Component\RAL\Resource\Resource;
use Doctrine\Common\Collections\ArrayCollection;
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

        return $result;
    }

    /**
     * find a set of resources according to criterias.
     * @param array $criterias
     * @param array $options
     * @return ArrayCollection|void
     */
    public function findBy(array $criterias, array $options = array())
    {
        //first add a type criteria if not found
        if ($this->className) {
            if (empty($criterias['rdf:type'])) {
                $criterias['rdf:type'] = $this->className;
            } else if (is_array ($criterias['rdfs:Class'])) {
                $criterias['rdf:type'][] = $this->className;
            } else {
                $criterias['rdf:type'] = array ($criterias['rdf:type'], $this->className);
            }
        }

        return $this->_rm->getUnitOfWork()->findBy($criterias, $options);
    }

    /**
     * @param array $criterias
     * @param array $options
     * @return ArrayCollection|void
     */
    public function findOneBy(array $criterias, array $options = array())
    {
        $options['limit'] = 1;
        return $this->findBy($criterias, $options);
    }

    /**
     * Returns all entities
     * @return Collection
     */
    public function findAll()
    {
        return $this->findBy(array(), array());
    }

    /**
     * Create a new entity.
     */
    public function create()
    {
        return $this->_rm->getUnitOfWork()->create($this->className);
    }

    /**
     * Calls UnitOfWork delete method and returns the result
     * @param Resource $resource
     */
    public function remove(Resource $resource)
    {
        return $this->_rm->getUnitOfWork()->remove($resource);
    }

    /**
     * Save a newly created resource
     * @param Resource $resource
     */
    public function persist(Resource $resource)
    {
        return $this->_rm->getUnitOfWork()->persist($resource);
    }

    /**
     *
     * @return \Devyn\Component\QueryBuilder\QueryBuilder
     */
    public function getQueryBuilder()
    {
        $qb = $this->_rm->createQueryBuilder();
        if ($this->className) {
            $qb->construct(
                "?s a ".$this->className.". ?s ?p ?o"
            )->where("?s a ".$this->className.". ?s ?p ?o");
        }

        return $qb;
    }
}