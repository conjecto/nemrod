<?php

namespace Devyn\Component\RAL\Manager;
use Doctrine\ORM\Repository\RepositoryFactory;

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
    public function __construct($repositoryFactory)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->unitOfWork = new UnitOfWork();
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
        return $this->persister->constructUri($className, $uri);
    }

}