<?php

namespace Devyn\Component\RAL\Manager;

/**
 * Class ResourceRepository
 * @package Devyn\Component\RAL\Manager
 */
class ResourceRepository
{
    /** @var  string $className */
    protected $className;

    /**
     * @param $className
     */
    public function __construct($className)
    {
        $this->$className = $className;
    }

    public function find($uri)
    {

    }

}