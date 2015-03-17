<?php
namespace Conjecto\RAL\Framing\Tests\Loader;


use Conjecto\RAL\Framing\Loader\JsonLdFrameLoader;
use Conjecto\RAL\Framing\Serializer\JsonLdSerializer;
use Conjecto\RAL\ResourceManager\Registry\RdfNamespaceRegistry;
use EasyRdf\Graph;

class JsonLdSerializerTest extends \PHPUnit_Framework_TestCase {
    public function testSerialize() {
        $loader = new JsonLdFrameLoader();
        $loader->addPath(__DIR__.'/Fixtures', 'namespace');
        $registry = new RdfNamespaceRegistry();

        $foaf = new Graph('http://njh.me/foaf.rdf');
        $foaf->parseFile(__DIR__.'/Fixtures/foaf.rdf');
        $resource = $foaf->primaryTopic();
        $serializer = new JsonLdSerializer($registry, $loader);

        $serialized = $serializer->serialize($resource, '@namespace/frame.jsonld');
        $decoded = json_decode($serialized, true);

        $this->assertEquals($resource->get('foaf:name'), $decoded['@graph'][0]['foaf:name']);
    }
}
