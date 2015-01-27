<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 06/01/2015
 * Time: 15:25
 */

namespace Devyn\Component\RAL\Manager;

use Devyn\Component\QueryBuilder\QueryBuilder;
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
    public function __construct($rm)
    {
        $this->_rm = $rm;
    }

    /**
     * @param $string
     * @return $this|\EasyRdf\Sparql\Result
     */
    private function query($string)
    {
        $result = $this->_rm->getClient()->query($string);

        return $result;
    }

    private function updateQuery($string)
    {
        $this->_rm->getClient()->update($string);
    }

    /**
     * @todo should be renamed
     * @param $className
     * @param $uri
     * @return Resource
     * @throws Exception
     */
    public function constructUri($className, $uri)
    {
        $body = "<".$uri. "> a ".(( $className == null ) ? $this->nextVariable() : $className)."; ?p ?q";

        /** @var QueryBuilder $qb */
        $qb = $this->_rm->getQueryBuilder();
        $qb->construct($body)->where($body);

        $result = $qb->getQuery()->execute();

        if ($result instanceof Result) {
            $result = $this->resultToGraph($result);
        }

        if (!$this->isEmpty($result)) {

            $resourceClass = null;
            foreach ($result->all($uri,'rdf:type') as $type) {
                $resourceClass = TypeMapper::get($type->getUri());
                if ($resourceClass != null) {
                    break;
                }
            }

            if (empty($resourceClass)) {
                throw new Exception("No associated class");
            }

            $resource = $this->resultToResource($uri, $result, $resourceClass);

            if ($resource) {
                $this->registerResource($resource);
            }

            return $resource;
        }
        return null;
    }

    /**
     *
     */
    public function constructBNode($owningUri, $property)
    {
        $body = "<".$owningUri."> ".$property." ?bnodeVar. ?bnodeVar ?p ?q.";

        $qb = $this->_rm->getQueryBuilder();
        $qb->construct($body)->where($body);

        $graph = $qb->getQuery()->execute();

        if(!$graph instanceof Graph) {
            $graph = $this->resultToGraph($graph);
        }

        $this->_rm->getUnitOfWork()->setBNodes($owningUri, $property, $graph);

        return $graph->get($owningUri, $property);
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
        //echo htmlspecialchars("DELETE {".$deleteStr."} INSERT {".$insertStr."} WHERE {".$whereStr."}");
        $result = $this->updateQuery("DELETE {".$deleteStr."} INSERT {".$insertStr."} WHERE {".$whereStr."}");

        return $result;
    }

    /**
     * @param Resource $resource
     */
    public function save($uri, $insert)
    {
        //var_dump($insert);
        list($insertArr, )= $this->getTriplesForUri($insert, $uri, false);
        echo htmlspecialchars("INSERT DATA{".implode(".", $insertArr)."}");
        $this->updateQuery("INSERT DATA{".implode(".", $insertArr)."}");
    }

    /**
     *
     */
    public function delete($uri, $graph)
    {
        list($deleteArr, $whereArr)= $this->getTriplesForUri($graph, $uri, true);
        //echo htmlspecialchars("DELETE {".implode(".", $deleteArr)."} WHERE {".implode(".", $whereArr)."}");

        $qb = $this->_rm->getQueryBuilder()->delete(implode(".", $deleteArr))->where(implode(".", $whereArr));
        //$qb->getQuery()->execute();
        $this->updateQuery("DELETE {".implode(".", $deleteArr)."} WHERE {".implode(".", $whereArr)."}");
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
        //$body = "?s ?p ?q";

        //end statments for query (order by, etc)
        $queryFinal = "";

        $criteriaParts = array();
        if (!empty ($criteria)) {
            foreach ($criteria as $property => $value) {
                if (is_array($value)) {
                    if (!empty($value)) {
                        foreach ($value as $val) {
                            $criteriaParts[] = $property. " " . $val;
                        }
                    }
                }
                $criteriaParts[] = $property. " " . $value;
            }
        }


        if (isset($options['orderBy'])) {
            $criteriaParts[] = $options['orderBy']." ?orderingvar";
            $queryFinal .= "?orderingvar";
        }

        $qb = $this->_rm->getQueryBuilder();
        $qb->construct("?s ?p ?q");
        $qb->where("?s ?p ?q");

        foreach ($criteriaParts as $triple) {
            $qb->addConstruct("?s ".$triple);
            $qb->andWhere("?s ".$triple);
        }

        $qb->setOffset(0);
        if ($queryFinal != "") {
            $qb->orderBy($queryFinal);
        }

        $graph = $qb->getQuery()->execute();

        //"CONSTRUCT {".$body."} WHERE {".$body."}"." ".$queryFinal;

        $this->_rm->getLogger()->info($qb->getSparqlQuery());
        //echo htmlspecialchars($query->getSparqlQuery());
        //$graph = $this->query($query->getSparqlQuery());

        if ($graph instanceof Result) {
            $graph = $this->resultToGraph($graph);
        }

        if ($this->isEmpty($graph)){
            return null;
        }

        $graph = $this->resultToGraph($graph);

        $collection = null;

        //extraction of collection is done by unit of work
        if (!empty($criteria['rdf:type'])) {
            if (is_array($criteria['rdf:type'])) {
                $collection = $this->extractResources($graph, $criteria['rdf:type'][0]);
            } else {
                $collection = $this->extractResources($graph, $criteria['rdf:type']);
            }
        } else {
            return null;
        }

        return $collection;
    }


    /**
     * @param $criteria
     * @param bool $bNodesAsVariables
     * @return array
     */
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
                    list($a,$b) = $this->getTriplesForUri($criteria, $uri, $bNodesAsVariables, true);
                    $criteriaParts = array_merge($criteriaParts, $a);
                    $whereParts = array_merge($whereParts, $b);
                }
            }
        }
        return array(implode(".", $criteriaParts),implode(".", $whereParts));
    }

    /**
     * get an array of triples as strings for a given URI
     * @param $array
     * @param $uri
     * @param $bNodesAsVariables
     * @param bool $followBNodes
     * @return array
     */
    private function getTriplesForUri($array, $uri, $bNodesAsVariables, $followBNodes = false)
    {
        $criteriaParts = array();
        $whereParts = array();
        $bNodeVariablesGroupByProperty = array();

        if ($this->_rm->getUnitOfWork()->isBNode($uri)) {
            $oldUri = $uri;
            $uri = $this->getNewBnode($uri);
            if (isset($array[$oldUri])) {
                $array[$uri] = $array[$oldUri];
                unset($array[$oldUri]);
            }
        }

        foreach ($array[$uri] as $property => $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    foreach ($value as $val) {

                        if ($val['type'] == 'literal') {
                            $criteriaParts[] = "<" . $uri . "> <" . $property . "> \"" . $val['value'] . "\"";
                            $whereParts[] = "<" . $uri . "> <" . $property . "> \"" . $val['value'] . "\"";
                        } else if($val['type'] == 'uri') {
                            $criteriaParts[] = "<" . $uri . "> <" . $property . "> <" . $val['value'] . ">";
                            $whereParts[] = "<" . $uri . "> <" . $property . "> <" . $val['value'] . ">";
                        } else if ($val['type'] == 'bnode') {

                            if ($bNodesAsVariables) {
//                                if (!isset ($bNodeVariablesGroupByProperty[$uri][$property]) ) {
//                                    if (!isset($bNodeVariablesGroupByProperty[$uri])) {
//                                        $bNodeVariablesGroupByProperty[$uri] = array();
//                                    }
                                    //$bNodeVariablesGroupByProperty[$uri][$property] = true ;
                                    echo "uuu";
                                    $varBnode = $this->nextVariable();
                                    $varBnodePred = $this->nextVariable();
                                    $varBnodeObj = $this->nextVariable();
                                    $criteriaParts[] = "<" . $uri . "> <" . $property . "> " . $varBnode;
                                    $criteriaParts[] = $varBnode . " " . $varBnodePred . " " . $varBnodeObj;
                                    $whereParts[] = "<" . $uri . "> <" . $property . "> " . $varBnode;
                                //}

                            } else {
                                $newBNode = $this->getNewBnode($val['value']);

                                $criteriaParts[] = "<" . $uri . "> <" . $property . "> " . $newBNode . "";
                                $whereParts[] = "<" . $uri . "> <" . $property . "> " . $newBNode . "";
                                if ($followBNodes) {
                                    list ($a, $b) = $this->getTriplesForUri($array, $val['value'], $bNodesAsVariables, false);
                                    $criteriaParts = array_merge($criteriaParts, $a);
                                    $whereParts = array_merge($whereParts, $b);
                                }
                            }
                        } else if ($val['type'] == 'uri'){
                            $criteriaParts[] = "<" . $uri . "> <" . $property . "> " . $val['value'] . "";
                        }
                    }
                }
            }
        }
        var_dump($whereParts);
        return array($criteriaParts, $whereParts);
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
            $this->_rm->getUnitOfWork()->registerResource($re);
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
     * @param Graph $graph
     * @param string $resourceClass
     * @return Resource
     */
    private function resultToResource($uri = null, Graph $graph, $resourceClass)
    {
        $resource = new $resourceClass($uri, $graph);

        return $resource;
    }

    /**
     * //@todo not used anymore
     * temp function : converting a result to a graph.
     * @param Result $result
     * @return Graph
     */
    private function resultToGraph($result)
    {
        //@todo
        if ($result instanceof Graph) {
            return $result;
        }

        $graph = new Graph(null);

        foreach ($result as $row) {
            $graph->add($row->subject, $row->predicate, $row->object);
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
        if (in_array($bNode, $this->bnodeMap)) {
            return $bNode;
        }
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

    private function isEmpty($result) {
        if ($result instanceof Graph) {
            return $result->isEmpty();
        } else if ($result instanceof Result) {
            $cnt = count($result) ;
            return ( $cnt == 0 );
        }
    }
} 