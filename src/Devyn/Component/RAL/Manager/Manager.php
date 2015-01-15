<?php

namespace Devyn\Component\RAL\Manager;
use Devyn\Component\QueryBuilder\QueryBuilder;
use Devyn\Component\RAL\Resource\Resource;
use EasyRdf\TypeMapper;
use Symfony\Bridge\Monolog\Logger;


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

    /** @var  QueryBuilder */
    private $qb;

    /** @var  Logger */
    private $logger;

    /**
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct($repositoryFactory, $sparqlClientUrl)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->unitOfWork = new UnitOfWork($this, $sparqlClientUrl);
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
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
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * @param mixed $qb
     */
    public function setQueryBuilder($qb)
    {
        $this->qb = $qb;
    }

    /**
     * @return \EasyRdf_Sparql_Client
     */
    public function getPersister()
    {
        return $this->persister;
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
        //var_dump($resource);
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

    /**
     * @param Resource $resource
     */
    public function save(Resource $resource)
    {

        //$this->getRepository($resource->)
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