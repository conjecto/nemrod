<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 26/01/2015
 * Time: 17:12
 */

namespace Devyn\Component\RAL\Manager;



use Devyn\Component\RAL\Mapping\ClassMetadata;
use Devyn\Component\RAL\Mapping\Driver\MappingDriver;

class ClassMetadataFactory
{
    /**
     * @var
     */
    private $loadedMetadatas;

    /**
     * @var MappingDriver
     */
    private $driver;

    /**
     *
     */
    public function getMetadataFor($className)
    {
        if (empty($this->metadatas[$className])) {
            $classMD = new ClassMetadata();
            $this->driver->loadMetadataForClass($className, $classMD);
            $this->loadedMetadatas[$className] = $classMD;
        }

        return $this->loadedMetadatas[$className];
    }

    public function getAllMetadata()
    {

    }

    /**
     * @return mixed
     */
    public function getDriver()
    {
        return $this->annotationdriver;
    }

    /**
     * @param mixed $annotationdriver
     */
    public function setDriver($annotationdriver)
    {
        $this->annotationdriver = $annotationdriver;
    }
}