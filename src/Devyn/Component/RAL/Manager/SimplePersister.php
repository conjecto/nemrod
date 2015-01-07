<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 06/01/2015
 * Time: 15:25
 */

namespace Devyn\Component\RAL\Manager;

use EasyRdf\Collection;
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

    /** @var  a count for setting uri on collection */
    private $collectionUriCount;

    /**  */
    public function __construct($rm, $sparqlClientUrl)
    {
        $this->_rm = $rm;
        $this->sparqlClient = new Client($sparqlClientUrl);
        $this->collectionUriCount = 0 ;
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
     * @param array $options
     * @throws Exception
     * @internal param bool $asArray
     * @return Collection|void
     */
    public function constructCollection(array $criteria, array $options)
    {
        $body = "?s ?p ?q";

        $criteriaParts = array();

        //end statments for query (order by, etc)
        $queryFinal = "";

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

        if (isset($options['orderBy'])) {
            //var_dump($options['orderBy']);
            $criteriaParts []= $options['orderBy']." ?orderingvar";
            $queryFinal .= "ORDER BY ?orderingvar OFFSET 0";
        }

        $body .= (count ($criteriaParts)) ? "; ".implode(';', $criteriaParts)."." : ".";

        $query  = "CONSTRUCT {".$body."} WHERE {".$body."}"." ".$queryFinal;

        $result = $this->query($query);

        $graph = $this->resultToGraph($result);

        $collection = null;

        //echo $query;

        //extraction of collection is done by unit of work
        if (!empty($criteria['rdf:type'])) {
            if (is_array($criteria['rdf:type'])) {
                $collection = $this->extractResources($graph, $criteria['rdf:type'][0]);
            } else {
                $collection = $this->extractResources($graph, $criteria['rdf:type']);
            }
        } else {
            throw new Exception("findBy: a type must be set");
        }

        return $collection;
    }

    /**
     * @param $graph
     * @param $className
     * @return Collection
     */
    private function extractResources($graph, $className)
    {
        $res = $graph->allOfType($className);
        $collUri = $this->nextCollectionUri();
        $this->_rm->getUnitOfWork()->managementBlackList($collUri);
        $coll = new Collection($collUri, $graph);

        //building collection
        foreach ($res as $re) {
            $coll->append($re);
        }

        $this->blackListCollection ($coll);

        foreach ($res as $re) {
            //registering entity if needed
            if (null == $this->_rm->getUnitOfWork()->retrieveResource($className, $re->getUri())) {
                $this->_rm->getUnitOfWork()->registerResource($re);
            }
        }

        return $coll;
    }

    /**
     * @param Collection $coll
     */
    private function blackListCollection(Collection $coll)
    {
        //going to first element.
        $coll->rewind();
        $ptr = $coll ;
        $head = $ptr->get('rdf:first');
        $next = $ptr->get('rdf:rest');

        //putting all structure collection on a blacklist
        while ($head) {echo $next->getUri() ;
            $this->_rm->getUnitOfWork()->managementBlackList($next->getUri());
            $head = $next->get('rdf:first');
            $next = $next->get('rdf:rest');
        }

        //and resetting pointer of collection
        $coll->rewind();
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

    /**
     * @param Graph $graph
     * @param $rdfType
     * @return array
     */
    private function collectionArrayFromGraph(Graph $graph, $rdfType)
    {
        $res = $graph->allOfType($rdfType);
        return $res;
    }

    /**
     *
     * @param Graph $graph
     * @param $rdfType
     * @return Collection
     */
    private function collectionFromGraph(Graph $graph, $rdfType)
    {
        $res = $graph->allOfType($rdfType);
        $coll = new Collection($this->nextCollectionUri(), $graph);
        foreach ($res as $re) {
            $coll->append($re);
        }

        return $coll;
    }

    /**
     * calls the resource registration process of resource manager's unit of work
     * @param $resource
     */
    private function registerResource($resource)
    {
        $this->_rm->getUnitOfWork()->registerResource($resource);
    }

    /**
     * provides a blank node uri for collections
     * @return string
     */
    private function nextCollectionUri()
    {
        return "_:internalcollection".(++$this->collectionUriCount);
    }

} 