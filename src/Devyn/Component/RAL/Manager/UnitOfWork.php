<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/01/2015
 * Time: 16:40
 */

namespace Devyn\Component\RAL\Manager;


use Devyn\Component\RAL\Annotation\Rdf\Resource;
use Devyn\Component\RAL\Mapping\ClassMetadata;
use Devyn\Component\RAL\Mapping\PropertyMetadata;
use Devyn\Component\RAL\Resource\Resource as BaseResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use EasyRdf\Container;
use EasyRdf\Exception;
use EasyRdf\Graph;
use EasyRdf\TypeMapper;

class UnitOfWork {

    const STATUS_REMOVED = 1 ;
    const STATUS_MANAGED = 2 ;
    const STATUS_NEW = 3 ;
    const STATUS_TEMP = 4 ;

    /**
     * registered resources
     * @var  ArrayCollection $registeredResources
     */
    private $tempResources;

    /**
     * registered resources
     * @var  ArrayCollection $registeredResources
     */
    private $registeredResources;

    /** @var  array $blackListedResources */
    private $blackListedResources;

    /**
     * Initial snapshots of registered resources
     * @var SnapshotContainer $initialSnapshots
     */
    private $initialSnapshots;

    /** @var PersisterInterface */
    private $persister;

    /** @var  int $variableCount */
    private $bnodeCount = 0;

