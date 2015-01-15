<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/01/2015
 * Time: 16:40
 */

namespace Devyn\Component\RAL\Manager;


use Devyn\Component\RAL\Resource\Resource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use EasyRdf\Container;
use EasyRdf\Exception;
use EasyRdf\Graph;
use EasyRdf\TypeMapper;

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
        //not implemented
    }

    /**
     * Register a resource to the list of
     * @param Resource $resource
     */
    public function registerResource($resource)
    {
        $resource->setRm($this->_rm);
        $this->registeredResources[$resource->getUri()] = $resource ;

        $this->resourceSnapshot($resource);
    }

    /**
     *
     */
    public function setBNodes($uri, $property, $graph)
    {
        if (empty($this->registeredResources[$uri])) {
            throw new Exception("no parent resource");
        }
        /** @var \EasyRdf\Resource $owningResource */
        $owningResource = $this->registeredResources[$uri];

        /** @var Graph $graph */
        $owningGraph = $owningResource->getGraph();

        $owningGraph->delete($uri, $property);
        /** @var Resource $res */
        foreach ($graph->allResources($uri, $property) as $res) {

            //var_dump($res);
            $bnode = $owningGraph->newBNode();

            $owningGraph->add($uri, $property, $bnode);
            $rdfPhp = $res->getGraph()->toRdfPhp();

            //
            if (!empty($rdfPhp[$res->getUri()])) {
                foreach ($rdfPhp[$res->getUri()] as $prop => $vals) {
                    foreach ($vals as $val) {
                        //echo $prop;
                        $bnode->set($prop, $val['value']);
                    }
                }
            }
        }
    }

    /**
     * Tells if a resource is managed by current UnitOfWork
     * @param $resource
     * @return bool
     */
    public function isRegistered($resource)
    {
        return (method_exists($resource, 'getUri') && isset($this->registeredResources[$resource->getUri()]));
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
     * @return PersisterInterface
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
        $this->graphSnapshot($resource->getGraph());
    }

    public function dumpRegistered()
    {
        echo $this->initialSnapshots->dump();
        echo $this->initialSnapshots->getGraph()->dump();
    }

    /**
     * update a resource
     * @param $resource
     */
    public function update(Resource $resource)
    {
        //resource must be an instance of Resource that is already managed
        if ((!$this->isResource($resource)) || (!$this->isRegistered($resource))) {
            throw new \InvalidArgumentException("Provided object is not a resource or is not currently managed");
        }
        $chgSet=$this->computeChangeSet($resource);
        $this->persister->update($resource->getUri(), $chgSet[0], $chgSet[1], array());
        //print_r(, false);
    }

    /**
     * @param Resource $resource
     */
    public function save($className, Resource $resource)
    {
        if (!empty($this->registeredResources[$resource->getUri()])) {
            //@todo perform "ask" on db to check if resource is already there
            throw new Exception("Resource already exist");
        }
        $this->registerResource($resource);
        $this->persister->save($resource->getUri(),$resource->getGraph()->toRdfPhp());
    }

    /**
     * @param Resource $resource
     */
    public function delete(Resource $resource)
    {
        $this->persister->delete($resource->getUri(),$resource->getGraph()->toRdfPhp());
    }

    /**
     * @param $className
     * @return Resource
     */
    public function create($className)
    {
        $classN = TypeMapper::get($className);
        /** @var Resource $resource */
        $resource = new $classN($this->generateURI(),new Graph());
        $resource->setType( $className);
        $resource->setRm($this->_rm);
        return $resource;
    }

    /**
     * @param Graph $graph
     */
    public function graphSnapshot(Graph $graph)
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
            $this->blackListedResources[] = $uri;
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

    /**
     *
     */
    private function computeChangeSet(Resource $resource)
    {
        return $this->diff($this->getSnapshotForResource($resource), $resource->getGraph()->toRdfPhp());
    }

    /**
     * Returns a pair of graphs consisting of (1st argument without content of 2nd argument, 2nd argument without
     * content of 1st argument)
     * @param $rdfArray1
     * @param $rdfArray2
     * @return array
     */
    private function diff($rdfArray1, $rdfArray2)
    {
        return array($this->minus($rdfArray1, $rdfArray2), $this->minus($rdfArray2, $rdfArray1));
    }

    /**
     * Removes elements of $rdfArray1 that are present in $rdfArray2
     * @param $rdfArray1
     * @param $rdfArray2
     * @return array
     */
    private function minus($rdfArray1, $rdfArray2)
    {
        $minusArray = array();
        $bnodesCollector = array();

        foreach ($rdfArray1 as $resource => $properties) {
            //bnodes are taken separately
            if (!empty($properties) && !$this->isBNode($resource)) {
                $minusArray[$resource] = array();
                foreach ($properties as $property => $values) {
                    if (!empty($values)) {
                        foreach ($values as $value) {

                            if (($value['type'] == 'bnode') && isset($rdfArray1[$value['value']]) && !empty($rdfArray1[$value['value']])) {
                                $minusArray[$resource][$property][] = $value ;
                                $bnodesCollector [$value['value']]= $rdfArray1[$value['value']];
                            }else if (empty($rdfArray2[$resource]) ||
                                empty($rdfArray2[$resource][$property]) ||
                                !$this->containsObject($value, $rdfArray2[$resource][$property])) {
                                if (!isset($minusArray[$resource][$property])) $minusArray[$resource][$property] = array();
                                $minusArray[$resource][$property][] = $value ;
                            }
                            //content of bnode resource is stored in a separate array and will be merged with final result
                            if (($value['type'] == 'bnode') && isset($rdfArray1[$value['value']]) && !empty($rdfArray1[$value['value']])) {
                                //@todo nothing for now but try a similarity test between blanknodes.
                            }
                        }
                    }
                }
            }
        }

        //including blank nodes to final result
        if (!empty($bnodesCollector)) {
            foreach ($bnodesCollector as $uri => $content) {
                $minusArray[$uri] = $content;
            }
        }

        //print_r($minusArray); echo "<br/>";
        return $minusArray;
    }

    /**
     * @param $resource
     * @return bool
     */
    public function isResource($resource)
    {
        return ($resource instanceof \EasyRdf\Resource);
    }

    /**
     * @param $object
     * @param $objectsList
     * @return boolean
     */
    private function containsObject($object, $objectsList)
    {
        foreach($objectsList as $obj) {
            if (($obj['type'] == $object['type']) && ($obj['value'] == $object['value'])){
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts snapshot for resource
     * @param $resource
     * @return array
     */
    private function getSnapshotForResource($resource)
    {
        $bigSnapshot = $this->initialSnapshots->getGraph()->toRdfPhp();

        $snapshot = array($resource->getUri() => $bigSnapshot[$resource->getUri()]);

        //getting snapshots also for blank nodes
        foreach ($bigSnapshot[$resource->getUri()] as $property => $values) {
            foreach ($values as $value) {
                if ($value['type'] == 'bnode' && isset ($bigSnapshot[$value['value']]) ) {
                    $snapshot[$value['value']] = $bigSnapshot[$value['value']];
                }
            }
        }

        return $snapshot;
    }

    /**
     * @todo move?
     * @param $uri
     * @return boolean
     */
    public function isBNode($uri) {
        if (substr($uri, 0, 2) == '_:') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Uri generation.
     * @return string
     */
    private function generateURI()
    {
        return uniqid("ogbd:");
    }
}