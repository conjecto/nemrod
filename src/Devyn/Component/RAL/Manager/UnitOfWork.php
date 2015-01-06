<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/01/2015
 * Time: 16:40
 */

namespace Devyn\Component\RAL\Manager;


use Devyn\Bridge\EasyRdf\Resource\Resource;
use Doctrine\Common\Collections\ArrayCollection;

class UnitOfWork {

    /**
     * unchanged version of managed resources
     * @var  ArrayCollection $unchangedResources
     */
    private $unchangedResources;

    /** @var PersisterInterface */
    private $persister;

    /**
     * @var Manager
     */
    private $_rm;

    /**
     * @param $manager
     * @param $clientUrl
     */
    public function __construct($manager, $clientUrl)
    {
        $this->_rm = $manager;
        $this->persister = new SimplePersister($manager, $clientUrl);
        $this->unchangedResources = array();
    }

    /**
     * Completes the resource according to provided graph
     *
     * @param $uri
     * @param $type
     * @param $properties
     */
    public function completeLoad($uri, $type, $properties)
    {

    }

    /**
     * Register a resource to the list of
     * @param Resource $resource
     */
    public function registerResource($resource)
    {
        $this->unchangedResources[$resource->getUri()] = $resource ;
    }

    /**
     * Register a resource to the list of
     * @param Resource $resource
     * @return mixed|null
     */
    public function retrieveResource($className, $uri)
    {
        if (!isset($this->unchangedResources[$uri])) {
            return null;
        }

        return $this->unchangedResources[$uri];
    }

    /**
     * @return mixed
     */
    public function getPersister()
    {
        return $this->persister;
    }

    /**
     * @param mixed $persister
     */
    public function setPersister($persister)
    {
        $this->persister = $persister;
    }

    /**
     *
     */
    public function findBy(array $criteria)
    {
        return $this->persister->constructCollection($criteria);
    }

    /**
     * @param $className
     * @param $uri
     * @return
     */
    public function find($className, $uri)
    {

    }
}