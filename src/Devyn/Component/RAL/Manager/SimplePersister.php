<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 06/01/2015
 * Time: 15:25
 */

namespace Devyn\Component\RAL\Manager;

use EasyRdf\Exception;
use EasyRdf\Graph;
use EasyRdf\Sparql\Client;
use EasyRdf\Sparql\Result;
use EasyRdf\TypeMapper;

/**
 * Class Persister
 * @package Devyn\Component\RAL\Manager
 */
class SimplePersister implements PersisterInterface
{

    /** @var Client */
    private $sparqlClient;

    /** @var Manager */
    private $_rm;

    /**  */
    public function __construct($rm, $sparqlClientUrl)
    {
        $this->_rm = $rm;
        $this->sparqlClient = new Client($sparqlClientUrl);
    }

    /**
     * @param $string
     * @return $this|\EasyRdf\Sparql\Result
     */
    public function query($string)
    {
        $result = $this->sparqlClient->query($string);

        return $result;
    }

    /**
     * @param $className
     * @param $uri
     * @return Resource
     * @throws Exception
     */
    public function constructUri($className, $uri)
    {
        $result = $this->query("CONSTRUCT {<".$uri."> a ".$className."; ?p ?q.} WHERE {<".$uri."> a ".$className."; ?p ?q.}");

        $resourceClass = TypeMapper::get($className);
        if (empty($resourceClass)) {
            throw new Exception("No associated class");
        }

        $resource = $this->resultToResource($uri, $result, $resourceClass);

        $this->registerResource($resource);

        return $resource;
    }

    /**
     * @todo second param is temporary
     * @param array $criteria
     * @param bool $asArray
     * @return Collection|void
     */
    public function constructCollection(array $criteria, $asArray = true)
    {
        $body = "?s ?p ?q";

        $criteriaParts = array();

        //translating criteria to simple query terms
        if (!empty ($criteria)) {
            foreach ($criteria as $property => $value) {
                if (is_array($value)) {
                    if (!empty($value)) {
                        foreach ($value as $val) {
                            $criteriaParts [] = $property. " " . $val;
                        }
                    }
                }
                $criteriaParts [] = $property. " " . $value;
            }
        }

        $body .= (count ($criteriaParts)) ? "; ".implode(';', $criteriaParts)."." : ".";

        $query  = "CONSTRUCT {".$body."} WHERE {".$body."}";

        $result = $this->query($query);

        $graph = $this->resultToGraph($result);

        $collect = null;

        if ($asArray) {
            if (!empty($criteria['rdf:type']) && is_array($criteria['rdf:type'])){
                $collec = $this->collectionArrayFromGraph($graph, $criteria['rdf:type'][0]);
            } else if (!empty($criteria['rdf:type'])){
                $collec = $this->collectionArrayFromGraph($graph, $criteria['rdf:type']);
            }
        }

        return $collec;
    }

    /**
     * Builds and return Resource of the corresponding type with provided uri and result
     * @param null $uri
     * @param Result $result
     * @param string $resourceClass
     * @return Resource
     */
    private function resultToResource($uri = null, Result $result, $resourceClass)
    {
        $resource = new $resourceClass($uri, $this->resultToGraph($result));

        return $resource;
    }

    /**
     * temp function : converting a result to a graph.
     * @param Result $result
     * @return Graph
     */
    private function resultToGraph(Result $result)
    {
        $graph = new Graph(null);

        foreach ($result as $row) {
            $graph->add($row->s, $row->p, $row->o);
        }

        return $graph;
    }

    private function collectionArrayFromGraph(Graph $graph, $rdfType)
    {
        $res = $graph->allOfType($rdfType);
        return $res;
    }

    /**
     * calls the resource registration process of resource manager's unit of work
     * @param $resource
     */
    private function registerResource($resource)
    {
        $this->_rm->getUnitOfWork()->registerResource($resource);
    }

} 