<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 04/02/2015
 * Time: 15:52.
 */

namespace Conjecto\RAL\ResourceManager\Manager\Event;

use Conjecto\RAL\ResourceManager\Resource\Resource;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base event for resource manager event dispatcher mechanism
 * Class ResourceLifeCycleEvent.
 */
class ResourceLifeCycleEvent extends Event
{
    /** @var Resource  */
    protected $resources;

    protected $uris;

    public function __construct(array $resources)
    {
        if (isset($resources['resources'])) {
            $this->resources = $resources['resources'];
        }
        if (isset($resources['uris'])) {
            $this->uris = $resources['uris'];
        }
    }

    public function getResource()
    {
        return $this->resources;
    }

    /**
     * @return Resource
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param Resource $resources
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * @return mixed
     */
    public function getUris()
    {
        return $this->uris;
    }

    /**
     * @param mixed $uris
     */
    public function setUris($uris)
    {
        $this->uris = $uris;
    }
}
