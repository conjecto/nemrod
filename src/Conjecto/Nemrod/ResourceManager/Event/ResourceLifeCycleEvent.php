<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\ResourceManager\Event;

use Conjecto\Nemrod\Resource;
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

    protected $rm;

    public function __construct(array $resources)
    {
        if (isset($resources['resources'])) {
            $this->resources = $resources['resources'];
        }

        if (isset($resources['uris'])) {
            $this->uris = $resources['uris'];
        }

        if (isset($resources['rm'])) {
            $this->rm = $resources['rm'];
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

    /**
     * @return mixed
     */
    public function getRm()
    {
        return $this->rm;
    }

    /**
     * @param mixed $rm
     */
    public function setRm($rm)
    {
        $this->rm = $rm;
    }
}
