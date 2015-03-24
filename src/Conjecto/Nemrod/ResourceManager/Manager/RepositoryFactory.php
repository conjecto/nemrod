<?php

namespace Conjecto\Nemrod\ResourceManager\Manager;

/**
 * Class RepositoryFactory.
 */
class RepositoryFactory
{
    /** @var  array $repositories */
    private $repositories;

    /** @var  string $connectionName */
    private $connectionName;

    /**
     *
     */
    public function __construct($connectionName)
    {
        $this->repositories = array();
        $this->connectionName = $connectionName;
    }

    /**
     * @param $className
     * @param $resourceManager
     *
     * @return ResourceRepository
     */
    public function getRepository($className, $resourceManager)
    {
        // creating and storing repository if not already done
        if (empty($this->repositories[$className])) {
            $this->repositories[$className] = new Repository($className, $resourceManager);
        }

        return $this->repositories[$className];
    }
}
