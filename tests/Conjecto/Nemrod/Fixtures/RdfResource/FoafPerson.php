<?php

namespace Tests\Conjecto\Nemrod\Fixtures\RdfResource;


use Conjecto\Nemrod\Resource as BaseResource;
use Conjecto\Nemrod\ResourceManager\Annotation\Resource;
use Conjecto\Nemrod\ResourceManager\Annotation\Property;
use Conjecto\Nemrod\Framing\Annotation as Serializer;
use JMS\Serializer\Annotation\AccessType;

/**
 * Class FoafPerson
 *
 * Resource(types={"foaf:Person"}, uriPattern = "http://www.foo.com/Person/")
 * Serializer\JsonLd(frame="@Devyn/RdfResource/folder.jsonld")
 * Serializer\SubClassOf(parentClasses={"ogbd:ObjetCourrier", "devyn:Tag"})
 * @AccessType("public_method")
 */
class FoafPerson extends BaseResource
{

}