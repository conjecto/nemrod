<?php

namespace Devyn\Component\RAL\Manager;

/**
 * Class ResourceRepository
 * @package Devyn\Component\RAL\Manager
 */
class ResourceRepository
{
    protected $className;

    /**
     *
     */
    public function __construct($className)
    {
        $this->$className = $className;
    }

}