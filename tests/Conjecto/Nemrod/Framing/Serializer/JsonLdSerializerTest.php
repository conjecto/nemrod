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
use Conjecto\Nemrod\Framing\Provider\ConstructedGraphProvider;
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
    private $jsonLdFrameLoader;
    private $typeMapperRegistry;

    public function setUp() {
        $this->jsonLdFrameLoader = new JsonLdFrameLoader();
        $this->jsonLdFrameLoader->addPath(__DIR__.'/Fixtures', 'fixtures');
        $this->typeMapperRegistry = new TypeMapperRegistry();
    }

    public function testRemoteSerialize()
    {
        $resource = self::$manager->getRepository('foaf:Person')->find('nemrod:576d38d0486a9');
        if(!$resource) {
            $resource =  self::$manager->getRepository('foaf:Person')->create('nemrod:576d38d0486a9');
            self::$manager->persist($resource);
            self::$manager->flush();
        }

        $resource->set("foaf:name", "newName");


        $provider = new ConstructedGraphProvider();
        $provider->setRm(self::$manager);

        $nsRegistry = self::$manager->getNamespaceRegistry();
        $metadataFactory = self::$manager->getMetadataFactory();

        $serializer = new JsonLdSerializer($nsRegistry, $this->jsonLdFrameLoader, $provider, $metadataFactory, $this->typeMapperRegistry);
        $jsonLd = $serializer->serialize($resource, "@fixtures/frame.jsonld");
        $decoded = json_decode($jsonLd, true);

        $this->assertEquals($resource->get('foaf:name'), $decoded['@graph'][0]['foaf:name']);
    }
}
