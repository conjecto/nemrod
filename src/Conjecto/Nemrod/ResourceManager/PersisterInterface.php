<?php

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
