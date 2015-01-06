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

    /**  */
    public function __construct($sparqlClientUrl)
    {
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
        $result = $this->query("CONSTRUCT {<".$uri."> a <".$className.">; ?p ?q.} WHERE {<".$uri."> a <".$className.">; ?p ?q.}");

        $resourceClass = TypeMapper::get($className);
        if (empty($resourceClass)) {
            throw new Exception("No associated class");
        }

        return $this->resultToResource($uri, $result, $resourceClass);
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

} 