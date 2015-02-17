<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 27/01/2015
 * Time: 16:36
 */

namespace Conjecto\EasyRdfBundle\RdfResource;

use Devyn\Component\RAL\Resource\Resource as BaseResource;
use Devyn\Component\RAL\Annotation\Rdf; //Resource;

/**
 * Class ExampleResource
 * @package Conjecto\EasyRdfBundle\RdfResource
 * @Rdf\Resource(types={"foaf:Organization"})
 */
class Organization
{
    /**
     * @Rdf\Property("foaf:name", cascade={"persist"})
     */
    protected $name;

    /**
     * @param null $uri
     * @param null $graph
     */
    public function __construct($uri = null, $graph = null)
    {
        parent::__construct($uri, $graph);
    }
} 