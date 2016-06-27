<?php
namespace Tests\Conjecto\Nemrod\Serializer;

use Conjecto\Nemrod\ElasticSearch\JsonLdFrameLoader;
use Conjecto\Nemrod\Framing\Metadata\Driver\AnnotationDriver;
use Conjecto\Nemrod\Framing\Provider\SimpleGraphProvider;
use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use Conjecto\Nemrod\Serializer\JMSResourceHandler;
use Conjecto\Nemrod\Serializer\ResourceHandler;
use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Conjecto\Nemrod\EndpointTestCase;

class JMSResourceHandlerTest extends EndpointTestCase
{
    /**
     * setUp
     */
    public function setUp() {
        parent::setUp();
        $this->jsonLdFrameLoader->addPath(__DIR__.'/Fixtures', 'fixtures');
    }

    /**
     * testResourceHandler
     */
    public function testResourceHandler() {
        $provider = new SimpleGraphProvider();
        $jsonLdSerializer = new JsonLdSerializer($this->nsRegistry, $this->jsonLdFrameLoader, $provider, $this->jsonLdMetadataFactory, $this->typeMapperRegistry);

        $person = self::$manager->getRepository('foaf:Person')->create();
        $friend = self::$manager->getRepository('foaf:Person')->create();
        $person->set('foaf:name', 'person1');
        $friend->set('foaf:name', 'person2');
        $person->set('foaf:knows', $friend);

        $data = array(
            'foo' => 'bar',
            'person' => $person
        );

        $builder = SerializerBuilder::create();
        $builder
            ->configureHandlers(function(HandlerRegistry $registry) use ($jsonLdSerializer) {
                $registry->registerSubscribingHandler(new JMSResourceHandler($jsonLdSerializer));
            })
        ;

        $serializer = $builder->build();
        $jsonContent = $serializer->serialize($data, 'json');
        $this->assertJson($jsonContent);
    }
}