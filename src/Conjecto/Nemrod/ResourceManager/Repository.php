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

use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\Resource;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ResourceRepository.
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
     *
     * @return Resource
     */
    public function find($uri)
    {
        /** @var \EasyRdf_Sparql_Result $result */
        $result = $this->_rm->find($uri, $this->className);

        return $result;
    }

    /**
     * find a set of resources according to criterias.
     *
     * @param array $criterias
     * @param array $options
     *
     * @return ArrayCollection|void
     */
    public function findBy(array $criterias, array $options = array())
    {
        //first add a type criteria if not found
        $this->addClassCriterion($criterias);
        return $this->_rm->getUnitOfWork()->findBy($criterias, $options);
    }

    /**
     * @param array $criterias
     * @param array $options
     *
     * @return ArrayCollection|void
     */
    public function findOneBy(array $criterias, array $options = array())
    {
        $this->addClassCriterion($criterias);
        return $this->_rm->getUnitOfWork()->findOneBy($criterias, $options);
    }

    /**
     * Returns all entities.
     *
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
     * Calls UnitOfWork delete method and returns the result.
     *
     * @param Resource $resource
     */
    public function remove(Resource $resource)
    {
        return $this->_rm->getUnitOfWork()->remove($resource);
    }

    /**
     * Save a newly created resource.
     *
     * @param Resource $resource
     */
    public function persist(Resource $resource)
    {
        return $this->_rm->getUnitOfWork()->persist($resource);
    }

    /**
     * @return \Conjecto\Nemrod\QueryBuilder
     */
    public function getQueryBuilder()
    {
        $qb = $this->_rm->createQueryBuilder();
        if ($this->className) {
            $qb->construct(
                '?s a '.$this->className.'. ?s ?p ?o'
            )->where('?s a '.$this->className.'. ?s ?p ?o');
        }

        return $qb;
    }

    /**
     * Adds current repo class criterion to finBy criteria array
     * @param $criterias
     */
    private function addClassCriterion(&$criterias)
    {
        if ($this->className) {
            if (empty($criterias['rdf:type'])) {
                $criterias['rdf:type'] = new Resource($this->className);
            } elseif (is_array($criterias['rdfs:Class'])) {
                $criterias['rdf:type'][] = new Resource($this->className);
            } else {
                $criterias['rdf:type'] = array($criterias['rdf:type'] => new Resource ($this->className));
            }
        }
    }
}
