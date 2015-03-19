<?php
namespace Conjecto\EasyRdfBundle\Model;

use Doctrine\Common\ClassLoader;
use EasyRdf;
use Doctrine\Common\Annotations\FileCacheReader;

class ResourceManager
{
    /**
     * @var FileCacheReader
     */
    protected $annotationReader;

    /**
     * @var EasyRdf\Sparql\Client
     */
    protected $sparqlClient;

    public function __construct($endPointUri = "none")
    {
        $this->sparqlClient = new EasyRdf\Sparql\Client($endPointUri);
    }

    public function getResource($uri)
    {
        $result = $this->sparqlClient->query("CONSTRUCT {".$uri." ?p ?q} WHERE {".$uri." a <http://cobusiness.fr/ontologies/barter.owl.n3#User>; ?p ?q.}");

        $foaf = new EasyRdf\Graph();

        //building graph from results array.
        //@todo: a better way to do this ?
        foreach ($result as $re) {
            $foaf->add($re->s, $re->p, $re->o);
        }

        /** @var EasyRdf\Serialiser\JsonLd $jsonldser */
        $jsonldser = new EasyRdf\Serialiser\JsonLd();

        //ClassLoader

        //@todo place jsonld compacting / framing elsewhere
        return $jsonldser->serialise($foaf, 'jsonld', $options = array('compact' => true, 'context' => '{"@context": {"foaf":"http://xmlns.com/foaf/0.1/","cob":"http://cobusiness.fr/ontologies/barter.owl.n3#"}}' ));
    }

    /**
     *
     */
    public function getMapping()
    {
    }

    /**
     * @return FileCacheReader
     */
    public function getAnnotationReader()
    {
        return $this->annotationReader;
    }

    /**
     * @param FileCacheReader $annotationReader
     */
    public function setAnnotationReader($annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }
}
