<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 21/01/2015
 * Time: 10:39
 */

namespace Devyn\Component\RAL\Manager;


use Devyn\Component\RAL\Resource\Resource as BaseResource;
use EasyRdf\Graph;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class SnapshotContainer
 * @package Devyn\Component\RAL\Manager
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
     * @param BaseResource $resource
     * @return BaseResource
     */
    public function takeSnapshot(BaseResource $resource)
    {
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
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
                        } else if ($value['type'] == 'literal') {
                            $this->addLiteral($resource2, $property, $value['value']);
                        } else {
                            //@todo check for addType
                        }
                    }
                }
            }
        }
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);
        echo "Snap in ".$totaltime." seconds";
        return $res;
    }

    /**
     * @todo check for better way
     * @param BaseResource $resource
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

            //unset($index[$resource->getUri()]);
        }

        return true;
    }

    /**
     * @param BaseResource $resource
     * @return \EasyRdf\Resource
     */
    public function getSnapshot(BaseResource $resource)
    {
        //check if $resource is known by getting type.
        //if uri is not known or result is null, resource is not known
        //@todo check if $resource is known
        try {
            $typ = $this->get($resource->getUri(), 'rdf:type');

            if (!$typ) return null;
        } catch (Exception $e) {
            return null;
        }

        $res = $this->resource($resource->getUri());

        return $res;
    }
}