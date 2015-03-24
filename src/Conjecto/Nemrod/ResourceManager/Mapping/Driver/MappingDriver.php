<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 26/01/2015
 * Time: 18:15.
 */

namespace Conjecto\Nemrod\ResourceManager\Mapping\Driver;

/**
 * @todo remove
 * Interface MappingDriver
 */
interface MappingDriver
{
    /**
     * @param $className
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
     *
     * @return mixed
     */
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata);
}
