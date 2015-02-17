<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 02/12/2014
 * Time: 10:20
 */
namespace Conjecto\EasyRdfBundle\RdfResource;


use Devyn\Component\RAL\Resource\Resource as BaseResource;
use Devyn\Component\RAL\Annotation\Rdf; //Resource;

/**
 * Class ExampleResource
 * @package Conjecto\EasyRdfBundle\RdfResource
 * @Rdf\Resource(types={"foaf:Person"}, uriPattern = "ogbd:person:")
 */
class Person extends BaseResource
{
    /**
     * @Rdf\Property("foaf:name", cascade={"persist"})
     */
    protected $name;

    /**
     * @Rdf\Property("vcard:hasAddress", cascade={"persist"})
     */
    protected $address;

    /**
     * @Rdf\Property("ogbd:bestFriend")
     */
    protected $bestFriend;

    /**
     * @param null $uri
     * @param null $graph
     */
    public function __construct($uri = null, $graph = null)
    {
        parent::__construct($uri, $graph);
    }

}