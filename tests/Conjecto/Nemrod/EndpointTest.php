<?php
namespace Tests\Conjecto\Nemrod;

use Conjecto\Nemrod\Manager;
use Conjecto\Nemrod\ResourceManager\Mapping\Driver\AnnotationDriver;
use Conjecto\Nemrod\ResourceManager\Registry\RdfNamespaceRegistry;
use Conjecto\Nemrod\ResourceManager\RepositoryFactory;
use Conjecto\Nemrod\ResourceManager\SimplePersister;
use Doctrine\Common\Annotations\AnnotationReader;
use EasyRdf\Sparql\Client;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EndpointTest extends TestCase
{
    /** @var Manager */
    protected static $manager;

    public static function setUpBeforeClass()
    {
        $repositoryFactory = new RepositoryFactory();
        $endpoint = $GLOBALS['SPARQL_ENDPOINT'];

        // client
        $client = new Client($endpoint, null);

        // metadata factory
        //$driver = new AnnotationDriver(new AnnotationReader(), []);
        //$metadataFactory = new MetadataFactory($driver);
        // dispatcher
        //$dispatcher = new EventDispatcher();
        // namespace registry
        //$registry = new RdfNamespaceRegistry();

        $manager = new Manager($repositoryFactory, null);
        $manager->setClient($client);
        //$manager->setMetadataFactory($metadataFactory);
        //$manager->setEventDispatcher($dispatcher);
        //$manager->setNamespaceRegistry($registry);

        self::$manager = $manager;
    }

    public static function tearDownAfterClass()
    {
        self::$manager = null;
    }
}