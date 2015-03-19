<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/01/2015
 * Time: 16:40.
 */

namespace Conjecto\RAL\ResourceManager\Manager;

use Conjecto\RAL\ResourceManager\Annotation\Rdf\Resource;
use Conjecto\RAL\ResourceManager\Manager\Event\ClearEvent;
use Conjecto\RAL\ResourceManager\Manager\Event\Events;
use Conjecto\RAL\ResourceManager\Manager\Event\PreFlushEvent;
use Conjecto\RAL\ResourceManager\Manager\Event\ResourceLifeCycleEvent;
use Conjecto\RAL\ResourceManager\Mapping\ClassMetadata;
use Conjecto\RAL\ResourceManager\Mapping\PropertyMetadata;
use Conjecto\RAL\ResourceManager\Resource\Resource as BaseResource;
use Doctrine\Common\Collections\ArrayCollection;
use EasyRdf\Container;
use EasyRdf\Exception;
use EasyRdf\Graph;
use EasyRdf\TypeMapper;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnitOfWork
{
    const STATUS_REMOVED = 1;
    const STATUS_MANAGED = 2;
    const STATUS_NEW = 3;
    const STATUS_TEMP = 4;

    /**
     * List of status for.
     */
    const STATUS_TRIPLE_UNKNOWN = "unknown";
    const STATUS_TRIPLE_ADDED = "added";
    const STATUS_TRIPLE_REMOVED = "removed";
    const STATUS_TRIPLE_UNCHANGED = "unchanged";

    /**
     * registered resources.
     *
     * @var ArrayCollection
     */
    private $tempResources;

    /**
     * registered resources.
     *
     * @var ArrayCollection
     */
    private $registeredResources;

    /** @var  array $blackListedResources */
    private $blackListedResources;

    /** @var  EventDispatcher */
    private $evd;

    /**
     * Initial snapshots of registered resources.
     *
     * @var SnapshotContainer
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
        $this->evd = $manager->getEventDispatcher();
        $this->persister = new SimplePersister($manager, $clientUrl);
        $this->registeredResources = new arrayCollection();
        $this->initialSnapshots = new SnapshotContainer($this);//;Container('snapshots', new Graph('snapshots'));
        $this->blackListedResources = new arrayCollection();
        $this->tempResources = new ArrayCollection();
        $this->uriCorrespondances = new arrayCollection();
    }

    /**
     * Register a resource to the list of.
     *
     * @param BaseResource $resource
     * @param boolean      $fromStore
     */
    public function registerResource($resource, $fromStore = true)
    {
        //echo $resource->getUri()." is a ".get_class($resource)."<br />";
        if (!$this->isRegistered($resource)) { //echo "not registered<br />";
            if ($resource instanceof BaseResource) {
                $resource->setRm($this->_rm);
            }

            $this->registeredResources[$resource->getUri()] = $resource;
            if ($fromStore) {
                $this->initialSnapshots->takeSnapshot($resource);
            }

            return $resource;
        } else {
            //echo "registered<br />";
            $tmp = $this->retrieveResource($resource->getUri());

            return $tmp;
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

        /* @var Graph $graph */
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
     * @todo remove
     * Tells if a resource is managed by current UnitOfWork
     *
     * @param $resource
     *
     * @return bool
     */
    public function isRegistered($resource)
    {
        return (method_exists($resource, 'getUri') && isset($this->registeredResources[$resource->getUri()]));
    }

    /**
     * //@todo remove first parameter
     * Register a resource to the list of.
     *
     * @param $className
     * @param $uri
     *
     * @internal param Resource $resource
     *
     * @return mixed|null
     */
    public function retrieveResource($uri)
    {
        if (!isset($this->registeredResources[$uri])) {
            return;
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
     * @param EventDispatcher $evd
     */
    public function setEventDispatcher(EventDispatcher $evd)
    {
        $this->evd = $evd;
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->evd;
    }

    /**
     * @param array $criteria
     * @param array $options
     *
     * @return Collection|\EasyRdf\Collection|void
     */
    public function findBy(array $criteria, array $options)
    {
        return $this->persister->constructSet($criteria, $options);
    }

    public function dumpRegistered()
    {
        echo $this->initialSnapshots->dump();
        //echo $this->initialSnapshots->getGraph()->dump();
    }

    /**
     * //@todo remove first parameter.
     *
     * @param $className
     * @param BaseResource $resource
     *
     * @throws Exception
     */
    public function persist(BaseResource $resource)
    {
        if (!empty($this->registeredResources[$resource->getUri()])) {
            //@todo perform "ask" on db to check if resource is already there
            //throw new Exception("Resource already exist");
        }

        //@todo relevant do do this here ?
        $this->evd->dispatch(Events::PrePersist, new ResourceLifeCycleEvent(array('resources' => array($resource))));

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
                        unset($this->tempResources[$res2->getUri()]);
                    }

                    $this->persist($res2);
                }
            }
        }
        $this->evd->dispatch(Events::PostPersist, new ResourceLifeCycleEvent(array('resources' => array($resource))));
    }

    /**
     * performing a diff between snaphshots and entities.
     */
    public function commit()
    {
        //$correspondances = array();
        $uris = array();

        //collecting
        $concernedResources = array();
        foreach ($this->registeredResources as $resource) {
            if (!isset($this->status[$resource->getUri()]) || ($this->status[$resource->getUri()] != self::STATUS_REMOVED)) {
                $concernedResources[$resource->getUri()] = $resource;
                $uris[] = $resource->getUri();
            }
        }

        /** @var BaseResource $resource */
        foreach ($concernedResources as $resource) {
            //generating an uri if resource is a blank node
            if ($resource->isBNode()) {
                /** @var ClassMetadata $metadata */
                $metadata = $this->_rm->getMetadataFactory()->getMetadataForClass(get_class($resource));
                $this->uriCorrespondances[$resource->getUri()] = $this->generateURI(array('prefix' => $metadata->uriPattern));
            }
        }

        $chSt = $this->diff(
            $this->getSnapshotForResource($this->registeredResources),
            $this->mergeRdfPhp($concernedResources),
            array('correspondence' => $this->uriCorrespondances));

        //triggering pre-flush event
        $this->evd->dispatch(Events::PreFlush, new PreFlushEvent($this->getChangesetForEvent($chSt)));

        //update if needed
        if (!empty($chSt[0]) || !empty($chSt[1])) {
            $this->persister->update(null, $chSt[0], $chSt[1], null);
        }

        //triggering post-flush events
        $this->evd->dispatch(Events::PostFlush, new ResourceLifeCycleEvent(array('uris' => $uris)));

        //reseting unit of work
        $this->reset();
    }

    /**
     * @param $chSet
     *
     * @return array
     */
    private function getChangesetForEvent($chSet)
    {
        $eventChangeSet = array();

        foreach ($chSet[0] as $uri => $changes) {
            if (!isset($eventChangeSet[$uri])) {
                $eventChangeSet[$uri] = array();
                $types = $this->registeredResources[$uri]->types();
                if ($types && count($types)) {
                    $eventChangeSet[$uri]['type'] = $types[0];
                }
                $eventChangeSet[$uri]['insert'] = array();
            }
            $eventChangeSet[$uri]['delete'] = $this->shortenPropertiesUris($changes);
        }

        foreach ($chSet[1] as $uri => $changes) {
            $resource = ($this->uriCorrespondances->indexOf($uri)) ? $this->registeredResources[$this->uriCorrespondances->indexOf($uri)] : $this->registeredResources[$uri];

            if (!isset($eventChangeSet[$uri])) {
                $eventChangeSet[$uri] = array();
                $types = $resource->types();
                if ($types && count($types)) {
                    $eventChangeSet[$uri]['type'] = $types[0];
                }
                $eventChangeSet[$uri]['delete'] = array();
            }
            $eventChangeSet[$uri]['insert'] = $this->shortenPropertiesUris($changes);
        }

        return $eventChangeSet;
    }

    /**
     * @param $phpRdfArray
     *
     * @return array
     */
    private function shortenPropertiesUris($phpRdfArray)
    {
        $result = array();

        foreach ($phpRdfArray as $prop => $values) {
            $result[$this->_rm->getNamespaceRegistry()->shorten($prop)] = $values;
        }

        return $result;
    }

    /**
     * @param BaseResource $resource
     */
    public function remove($resource)
    {
        $this->evd->dispatch(Events::PreRemove, new ResourceLifeCycleEvent(array('resources' => array($resource))));

        //@todo look for uplinks for that resource + manage
        $this->removeUplinks($resource);

        if (isset($this->registeredResources[$resource->getUri()])) {
            $this->status[$resource->getUri()] = $this::STATUS_REMOVED;
        }
        $this->evd->dispatch(Events::PostRemove, new ResourceLifeCycleEvent(array('resources' => array($resource))));
    }

    /**
     * @param $type
     *
     * @return BaseResource
     */
    public function create($type = null)
    {
        $className = null;
        if ($type) {
            $className = TypeMapper::get($type);
        }

        if (!$className) {
            $className = "Conjecto\\RAL\\ResourceManager\\Resource\\Resource";
        }

        /** @var BaseResource $resource */
        $resource = new $className($this->nextBNode(), new Graph());
        $resource->setType($type);
        $resource->setRm($this->_rm);

        //storing resource in temp resources array
        $this->tempResources[$resource->getUri()] = $resource;

        $this->evd->dispatch(Events::PostCreate, new ResourceLifeCycleEvent(array('resources' => array($resource))));

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
     *
     * @return boolean
     */
    public function isManagementBlackListed($uri)
    {
        return ($this->blackListedResources->contains($uri));
    }

    /**
     * @param $resources
     *
     * @return array
     */
    private function mergeRdfPhp($resources)
    {
        $merged = array();

        /** @var Resource $resource */
        foreach ($resources as $resource) {
            $entries = $resource->getGraph()->toRdfPhp();

            if (!isset($merged[$resource->getUri()]) && isset($entries[$resource->getUri()])) {
                $merged[$resource->getUri()] = $entries[$resource->getUri()];
            }
        }

        return $merged;
    }

    /**
     * Returns a pair of graphs consisting of (1st argument without content of 2nd argument, 2nd argument without
     * content of 1st argument).
     *
     * @param $rdfArray1
     * @param $rdfArray2
     * @param $options array containing a correspondance array
     *
     * @return array
     */
    private function diff($rdfArray1, $rdfArray2, $options)
    {
        return array(
            $this->minus($rdfArray1, $rdfArray2, $options),
            $this->minus($rdfArray2, $rdfArray1, $options),
        );
    }

    /**
     * Removes elements of $rdfArray1 that are present in $rdfArray2.
     *
     * @param $rdfArray1
     * @param $rdfArray2
     * @param $options array containing a correspondance array
     *
     * @return array
     */
    private function minus($rdfArray1, $rdfArray2, $options)
    {
        $minusArray = array();
        $tmpMinus = array();

        foreach ($rdfArray1 as $resource => $properties) {
            if ($this->isManagementBlackListed($resource)) {
                continue;
            }
            //bnodes are taken separately
            if (!empty($properties)) {
                $index = (isset($options['correspondence'][$resource])) ? $options['correspondence'][$resource] : $resource;

                foreach ($properties as $property => $values) {
                    if (!empty($values)) {
                        foreach ($values as $value) {
                            //special case of a removed resource
                            if ($this->isManagementBlackListed($value['value'])) {
                                echo 1234;
                                continue;
                            }
                            if (isset($this->status[$resource]) && $this->status[$resource] == self::STATUS_REMOVED) {
                                $tmpMinus[$index]['all'] = array();
                            } elseif (!isset($rdfArray2[$resource]) ||
                                empty($rdfArray2[$resource]) ||
                                empty($rdfArray2[$resource][$property]) ||
                                !$this->containsObject($value, $rdfArray2[$resource][$property])) {
                                if (!isset($tmpMinus[$index][$property])) {
                                    $tmpMinus[$index][$property] = array();
                                }

                                if (isset($options['correspondence'][$value['value']])) {
                                    $value = array('value' => $options['correspondence'][$value['value']], 'type' => 'uri');
                                }
                                $tmpMinus[$index][$property][] = $value;
                            }
                        }
                    }
                }
                if (isset($tmpMinus[$index]) && count($tmpMinus[$index])) {
                    $minusArray[$index] = $tmpMinus[$index];
                }
            }
        }

        return $minusArray;
    }

    /**
     * @param $resource
     *
     * @return bool
     */
    public function isResource($resource)
    {
        return ($resource instanceof \EasyRdf\Resource);
    }

    /**
     * Replaces an already managed resource instance with an.
     *
     * @param \EasyRdf\Resource $resource1
     * @param \EasyRdf\Resource $resource2
     */
    public function replaceResourceInstance($resource)
    {
        //@todo rework this.
        return;

        /** @var BaseResource $resource1 */
        $resource1 = $this->retrieveResource($resource->getUri());

        if (!$resource1) {
            return;
        }

        $uri1 = $resource1->getUri();
        $uri2 = $resource->getUri();

        if ($uri1 != $uri2) {
            //@todo create appropriate exception
            throw new \Exception("Resources do not match");
        }

        //computing a diff over two resources (same as if we were to save changes to store)
        $changeSet = $this->diff(
            $this->getSnapshotForResource(array($resource)),
            $this->mergeRdfPhp(array($resource1)),
            array('correspondence' => $this->uriCorrespondances)
        );

        //1st element of array is the set of properties that are to be transfered to new instance

        echo "$uri1 s<pre>";
        print_r($this->getSnapshotForResource(array($resource)));
        echo "</pre>";
        echo "$uri1 m<pre>";
        print_r($this->mergeRdfPhp(array($resource1)));
        echo "</pre>";
        echo "$uri1<pre>";
        print_r($changeSet);
        echo "</pre>";
        if (isset($changeSet[0][$uri1])) {
            foreach ($changeSet[0][$uri1] as $property => $values) {
                foreach ($values as $value) {
                    $resource->delete($property, $value, false);
                }
            }
        }

        //2nd element of array is the set of properties that are to be removed from new instance
        if (isset($changeSet[1][$uri1])) {
            foreach ($changeSet[1][$uri1] as $property => $values) {
                foreach ($values as $value) {
                    $resource->add($property, $value, false);
                }
            }
        }
        /* @var \EasyRdf\Resource $old */
        //$old = $this->registeredResources[$uri1];

        $this->registeredResources[$uri1] = $resource;
    }

    /**
     * @param $object
     * @param $objectsList
     *
     * @return boolean
     */
    private function containsObject($object, $objectsList)
    {
        foreach ($objectsList as $obj) {
            if ($obj['type'] == $object['type']) {
                if ($obj['type'] == 'uri') {
                    $objValue = ($this->_rm->getNamespaceRegistry()->shorten($obj['value'])) ? $this->_rm->getNamespaceRegistry()->shorten($obj['value']) : $obj['value'];
                    $objectValue = ($this->_rm->getNamespaceRegistry()->shorten($object['value'])) ? $this->_rm->getNamespaceRegistry()->shorten($object['value']) : $object['value'];

                    if ($objValue == $objectValue) {
                        return true;
                    }
                } elseif ($obj['value'] == $object['value']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extracts snapshot for resource.
     *
     * @param $resource
     *
     * @return array
     */
    private function getSnapshotForResource($resources)
    {
        $snapshot = array();
        foreach ($resources as $resource) {
            $snap = $this->initialSnapshots->getSnapshot($resource);
            if ($snap) {
                $bigSnapshot = $snap->getGraph()->toRdfPhp();

                $snapshot[$resource->getUri()] = $bigSnapshot[$resource->getUri()];

                //getting snapshots also for blank nodes
                foreach ($bigSnapshot[$resource->getUri()] as $property => $values) {
                    foreach ($values as $value) {
                        if ((!$this->isManagementBlackListed($value['value'])) && $value['type'] == 'bnode' && isset($bigSnapshot[$value['value']])) {
                            $snapshot[$value['value']] = $bigSnapshot[$value['value']];
                        }
                    }
                }
            }
        }

        return $snapshot;
    }

    /**
     * Deletes resource from managed and snapshot.
     *
     * @param $resource
     */
    private function deleteSnapshotForResource($resource)
    {
        //iterating through graph
        $this->initialSnapshots->removeSnapshot($resource);
    }

    /**
     * Queries for all resources pointing to the current one, and declares resources.
     *
     * @param $resource
     */
    private function removeUplinks($resource)
    {
        /** @var Graph $result */
        $result = $this->_rm->createQueryBuilder()
            ->construct("?s a ?t; ?p <".$resource->getUri().">")
            ->where("?s a ?t; ?p <".$resource->getUri().">")
            ->getQuery()
        ->execute();

        $resources = $result->resources();
        /** @var \EasyRdf\Resource $re */
        foreach ($resources as $re) {
            $this->registerResource($re);
            foreach ($result->properties($re->getUri()) as $prop) {
                if ($prop != "rdf:type") {
                    $re->delete($prop);
                }
            }
        }
    }

    /**
     * @todo move?
     *
     * @param $uri
     *
     * @return boolean
     */
    public function isBNode($uri)
    {
        if (substr($uri, 0, 2) == '_:') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Uri generation.
     *
     * @return string
     */
    private function generateURI($options = array())
    {
        $prefix = (isset($options['prefix']) && $options['prefix'] != '') ? $options['prefix'] : "og_bd:";

        return uniqid($prefix);
    }

    /**
     *
     */
    private function reset()
    {
        $this->registeredResources = new arrayCollection();
        //no convenient method for reseting snapshot container, so we create a new one
        $this->initialSnapshots = new SnapshotContainer($this);
        $this->blackListedResources = new arrayCollection();
        $this->tempResources = new ArrayCollection();
        $this->uriCorrespondances = new ArrayCollection();

        $this->evd->dispatch(Events::OnClear, new ClearEvent($this->_rm));
    }

    /**
     * provides a blank node uri for collections.
     *
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

    /**
     * @param Collection $coll
     */
    public function blackListCollection($coll)
    {
        //going to first element.
        $coll->rewind();
        $ptr = $coll;
        $head = $ptr->get('rdf:first');
        $next = $ptr->get('rdf:rest');

        $this->managementBlackList($coll->getUri());
        //putting all structure collection on a blacklist
        while ($head) {
            $this->managementBlackList($next->getUri());
            $head = $next->get('rdf:first');
            $next = $next->get('rdf:rest');
        }

        //and resetting pointer of collection
        $coll->rewind();
    }
}
