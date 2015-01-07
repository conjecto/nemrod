<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 06/01/2015
 * Time: 15:27
 */

namespace Devyn\Component\RAL\Manager;


use Devyn\Bridge\EasyRdf\Resource\Resource;
use EasyRdf\Sparql\Result;

/**
 * Interface AbstractPersister
 * @package Devyn\Component\RAL\Manager
 */
interface PersisterInterface
{

    /**
     * @return Result
     */
    public function query($queryString);

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

}