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
 * Interface AbstractPersister.
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
     *
     * @return mixed
     */
    public function constructSet(array $criteria, array $options);

    public function constructBNode($owningUri, $property);
}
