<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 04/02/2015
 * Time: 15:52
 */

namespace Devyn\Component\RAL\Manager\Event;


use Devyn\Component\RAL\Resource\Resource;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base event for resource manager event dispatcher mechanism
 * Class ResourceLifeCycleEvent
 * @package Devyn\Component\RAL\Manager\Events
 */
class ResourceLifeCycleEvent extends Event
{
    /** @var Resource  */
    protected $resources;

    public function __construct(array $resources)
    {
        $this->resources = $resources;
    }

    public function getResource()
    {
        return $this->resources;
    }
} 