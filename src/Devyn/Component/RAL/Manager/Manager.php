<?php

namespace Devyn\Component\RAL\Manager;
use Devyn\Component\RAL\Resource\Resource;


/**
 * Class ResourceManager
 * @package Devyn\Component\RAL\Manager
 */
class Manager
{

    /** @var  RepositoryFactory */
    private $repositoryFactory;

    /** @var PersisterIterface */
    private $persister;

    /** @var UnitOfWork */
    private $unitOfWork;


    /**
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct($repositoryFactory, $sparqlClientUrl)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->unitOfWork = new UnitOfWork($this, $sparqlClientUrl);
    }

    /**
     * @param $className
     * @return mixed
     */
    public function getRepository($className)
    {
        return $this->repositoryFactory->getRepository($className, $this);
    }

    /**
     * @param RepositoryFactory $repo
     */
    public function setRepositoryFactory(RepositoryFactory $repo)
    {
        $this->repositoryFactory = $repo;
    }

    /**
     * @return \EasyRdf_Sparql_Client
     */
    public function getPersister()
    {
        return $this->sparqlClient;
    }

    /**
     * @param $persister
     */
    public function setPersister($persister)
    {
        $this->persister = $persister;
    }

    /**
     * @param $className
     * @param $uri
     * @return mixed
     */
    public function find($className, $uri)
    {
        //trying to find resource if already loaded
        $resource = $this->unitOfWork->retrieveResource($className, $uri);

        if (!empty($resource)) {
            return $resource;
        }

        //empty result from retrieve means we havn't already loaded it. Asking to persister to find it.

        /** @var PersisterInterface $persister */
        $persister = $this->unitOfWork->getPersister();

        /** @var Resource $res */
        $res = $persister->constructUri($className, $uri);

        return $res;
    }

    public function update($resource) {
        $this->getUnitOfWork()->update($resource);
    }

    /**
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * @param UnitOfWork $unitOfWork
     */
    public function setUnitOfWork($unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }

    public function dump()
    {
        $this->getUnitOfWork()->dumpRegistered();
    }

    /**
     *
     * @param $resource
     * @return boolean
     */
    public function isResource($resource)
    {
        return $this->getUnitOfWork()->isResource($resource);
    }
}