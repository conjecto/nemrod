<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\QueryBuilder\Internal\Hydratation;

use Conjecto\Nemrod\Resource;
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

        $collection = new Collection($collectionUri, $this->graph);

        //building collection
        foreach ($resources as $resource) {
            if (!$resource instanceof Resource) {
                $resource = new Resource($resource->getUri(), $resource->getGraph());
                $resource->setRm($this->rm);
            }
            $this->rm->getUnitOfWork()->registerResource($resource);
            $this->rm->getUnitOfWork()->replaceResourceInstance($resource);
            $collection->append($resource);
        }
        $this->rm->getUnitOfWork()->blackListCollection($collection);

        return $collection;
    }
}
