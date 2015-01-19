<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 06/01/2015
 * Time: 15:27
 */

namespace Devyn\Component\RAL\Manager;

/**
 * Interface AbstractPersister
 * @package Devyn\Component\RAL\Manager
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