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

use Conjecto\Nemrod\Resource as BaseResource;
use EasyRdf\Graph;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class SnapshotContainer.
 */
class SnapshotContainer extends Graph
{
    /** @var UnitOfWork */
    private $unitOfWork;

    /**
     * @param UnitOfWork $uow
     */
    public function __construct(UnitOfWork $uow)
    {
        parent::__construct();

        $this->unitOfWork = $uow;
    }

    /**
     * Proceeds to a copy of resource provided as argument and stores it.
     *
     * @param BaseResource $resource
     *
     * @return BaseResource
     */
    public function takeSnapshot($resource)
    {
        $res = $this->resource($resource->getUri());

        $graph = $resource->getGraph();

        foreach ($graph->toRdfPhp() as $resource2 => $properties) {
            if ($resource2 != $res->getUri()) {
                continue;
            }
            if (!$this->unitOfWork->isManagementBlackListed($resource2)) {
                foreach ($properties as $property => $values) {
                    foreach ($values as $value) {
                        if ($value['type'] == 'bnode' || $value['type'] == 'uri') {
                            $this->addResource($resource2, $property, $value['value']);
                        } elseif ($value['type'] == 'literal') {
                            $this->addLiteral($resource2, $property, $value['value']);
                        } else {
                            //@todo check for addType
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * @todo check for better way
     *
     * @param BaseResource $resource
     *
     * @return boolean
     */
    public function removeSnapshot(BaseResource $resource)
    {
        $index = $this->toRdfPhp();

        //remove bnodes associated to resource
        if (isset($index[$resource->getUri()])) {
            foreach ($index[$resource->getUri()] as $property => $values) {
                $this->delete($resource, $property);
                foreach ($values as $value) {
                    if ($value ['type'] == 'bnode') {
                        if (isset($index[$value['value']])) {
                            unset($index[$value['value']]);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param BaseResource $resource
     *
     * @return \EasyRdf\Resource
     */
    public function getSnapshot($resource)
    {
        //check if $resource is known by getting type.
        //if uri is not known or result is null, resource is not known
        //@todo check if $resource is known
        try {
            $typ = $this->get($resource->getUri(), 'rdf:type');

            if (!$typ) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        $res = $this->resource($resource->getUri());

        return $res;
    }
}
