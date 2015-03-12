<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 06/01/2015
 * Time: 15:27
 */

namespace Conjecto\RAL\ResourceManager\Manager;

/**
 * Interface AbstractPersister
 * @package Conjecto\RAL\ResourceManager\Manager
 */
interface PersisterInterface
{
    /**
     * @return Resource
     */
    public function constructUri($className, $uri);

    /**
     * @param array $criteria
     * @param array $options
     * @return mixed
     */
    public function constructCollection(array $criteria, array $options);

    public function constructBNode($owningUri, $property);

}