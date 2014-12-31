<?php

namespace Devyn\Component\RAL\Manager;


/**
 * Class RepositoryFactory
 * @package Devyn\Component\RAL\Manager
 */
class RepositoryFactory
{

    /** @var  array $repositories */
    private $repositories;

    /**
     *
     */
    public function __construct()
    {
        echo "pwet";
        $this->repositories = array();
    }

    /**
     * @param $className
     */
    public function getRepository($className)
    {
        // creating and storing repository if not already done
        if (empty($this->repositories[$className])) {
            $this->repositories[$className] = new ResourceRepository($className);
        }
        return $this->repositories[$className];
    }
} 