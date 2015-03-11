<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 26/01/2015
 * Time: 14:18
 */

namespace Conjecto\RAL\QueryBuilder\Internal\Hydratation;

use EasyRdf\Collection;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class CollectionHydrator extends AbstractHydrator
{
    public function hydrateResources($options = array())
    {
        if (!array_key_exists('rdf:type', $options)) {
            throw new MissingOptionsException('Missing key rdf:type in options');
        }

        $resources = $this->graph->allOfType($options['rdf:type']);

        $collectionUri = uniqid('collection:');
        $this->rm->getUnitOfWork()->managementBlackList($collectionUri);
        $collection = new Collection($collectionUri, $this->graph);

        //building collection
        foreach ($resources as $resource) {
            $collection->append($resource);
        }

        foreach ($resources as $resource) {
            $this->rm->getUnitOfWork()->registerResource($resource);
        }

        return $collection;
    }
} 