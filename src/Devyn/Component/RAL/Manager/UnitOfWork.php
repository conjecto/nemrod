<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/01/2015
 * Time: 16:40
 */

namespace Devyn\Component\RAL\Manager;


use Doctrine\Common\Collections\ArrayCollection;

class UnitOfWork {

    /**
     * unchanged version of managed resources
     * @var  ArrayCollection $unchangedResources
     */
    public $unchangedResources;

    public function __construct()
    {

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
     * @param $resource
     */
    public function registerResource($resource)
    {

    }

}