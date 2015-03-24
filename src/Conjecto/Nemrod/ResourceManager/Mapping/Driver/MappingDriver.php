<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
