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
    private $collectionUriCount = 0;

    /** @var  int $variableCount */
    private $variableCount = 0;

    /** @var  int $variableCount */
    private $bnodeCount = 0;

    private $bnodeMap = array();

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
     * @param $uri
     * @param array $delete
     * @param array $insert
     * @param array $where
     */
    public function update($uri, $delete, $insert, $where)
    {

        list($deleteStr, $whereStr) = $this->phpRdfToSparqlBody($delete, true);
        list($insertStr) = $this->phpRdfToSparqlBody($insert);

        //$whereStr = "";
        echo htmlspecialchars("DELETE {".$deleteStr."} INSERT {".$insertStr."} WHERE {".$whereStr."}");
        //$result = $this->sparqlClient->update("DELETE {".$deleteStr."} INSERT {".$insertStr."} WHERE {".$whereStr."}");

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

        //end statments for query (order by, etc)
        $queryFinal = "";

        $criteriaParts = array();
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

    private function phpRdfToSparqlBody($criteria, $bNodesAsVariables = false)
    {
        //translating criteria to simple query terms

        $criteriaParts = array();
        $whereParts = array();
        if (!empty ($criteria)) {
            foreach ($criteria as $uri => $properties) {
                //depending on the way we want to generate triples for bnodes
                if (!$this->_rm->getUnitOfWork()->isBNode($uri) // not a bnode
                    || $bNodesAsVariables) //or a bnode, but we have to treat it as a variable
                {
                    if ($this->_rm->getUnitOfWork()->isBNode($uri)) {
                        $uri = $this->getNewBnode($uri);
                    }
                    $uri = (isset($this->bnodeMap[$uri])) ? $this->bnodeMap[$uri] : $uri;
                    foreach ($properties as $property => $value) {
                        if (is_array($value)) {
                            if (!empty($value)) {
                                foreach ($value as $val) {
                                    if ($val['type'] == 'literal') {
                                        $criteriaParts[] = "<" . $uri . "> <" . $property . "> \"" . $val['value'] . "\"";
                                    } else if ($val['type'] == 'bnode') {
                                        if ($bNodesAsVariables) {
                                            $varBnode = $this->nextVariable();
                                            $varBnodePred = $this->nextVariable();
                                            $varBnodeObj = $this->nextVariable();
                                            $criteriaParts[] = "<" . $uri . "> <" . $property . "> " . $varBnode;
                                            $criteriaParts[] = $varBnode . " " . $varBnodePred . " " . $varBnodeObj;
                                            $whereParts[] = "<" . $uri . "> <" . $property . "> " . $varBnode;
                                        } else {
                                            $newBNode = $this->getNewBnode($val['value']);

                                            $criteriaParts[] = "<" . $uri . "> <" . $property . "> " . $newBNode . "";
                                        }
                                    } else if ($val['type'] == 'uri'){
                                        $criteriaParts[] = "<" . $uri . "> <" . $property . "> " . $val['value'] . "";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return array(implode(".", $criteriaParts),implode(".", $whereParts));
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
            //$pr = $this->_rm->getUnitOfWork()->retrieveResource($className, $re->getUri());
            //if (null == $pr) {
                $this->_rm->getUnitOfWork()->registerResource($re);
            //} else {//@todo probably done wrong ; should instead switch registered resources' graph to collection graph
            //    $pr->setGraph($graph);
            //}
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
        while ($head) {
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

    /**
     * provides a blank node uri for collections
     * @return string
     */
    private function nextVariable()
    {
        return "?var".(++$this->variableCount);
    }

    /**
     * Get existing new bnode or create new bnode for given bnode
     * @param $bNode
     */
    public function getNewBnode($bNode){
        if (!isset($this->bnodeMap[$bNode])){
            $this->bnodeMap[$bNode] = $this->nextBNode();
        }
        return $this->bnodeMap[$bNode];
    }

    /**
     * provides a blank node uri for collections
     * @return string
     */
    private function nextBNode()
    {
        return "_:bn".(++$this->bnodeCount);
    }
} 