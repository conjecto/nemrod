<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Conjecto\Nemrod\Framing\Serializer;

use Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader;
use Conjecto\Nemrod\Framing\Provider\SimpleGraphProvider;
use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\Resource;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use EasyRdf\Graph;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Tests\Conjecto\Nemrod\EndpointTest;

/**
 * Class JsonLdSerializerTest.
 */
class JsonLdSerializerTest extends EndpointTest
{

    public function setUp() {

    }

    /**
     * @throws \Twig_Error_Loader
     */
    /*public function testSerialize()
    {
        $loader = new JsonLdFrameLoader();
        $loader->addPath(__DIR__.'/Fixtures', 'fixtures');

        $registry = new RdfNamespaceRegistry();
        $foaf = new Graph('http://njh.me/foaf.rdf');
        $foaf->parseFile(__DIR__.'/Fixtures/foaf.rdf');

        $graphProvider = new SimpleGraphProvider();
        $metadataFactory = $this->getMockBuilder('Metadata\\MetadataFactory')->disableOriginalConstructor()->getMock();
        $typeMapperRegistry = $this->getMockBuilder(TypeMapperRegistry::class)->getMock();
        $serializer = new JsonLdSerializer($registry, $loader, $graphProvider, $metadataFactory, $typeMapperRegistry);

        $resource = $foaf->primaryTopic();
        $serialized = $serializer->serialize($resource, '@fixtures/frame.jsonld');
        $decoded = json_decode($serialized, true);

        $this->assertEquals($resource->get('foaf:name'), $decoded['@graph'][0]['foaf:name']);
    }*/

    public function testRemoteSerialize() {
        $resource = self::$manager->getRepository('foaf:Person')->create();
        self::$manager->persist($resource);
        $resource->set("rdfs:label", "popo");
        self::$manager->flush();

        $resource->delete("rdfs:label");
        self::$manager->flush();


//        $resource->set("rdfs:label", "popo");
//        self::$manager->persist($resource);
//        self::$manager->flush();
//        print $resource->getUri();

    }
}
