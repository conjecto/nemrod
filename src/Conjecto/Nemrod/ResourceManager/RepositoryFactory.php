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
