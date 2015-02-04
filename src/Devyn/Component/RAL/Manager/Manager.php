<?php

namespace Devyn\Component\RAL\Manager;
use Devyn\Component\QueryBuilder\Query;
use Devyn\Component\QueryBuilder\QueryBuilder;
use Devyn\Component\RAL\Resource\Resource;
use Doctrine\ORM\Mapping\ClassMetadata;
use EasyRdf\TypeMapper;
use Metadata\MetadataFactory;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;


/**
 * Class ResourceManager
 * @package Devyn\Component\RAL\Manager
 */
class Manager
{

    /** @var Client */
    private $sparqlClient;

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

    /** @var MetadataFactory */
    private $metadataFactory;

    /** @var EventDispatcher */
    private $evd;

    /**
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct($repositoryFactory, $sparqlClientUrl)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->unitOfWork = new UnitOfWork($this, $sparqlClientUrl);
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->evd;
    }

    /**
     * @param EventDispatcher $evd
     */
    public function setEventDispatcher(EventDispatcher $evd)
    {
        $this->evd = $evd;
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
     * @param null|string $className
     * @return mixed
     */
    public function getRepository($className = null)
    {
        //TypeMapper::set($className,"Devyn\\Component\\RAL\\Resource\\Resource");
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
        if($this->qb == null) {
            $this->qb = $this->createQueryBuilder();
        }
        $this->qb->reset();
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
    public function persist(Resource $resource)
    {
        $this->getUnitOfWork()->persist($resource);
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * @param string $sparqlQuery
     * @internal param string $dql
     * @return Query
     */
    public function createQuery($sparqlQuery = '')
    {
        $query = new Query($this);

        if ( ! empty($sparqlQuery)) {
            $query->setSparqlQuery($sparqlQuery);
        }

        return $query;
    }

    public function dump()
    {
        $this->getUnitOfWork()->dumpRegistered();
    }

    /**
     * @param $className
     */
    public function create($className)
    {
        return $this->getRepository($className)->create();
    }

    /**
     * calls Managers UnitOfWork commit function
     */
    public function flush()
    {
        $this->unitOfWork->commit();
    }

    /**
     * @param Resource $resource
     * @return mixed
     */
    public function remove(Resource $resource)
    {
        return $this->getUnitOfWork()->remove($resource);
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

    /**
     *
     */
    public function setMetadataFactory($metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @return MetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @param $type
     * @return \Metadata\ClassHierarchyMetadata|\Metadata\MergeableClassMetadata|null
     */
    public function getMetadata($type)
    {
        $class = TypeMapper::get($type);

        if ($class) {
            return $this->metadataFactory->getMetadataForClass($class);
        }
        return null;
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

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->sparqlClient;
    }

    /**
     * @param Client $sparqlClient
     */
    public function setClient($sparqlClient)
    {
        $this->sparqlClient = $sparqlClient;
    }

    /**
     * @param $phpClass
     */
    public function getRdfClass($phpClass)
    {
        $class = TypeMapper::get($phpClass);
        return $class;
    }
}