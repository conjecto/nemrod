<?php

namespace Conjecto\RAL\ResourceManager\Manager;

use Conjecto\RAL\ResourceManager\Mapping\ClassMetadata;
use Conjecto\RAL\ResourceManager\Mapping\Driver\MappingDriver;

class ClassMetadataFactory
{
    /**
     * @var
     */
    private $loadedMetadata;

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
            $classMD = new ClassMetadata($className);
            $this->driver->loadMetadataForClass($className, $classMD);
            $this->loadedMetadata[$className] = $classMD;
        }

        return $this->loadedMetadata[$className];
    }

    /**
     * loadedMetadata
     * @return array
     */
    public function getAllMetadata()
    {
        return $this->loadedMetadata;
    }

    /**
     * @todo remove
     * @return mixed
     */
    public function getDriver()
    {
        return $this->annotationdriver;
    }

    /**
     * @todo remove
     * @param mixed $annotationdriver
     */
    public function setDriver($annotationdriver)
    {
        $this->annotationdriver = $annotationdriver;
    }
}