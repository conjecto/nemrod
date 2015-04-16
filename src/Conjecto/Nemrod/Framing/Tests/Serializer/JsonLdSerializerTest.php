<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Framing\Tests\Loader;

use Conjecto\Nemrod\Framing\Loader\JsonLdFrameLoader;
use Conjecto\Nemrod\Framing\Provider\SimpleGraphProvider;
use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use EasyRdf\Graph;
use Metadata\MetadataFactory;

/**
 * Class JsonLdSerializerTest.
 */
class JsonLdSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws \Twig_Error_Loader
     */
    public function testSerialize()
    {
        $loader = new JsonLdFrameLoader();
        $loader->addPath(__DIR__.'/Fixtures', 'namespace');
        $registry = new RdfNamespaceRegistry();

        $foaf = new Graph('http://njh.me/foaf.rdf');
        $foaf->parseFile(__DIR__.'/Fixtures/foaf.rdf');

        $graphProvider = new SimpleGraphProvider();

        $metadataFactory = $this->getMockBuilder('Metadata\\MetadataFactory')->disableOriginalConstructor()->getMock();// new MetadataFactory();

        $resource = $foaf->primaryTopic();
        $serializer = new JsonLdSerializer($registry, $loader, $graphProvider, $metadataFactory);

        $serialized = $serializer->serialize($resource, '@namespace/frame.jsonld');
        $decoded = json_decode($serialized, true);

        $this->assertEquals($resource->get('foaf:name'), $decoded['@graph'][0]['foaf:name']);
    }
}
