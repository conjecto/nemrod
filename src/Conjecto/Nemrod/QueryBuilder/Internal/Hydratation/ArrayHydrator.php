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

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ArrayHydrator extends AbstractHydrator
{
    public function hydrateResources($options = array())
    {
        if (!array_key_exists('rdf:type', $options)) {
            throw new MissingOptionsException('Missing key rdf:type in options');
        }

        $resources = $this->graph->allOfType($options['rdf:type']);

        $array = array();

        //building array
        foreach ($resources as $resource) {
            $array[$resource->getUri()] = $resource;
        }

        foreach ($resources as $resource) {
            $this->rm->getUnitOfWork()->registerResource($resource);
        }

        return $array;
    }
}
