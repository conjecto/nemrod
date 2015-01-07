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
use EasyRdf\Container;
use EasyRdf\Graph;

class UnitOfWork {

    /**
     * registered resources
     * @var  ArrayCollection $registeredResources
     */
    private $registeredResources;

    /** @var  array $blackListedResources */
    private $blackListedResources;

    /**
     * Initial snapshots of registered resources
     * @var $initialSnapshots
     */
    private $initialSnapshots;

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
        $this->registeredResources = array();
        $this->initialSnapshots = new Container('snapshots', new Graph('snapshots'));
        $this->blackListedResources = array();
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
        $this->registeredResources[$resource->getUri()] = $resource ;
        $this->resourceSnapshot($resource);
    }

    /**
     * Register a resource to the list of
     * @param $className
     * @param $uri
     * @internal param Resource $resource
     * @return mixed|null
     */
    public function retrieveResource($className, $uri)
    {
        if (!isset($this->registeredResources[$uri])) {
            return null;
        }

        return $this->registeredResources[$uri];
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
     * @param array $criteria
     * @param array $options
     * @return Collection|\EasyRdf\Collection|void
     */
    public function findBy(array $criteria, array $options)
    {
        return $this->persister->constructCollection($criteria, $options);
    }

    /**
     * @param Resource $resource
     */
    private function resourceSnapshot(Resource $resource)
    {
        //copying resource
        $this->initialSnapshots->append($resource);
        $this->graphSnapShot($resource->getGraph());
    }

    public function dumpRegistered()
    {
        //var_dump($this->registeredResources);
        echo $this->initialSnapshots->dump();
        echo $this->initialSnapshots->getGraph()->dump();
        //foreach () {

        //}
    }

    /**
     * @param Graph $graph
     */
    public function graphSnapShot(Graph $graph)
    {
        foreach ($graph->toRdfPhp() as $resource => $properties) {
            if (!$this->isManagementBlackListed($resource)) {
                foreach ($properties as $property => $values) {
                    foreach ($values as $value) {
                        if ($value['type'] == 'bnode' || $value['type'] == 'uri') {
                            $this->initialSnapshots->getGraph()->addResource($resource, $property, $value['value']);
                        } else if ($value['type'] == 'literal') {
                            $this->initialSnapshots->getGraph()->addLiteral($resource, $property, $value['value']);
                        } else {
                            //@todo check for addType
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $uri
     */
    public function managementBlackList($uri)
    {
        if (!in_array($uri, $this->blackListedResources)) {
            $this->blackListedResources []= $uri;
        }
    }

    /**
     * @param $uri
     * @return boolean
     */
    private function isManagementBlackListed($uri)
    {
        return (in_array($uri, $this->blackListedResources));
    }
}