<?php
namespace Tests\Conjecto\Nemrod;

use Conjecto\Nemrod\ElasticSearch\JsonLdFrameLoader;
use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\ResourceManager\Mapping\Driver\AnnotationDriver;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use Conjecto\Nemrod\ResourceManager\RepositoryFactory;
use Conjecto\Nemrod\ResourceManager\SimplePersister;
use Doctrine\Common\Annotations\AnnotationReader;
use EasyRdf\Sparql\Client;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Conjecto\Nemrod\Fixtures\RdfResource\FoafPerson;

class EndpointTestCase extends BaseTestCase
{
    /** @var Manager */
    protected static $manager;

    protected $jsonLdFrameLoader;
    protected $typeMapperRegistry;
    protected $nsRegistry;
    protected $jsonLdMetadataFactory;

    public static function setUpBeforeClass()
    {
        $repositoryFactory = new RepositoryFactory();
        $endpoint = $GLOBALS['SPARQL_ENDPOINT'];

        // client
        $client = new Client($endpoint, null);

        // metadata factory
        $driver = new AnnotationDriver(new AnnotationReader(), [__DIR__. '/Fixtures/RdfResource']);
        $metadataFactory = new MetadataFactory($driver);

        // dispatcher
        $dispatcher = new EventDispatcher();
        // namespace registry
        $registry = new RdfNamespaceRegistry();

        $manager = new Manager($repositoryFactory, null);
        $manager->setClient($client);
        $manager->setMetadataFactory($metadataFactory);
        $manager->setEventDispatcher($dispatcher);
        $manager->setNamespaceRegistry($registry);

        self::$manager = $manager;
    }

    public static function tearDownAfterClass()
    {
        self::$manager = null;
    }

    public function setUp() {
        $this->jsonLdFrameLoader = new JsonLdFrameLoader();
        $this->typeMapperRegistry = new TypeMapperRegistry();
        $this->nsRegistry = self::$manager->getNamespaceRegistry();

        // json metadata factory
        $driver = new \Conjecto\Nemrod\Framing\Metadata\Driver\AnnotationDriver(new AnnotationReader(), []);
        $this->jsonLdMetadataFactory = new MetadataFactory($driver);
    }

}