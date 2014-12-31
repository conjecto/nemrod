<?php

namespace Devyn\Component\RAL\Manager;

/**
 * Class ResourceRepository
 * @package Devyn\Component\RAL\Manager
 */
class ResourceRepository
{
    /** @var  string $className */
    protected $className;

    /** @var  ResourceManager */
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
        //@todo Continue/change this
        return $this->_rm->getSparqlClient()->query("CONSTRUCT {<".$uri."> a <".$this->className.">; ?p ?q.} WHERE {<".$uri."> a <".$this->className.">; ?p ?q.}");
    }

}