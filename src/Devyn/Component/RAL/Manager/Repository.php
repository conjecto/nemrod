<?php

namespace Devyn\Component\RAL\Manager;

/**
 * Class ResourceRepository
 * @package Devyn\Component\RAL\Manager
 */
class Repository
{
    /** @var  string $className */
    protected $className;

    /** @var Manager */
    protected $_rm;

    /**
     * @param $className
     * @param $resourceManager
     */
    public function __construct($className, $resourceManager)
    {
        $this->className = $className;
        $this->_rm = $resourceManager;
    }

    /**
     * @param $uri
     * @return \EasyRdf_Resource
     */
    public function find($uri)
    {

        /** @var \EasyRdf_Sparql_Result $result */
        $result = $this->_rm->getSparqlClient()->query("CONSTRUCT {<".$uri."> a <".$this->className.">; ?p ?q.} WHERE {<".$uri."> a <".$this->className.">; ?p ?q.}");

        //storing result to unit of work

        //@todo Continue/change this
        return $this->resultToResource($uri, $result);

    }

    /**
     *
     */
    public function findBy(array $criterias)
    {

    }

    /**
     * Builds and return Resource of the corresponding type with provided uri and result
     * @param null $uri
     * @param \EasyRdf_Sparql_Result $result
     */
    private function resultToResource($uri = null, \EasyRdf_Sparql_Result $result){
        $class = \EasyRdf_TypeMapper::get($this->className);

        var_dump($this->className);

        $resource = new $class();

        $resource->setUri($uri);
        $resource->setGraph($this->resultToGraph($uri, $result));

        return $resource;
    }

    /**
     * temp function
     * @param string|null $uri
     * @param \EasyRdf_Sparql_Result $result
     * @return \EasyRdf_Graph
     */
    private function resultToGraph($uri = null, \EasyRdf_Sparql_Result $result)
    {
        $graph = new \EasyRdf_Graph($uri);

        foreach ($result as $row) {
            $graph->add($row->s, $row->p, $row->o);
        }

        return $graph;
    }

}