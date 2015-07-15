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

use Conjecto\Nemrod\ResourceManager\Annotation\Resource;
use Conjecto\Nemrod\ResourceManager\Event\ClearEvent;
use Conjecto\Nemrod\ResourceManager\Event\Events;
use Conjecto\Nemrod\ResourceManager\Event\PreFlushEvent;
use Conjecto\Nemrod\ResourceManager\Event\ResourceLifeCycleEvent;
use Conjecto\Nemrod\ResourceManager\Mapping\ClassMetadata;
use Conjecto\Nemrod\ResourceManager\Mapping\PropertyMetadata;
use Conjecto\Nemrod\Resource as BaseResource;
use Conjecto\Nemrod\Manager;
use Doctrine\Common\Collections\ArrayCollection;
use EasyRdf\Collection;
use EasyRdf\Exception;
use EasyRdf\Literal;
use EasyRdf\Graph;
use EasyRdf\TypeMapper;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnitOfWork
{
    const STATUS_REMOVED = 1;
    const STATUS_MANAGED = 2;
    const STATUS_NEW = 3;
    const STATUS_TEMP = 4;
    const STATUS_DIRTY = 5;

    /**
     * List of status for.
     */
    const STATUS_TRIPLE_UNKNOWN = 'unknown';
    const STATUS_TRIPLE_ADDED = 'added';
    const STATUS_TRIPLE_REMOVED = 'removed';
    const STATUS_TRIPLE_UNCHANGED = 'unchanged';

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
    public function __construct(Manager $manager, $clientUrl)
    {
        $this->_rm = $manager;
        $this->evd = $manager->getEventDispatcher();
        $this->persister = new SimplePersister($manager, $clientUrl);
        $this->registeredResources = new arrayCollection();
        $this->initialSnapshots = new SnapshotContainer($this);
        $this->blackListedResources = new arrayCollection();
        $this->tempResources = new ArrayCollection();
        $this->uriCorrespondances = new arrayCollection();
    }

    /**
     * Register a resource to the list of.
     *
     * @param BaseResource $resource
     * @param bool         $fromStore
     *
     * @return \Conjecto\Nemrod\Resource|mixed|null
     */
    public function registerResource(BaseResource $resource, $fromStore = true)
    {
        if (!$this->isRegistered($resource)) {
            $resource->setRm($this->_rm);
            $uri = $this->_rm->getNamespaceRegistry()->expand($resource->getUri());
            $this->registeredResources[$uri] = $resource;
            if ($fromStore) {
                $resource->setReady();
                $this->setStatus($resource, self::STATUS_MANAGED);
            }

            return $resource;
        } else {
            $tmp = $this->retrieveResource($resource->getUri());

            return $tmp;
        }
    }

    /**
     *
     */
    public function setBNodes($uri, $property, Graph $graph)
    {
        $uri = $this->_rm->getNamespaceRegistry()->expand($uri);
        if (empty($this->registeredResources[$uri])) {
            throw new Exception('no parent resource');
        }
        /** @var \Conjecto\Nemrod\Resource $owningResource */
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
     * Tells if a resource is managed by current UnitOfWork.
     *
     * @param $resource
     *
     * @return bool
     */
    public function isRegistered(BaseResource $resource)
    {
        if (!method_exists($resource, 'getUri')) {
            return false;
        }
        $uri = $this->_rm->getNamespaceRegistry()->expand($resource->getUri());

        return isset($this->registeredResources[$uri]);
    }

    /**
     * Register a resource to the list of.
     *
     * @param $className
     * @param $uri
     *
     *
     * @return mixed|null
     */
    public function retrieveResource($uri)
    {
        $uri = $this->_rm->getNamespaceRegistry()->expand($uri);

        if (!isset($this->registeredResources[$uri])) {

            //trying to find the resource through its bnode
            if (!$this->isBNode($uri)) {
                $uri = $this->uriCorrespondances->indexOf($uri);
            }

            if (!$uri || !isset($this->registeredResources[$uri])) {
                return;
            }
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

    /**
     * @param array $criteria
     * @param array $options
     *
     * @return Collection|\EasyRdf\Collection|void
     */
    public function findOneBy(array $criteria, array $options)
    {
        return $this->persister->constructOne($criteria, $options);
    }

    public function dumpRegistered()
    {
        echo $this->initialSnapshots->dump();
    }

    /**
     * @param $className
     * @param BaseResource $resource
     *
     * @throws Exception
     */
    public function persist(BaseResource $resource)
    {
        $status = $this->getStatus($resource);
        //nothing to do for an already managed resource. just returning uri
        if ($status == self::STATUS_MANAGED) {
            return $resource->getUri();
        }
        else if (!$status) {
            $this->setStatus($resource, self::STATUS_NEW);

            if ($resource->isBNode() && !isset($this->uriCorrespondances[$resource->getUri()])) {
                /** @var ClassMetadata $metadata */
                $metadata = $this->_rm->getMetadataFactory()->getMetadataForClass(get_class($resource));
                $this->uriCorrespondances[$resource->getUri()] = $this->generateURI(array('prefix' => $metadata->uriPattern));
            } else if (isset($this->uriCorrespondances[$resource->getUri()])) {
                //if uri is already set, we stop everything and return it.
                return $this->uriCorrespondances[$resource->getUri()];
            }
        }

        $this->evd->dispatch(Events::PrePersist, new ResourceLifeCycleEvent(array('resources' => array($resource))));

        $this->registerResource($resource, false);

        //getting entities to be cascade persisted
        $metadata = $this->_rm->getMetadataFactory()->getMetadataForClass(get_class($resource));
        /** @var PropertyMetadata $pm */
        foreach ($metadata->propertyMetadata as $pm) {
            if (is_array($pm->cascade) && in_array('persist', $pm->cascade)) {
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

        return $this->uriCorrespondances[$resource->getUri()];
    }

    /**
     * performing a diff between snaphshots and entities.
     */
    public function commit($array = array())
    {
        $uris = array();

        //collecting
        $concernedResources = array();
        foreach ($this->registeredResources as $resource) {
            $status = $this->getStatus($resource);
            if ($status === self::STATUS_NEW || $status === self::STATUS_DIRTY) {
                $concernedResources[$resource->getUri()] = $resource;
                $uris[] = $resource->getUri();
            }
        }

        /** @var BaseResource $resource */
        foreach ($concernedResources as $resource) {
            //generating an uri if resource is a blank node
            if ($resource->isBNode() && !isset($this->uriCorrespondances[$resource->getUri()])) {
                /** @var ClassMetadata $metadata */
                $metadata = $this->_rm->getMetadataFactory()->getMetadataForClass(get_class($resource));
                $this->uriCorrespondances[$resource->getUri()] = $this->generateURI(array('prefix' => $metadata->uriPattern));
            }
        }
        $this->getSnapshotForResource($this->registeredResources, $array);

        $chSt = $this->diff(
            $this->getSnapshotForResource($this->registeredResources),
            $this->mergeRdfPhp($concernedResources),
            array('correspondence' => $this->uriCorrespondances));

        //triggering pre-flush event
        $this->evd->dispatch(Events::PreFlush, new PreFlushEvent($this->getChangesetForEvent($chSt), $this->_rm));

        //update if needed
        if (!empty($chSt[0]) || !empty($chSt[1])) {
            $this->persister->update(null, $chSt[0], $chSt[1], null);
        }

        //triggering post-flush events
        $this->evd->dispatch(Events::PostFlush, new ResourceLifeCycleEvent(array('rm' => $this->_rm, 'uris' => $uris)));

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

            if (is_array($changes) && current(array_keys($changes)) === 'all') {
                $eventChangeSet[$uri]['delete'] = 'all';
            } else {
                $eventChangeSet[$uri]['delete'] = $this->shortenPropertiesUris($changes);
            }
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
    public function remove(BaseResource $resource)
    {
        if (!$resource->isBNode()) {
            $this->evd->dispatch(Events::PreRemove, new ResourceLifeCycleEvent(array('resources' => array($resource))));

            $this->snapshot($resource);
            $this->removeUplinks($resource);

            if (isset($this->registeredResources[$this->_rm->getNamespaceRegistry()->expand($resource->getUri())])) {
                $this->setStatus($resource, $this::STATUS_REMOVED);
            }
            $this->evd->dispatch(Events::PostRemove, new ResourceLifeCycleEvent(array('resources' => array($resource))));
        }
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
            $className = TypeMapper::getDefaultResourceClass();
        }

        /** @var BaseResource $resource */
        $resource = new $className($this->nextBNode(), new Graph());
        $resource->addType($type);
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
     * @return bool
     */
    public function isManagementBlackListed($uri)
    {
        return ($this->blackListedResources->contains($uri));
    }

    /**
     * @param BaseResource $resource
     */
    public function setDirty(BaseResource $resource)
    {
        $this->setStatus($resource, self::STATUS_DIRTY);
    }

    /**
     * provides a blank node uri for collections.
     *
     * @return string
     */
    public function nextBNode()
    {
        return '_:bn'.(++$this->bnodeCount);
    }

    /**
     *
     */
    public function isManaged(BaseResource $resource)
    {
        $uri = $this->_rm->getNamespaceRegistry()->expand($resource->getUri());

        return (isset($this->registeredResources[$uri]));
    }

    /**
     * @param Collection $coll
     */
    public function blackListCollection(Collection $coll)
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

    /**
     * @param $resource
     *
     * @return bool
     */
    public function isResource($resource)
    {
        return ($resource instanceof BaseResource);
    }

    /**
     * @param $resource
     *
     * @return BaseResource
     */
    public function replaceResourceInstance(BaseResource $resource)
    {
        /** @var BaseResource $managedInstance */
        $managedInstance = $this->retrieveResource($resource->getUri());

        if (!$managedInstance) {
            return $resource;
        }

        //getting all triples of new resource
        $properties = $resource->properties();
        foreach ($properties as $prop) {
            $values = $resource->getGraph()->all($resource->getUri(), $prop);
            foreach ($values as $value) {
                //var_dump($managedInstance) ;
                $status = $this->tripleStatus($managedInstance, $prop, $value);

                //we have no trace of this triple. We can add it to resource AND snapshot
                if (($status === self::STATUS_TRIPLE_UNKNOWN)) {
                    $managedInstance->add($prop, $value);
                    $this->initialSnapshots->add($resource->getUri(), $prop, $value);
                }
            }
        }

        return $managedInstance;
    }

    /**
     * @param BaseResource $resource
     * @param $status
     */
    private function setStatus(BaseResource $resource, $status)
    {
        $uri = $this->_rm->getNamespaceRegistry()->expand($resource->getUri());

        $this->status[$uri] = $status;
    }

    /**
     * @param BaseResource $resource
     */
    private function getStatus($resource)
    {
        if (is_string($resource)) {
            $resource = $this->retrieveResource($resource);
        }
        $uri = $this->_rm->getNamespaceRegistry()->expand($resource->getUri());
        if (isset($this->status[$uri])) {
            return $this->status[$uri];
        }

        return;
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
                                continue;
                            }
                            if ($this->getStatus($resource) === self::STATUS_REMOVED) {
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
     * returns the status for a triple inside the unit of work.
     *
     * @param \Conjecto\Nemrod\Resource $resource
     * @param $property
     * @param $value
     *
     * @return string
     */
    private function tripleStatus($resource, $property, $value)
    {
        if (is_string($resource)) {
            $resource = $this->retrieveResource($resource);
        }
        $snapshotValues = $this->initialSnapshots->all($resource, $property);
        $resourceValues = $resource->all($resource, $property);

        $valueInSnapShot = false;
        foreach ($snapshotValues as $val) {
            $snapValue =  ($val instanceof Literal) ? $val->getValue() : $val['value'];
            if (($value instanceof Literal) || $snapValue === $value['value']) {
                $valueInSnapShot = true;
            }
        }

        $valueInResource = false;
        foreach ($resourceValues as $val) {
            if ($val['value'] === $value['value']) {
                $valueInResource = true;
            }
        }

        if ($valueInSnapShot) {
            if ($valueInResource) {
                return self::STATUS_TRIPLE_UNCHANGED;
            } else {
                return self::STATUS_TRIPLE_REMOVED;
            }
        } else {
            if ($valueInResource) {
                return self::STATUS_TRIPLE_ADDED;
            } else {
                return self::STATUS_TRIPLE_UNKNOWN;
            }
        }
    }

    /**
     * @param $object
     * @param $objectsList
     *
     * @return bool
     */
    private function containsObject($object, $objectsList)
    {
        foreach ($objectsList as $obj) {
            if ($obj['type'] === $object['type']) {
                if ($obj['type'] === 'uri') {
                    $objValue = ($this->_rm->getNamespaceRegistry()->shorten($obj['value'])) ? $this->_rm->getNamespaceRegistry()->shorten($obj['value']) : $obj['value'];
                    $objectValue = ($this->_rm->getNamespaceRegistry()->shorten($object['value'])) ? $this->_rm->getNamespaceRegistry()->shorten($object['value']) : $object['value'];

                    if ($objValue === $objectValue) {
                        return true;
                    }
                } elseif ($obj['value'] === $object['value']) {
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
    private function getSnapshotForResource(\Traversable $resources, $array = array())
    {
        $snapshot = array();
        foreach ($resources as $resource) {
            $snap = $this->initialSnapshots->getSnapshot($resource);
            if ($snap) {
                $bigSnapshot = $snap->getGraph()->toRdfPhp();

                $snapshot[$resource->getUri()] = $bigSnapshot[$resource->getUri()];

                //getting snapshots also for blank nodes
                if (!empty($bigSnapshot))
                foreach ($bigSnapshot[$resource->getUri()] as $property => $values) {
                    return $bigSnapshot;
                    foreach ($values as $value) {
                        $array[] = $value['value'];
//                        if ((!$this->isManagementBlackListed($value['value'])) && $value['type'] === 'bnode' && isset($bigSnapshot[$value['value']])) {
//                            $snapshot[$value['value']] = $bigSnapshot[$value['value']];
//                        }
                    }
                }
                return $array;
            }
        }

        return $snapshot;
    }

    /**
     * Takes a snapshot for a single triplet.
     *
     * @param $uri
     * @param $prop
     * @param null $value
     */
    public function snapshotForTriple($uri, $prop, $value = null)
    {
        $this->initialSnapshots->add($uri, $prop, $value);
    }

    /**
     * Takes a snapshot for a whole resource.
     *
     * @param $resource
     */
    public function snapshot(BaseResource $resource)
    {
        $this->initialSnapshots->takeSnapshot($resource);
    }

    /**
     * Queries for all resources pointing to the current one, and declares resources.
     *
     * @param $resource
     */
    private function removeUplinks(BaseResource $resource)
    {
        /** @var Graph $result */
        $result = $this->_rm->createQueryBuilder()
            ->construct('?s a ?t; ?p <'.$resource->getUri().'>')
            ->where('?s a ?t; ?p <'.$resource->getUri().'>')
            ->getQuery()
        ->execute();

        $resources = $result->resources();
        /** @var \Conjecto\Nemrod\Resource $re */
        foreach ($resources as $re) {
            $this->registerResource($re);
            foreach ($result->properties($re->getUri()) as $prop) {
                if ($prop !== 'rdf:type') {
                    $re->delete($prop);
                }
            }
        }
    }

    /**
     * @param $uri
     *
     * @return bool
     */
    public function isBNode($uri)
    {
        if (substr($uri, 0, 2) === '_:') {
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
    public function generateURI($options = array())
    {
        $prefix = (isset($options['prefix']) && $options['prefix'] !== '') ? $options['prefix'] : 'og_bd:';

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
        $this->status = new ArrayCollection();

        $this->evd->dispatch(Events::OnClear, new ClearEvent($this->_rm));
    }
}