    /** @var array */
    private $status = array();

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
        $this->registeredResources = new arrayCollection();
        $this->initialSnapshots = new SnapshotContainer($this);//;Container('snapshots', new Graph('snapshots'));
        $this->blackListedResources = new arrayCollection();
        $this->tempResources = new ArrayCollection();
    }

    /**
     * Register a resource to the list of
     * @param BaseResource $resource
     * @param boolean $fromStore
     */
    public function registerResource($resource, $fromStore = true)
    {

        //echo $resource->getUri();
        $resource->setRm($this->_rm);
        $this->registeredResources[$resource->getUri()] = $resource ;
        if (method_exists($resource, "setRm")) {
            $resource->setRm($this->_rm);
            $this->registeredResources[$resource->getUri()] = $resource;

        }

        if ($fromStore) {
            $this->initialSnapshots->takeSnapshot($resource);
        }
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
        /** @var BaseResource $res */
        foreach ($graph->allResources($uri, $property) as $res) {

            $bnode = $owningGraph->newBNode();

            $owningGraph->add($uri, $property, $bnode);
            $rdfPhp = $res->getGraph()->toRdfPhp();

            //
            if (!empty($rdfPhp[$res->getUri()])) {
                foreach ($rdfPhp[$res->getUri()] as $prop => $vals) {
                    foreach ($vals as $val) {

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
     * //@todo remove first parameter
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

    public function dumpRegistered()
    {
        echo $this->initialSnapshots->dump();
        //echo $this->initialSnapshots->getGraph()->dump();
    }

    /**
     * //@todo remove first parameter
     * @param $className
     * @param BaseResource $resource
     * @throws Exception
     */
    public function persist(BaseResource $resource)
    {
        if (!empty($this->registeredResources[$resource->getUri()])) {
            //@todo perform "ask" on db to check if resource is already there
            throw new Exception("Resource already exist");
        }
        $this->registerResource($resource, $fromStore = false);

        //getting entities to be cascade persisted
        $metadata = $this->_rm->getMetadataFactory()->getMetadataForClass(get_class($resource));
        /** @var PropertyMetadata $pm */
        foreach ($metadata->propertyMetadata as $pm) {
            if (is_array($pm->cascade) && in_array("persist", $pm->cascade)) {
                $cascadeResources = $resource->allResources($pm->value);

                foreach ($cascadeResources as $res2) {

                    //sub-resource may have been stored as a temporary resource -
                    // it becomes a managed resource
                    if (!empty($this->tempResources[$res2->getUri()])) {
                        $res2 = $this->tempResources[$res2->getUri()];
                        unset ($this->tempResources[$res2->getUri()]);
                    }

                    $this->persist($res2);
                }
            }
        }
    }

    /**
     * performing a diff between snaphshots and entities
     */
    public function commit()
    {

        $correspondances = array();

        /** @var BaseResource $resource */
        foreach ($this->registeredResources as $resource)
        {
            //generating an uri if resource is a blank node
            if ($resource->isBNode()) {
                /** @var ClassMetadata $metadata */
                $metadata = $this->_rm->getMetadataFactory()->getMetadataForClass(get_class($resource));
                $correspondances[$resource->getUri()] = $this->generateURI(array('prefix' => $metadata->uriPattern));
            }
        }
        $chSt = $this->computeChangeSet(array(), array('correspondence' => $correspondances));

        //update if needed
        if (!empty($chSt[0]) || !empty($chSt[1])) {
            $this->persister->update(null, $chSt[0], $chSt[1], null);
        }
    }

    /**
     * @param BaseResource $resource
     */
    public function remove(BaseResource $resource)
    {
        //$this->persister->delete($resource->getUri(),$resource->getGraph()->toRdfPhp());
        if (isset ($this->registeredResources[$resource->getUri()])){
            //unset($this->registeredResources[$resource->getUri()]);
            $this->status[$resource->getUri()] = $this::STATUS_REMOVED;
        }
        //$this->deleteSnapshotForResource($resource);
    }

    /**
     * @param $type
     * @return BaseResource
     */
    public function create($type = null)
    {
        $className = null;
        if ($type) {
            $className = TypeMapper::get($type);
        }

        if (!$className) {
            $className = "Devyn\\Component\\RAL\\Resource\\Resource";
        }

        /** @var BaseResource $resource */
        $resource = new $className($this->nextBNode(), new Graph());
        $resource->setType($type);
        $resource->setRm($this->_rm);

        //storing resource in temp resources array
        $this->tempResources[$resource->getUri()] = $resource;

        return $resource;
    }

    /**
     * @param $uri
     */
    public function managementBlackList($uri)
    {
        if (!$this->blackListedResources->contains($uri, $this->blackListedResources)) {
            $this->blackListedResources[] = $uri;
        }
    }

    /**
     * @param $uri
     * @return boolean
     */
    public function isManagementBlackListed($uri)
    {
        return ($this->blackListedResources->contains($uri));
    }

    /**
     *
     */
    private function computeChangeSet($resources = array(), $options)
    {
        if (empty($resources)) {
            $resources = $this->registeredResources;
        }

        $outResources = array();
        foreach($resources as $resource) {
            if (!isset($this->status[$resource->getUri()]) || ($this->status[$resource->getUri()] != self::STATUS_REMOVED)) {
                $outResources[$resource->getUri()] = $resource;
            }
        }

        return $this->diff($this->getSnapshotForResource($resources), $this->mergeRdfPhp($outResources), $options);
    }

    /**
     *
     * @param $resources
     * @return array
     */
    private function mergeRdfPhp($resources)
    {
        $merged = array();

        /** @var Resource $resource */
        foreach ($resources as $resource) {
            $entries = $resource->getGraph()->toRdfPhp();
            if(!isset($merged[$resource->getUri()]) && isset($entries[$resource->getUri()])) {
                //echo "w";
                $merged[$resource->getUri()] = $entries[$resource->getUri()];
            }

        }
        //echo count ($merged);
        return $merged;
    }

    /**
     * Returns a pair of graphs consisting of (1st argument without content of 2nd argument, 2nd argument without
     * content of 1st argument)
     * @param $rdfArray1
     * @param $rdfArray2
     * @param $options array containing a correspondance array
     * @return array
     */
    private function diff($rdfArray1, $rdfArray2, $options)
    {
        return array(
            $this->minus($rdfArray1, $rdfArray2, $options),
            $this->minus($rdfArray2, $rdfArray1, $options)
        );
    }

    /**
     * Removes elements of $rdfArray1 that are present in $rdfArray2
     * @param $rdfArray1
     * @param $rdfArray2
     * @param $options array containing a correspondance array
     * @return array
     */
    private function minus($rdfArray1, $rdfArray2, $options)
    {
        $minusArray = array();
        $tmpMinus = array();

        foreach ($rdfArray1 as $resource => $properties) {
            //echo "[".$resource."]";
            //bnodes are taken separately
            if (!empty($properties)) {
                $index = (isset($options['correspondence'][$resource])) ? $options['correspondence'][$resource] : $resource ;

                foreach ($properties as $property => $values) {
                    if (!empty($values)) {
                        foreach ($values as $value) {
                            if (!isset ($rdfArray2[$resource]) ||
                                empty($rdfArray2[$resource]) ||
                                empty($rdfArray2[$resource][$property]) ||
                                !$this->containsObject($value, $rdfArray2[$resource][$property])) {
                                if (!isset($tmpMinus[$index][$property])) $tmpMinus[$index][$property] = array();

                                if (isset($options['correspondence'][$value['value']])) {
                                    $value = array('value' => $options['correspondence'][$value['value']], 'type' => 'uri');
                                }
                                $tmpMinus[$index][$property][] = $value ;
                            }
                        }
                    }
                }
                if (isset ($tmpMinus[$index]) && count($tmpMinus[$index])) {

                    $minusArray[$index] = $tmpMinus[$index];
                }

            }
        }
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
    private function getSnapshotForResource($resources)
    {
        $snapshot = array();
        foreach ($resources as $resource) {
            if ($this->initialSnapshots->getSnapshot($resource)) {
                $bigSnapshot = $this->initialSnapshots->getSnapshot($resource)->getGraph()->toRdfPhp();

                $snapshot[$resource->getUri()] = $bigSnapshot[$resource->getUri()];

                //getting snapshots also for blank nodes
                foreach ($bigSnapshot[$resource->getUri()] as $property => $values) {
                    foreach ($values as $value) {
                        if ($value['type'] == 'bnode' && isset ($bigSnapshot[$value['value']])) {
                            $snapshot[$value['value']] = $bigSnapshot[$value['value']];
                        }
                    }
                }
            }
        }

        return $snapshot;
    }

    /**
     * Deletes resource from managed and snapshot
     * @param $resource
     */
    private function deleteSnapshotForResource($resource)
    {
        //iterating through graph
        $this->initialSnapshots->removeSnapshot($resource);
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
    private function generateURI($options = array())
    {
        $prefix = (isset($options['prefix']) && $options['prefix'] != '') ? $options['prefix'] : "ogbd:" ;

        return uniqid($prefix);
    }

    /**
     * provides a blank node uri for collections
     * @return string
     */
    public function nextBNode()
    {
        return "_:bn".(++$this->bnodeCount);
    }

    /**
     *
     */
    public function isManaged(BaseResource $resource)
    {
        return (isset($this->registeredResources[$resource->getUri()]));
    }

}