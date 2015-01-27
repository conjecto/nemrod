<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 26/01/2015
 * Time: 18:15
 */

namespace Devyn\Component\RAL\Mapping\Driver;

/**
 * @todo remove
 * Interface MappingDriver
 * @package Devyn\Component\RAL\Mapping\Driver
 */
interface MappingDriver {

    /**
     * @param $className
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
     * @return mixed
     */
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata);
} 