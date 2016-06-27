<?php


namespace Tests\Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\CompilerPass;

use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\CompilerPass\JMSSerializerResourceHandlerPass;
use Conjecto\Nemrod\Resource;
use Conjecto\Nemrod\ResourceManager\Registry\TypeMapperRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use EasyRdf\Graph;
use JMS\Serializer\GraphNavigator;
use JMS\SerializerBundle\JMSSerializerBundle;
use JMS\SerializerBundle\Tests\DependencyInjection\JMSSerializerExtensionTest;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tests\Conjecto\Nemrod\Fixtures\RdfResource\FoafPerson;


class JMSSerializerResourceHandlerPassTest extends TestCase
{
    public function testCompilerPass() {
        $kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernel
            ->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue(array()))
        ;

        $bundle = new JMSSerializerBundle($kernel);
        $extension = $bundle->getContainerExtension();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir().'/serializer');
        $container->setParameter('kernel.bundles', array());
        $container->set('annotation_reader', new AnnotationReader());
        $container->set('translator', $this->createMock('Symfony\\Component\\Translation\\TranslatorInterface'));
        $container->set('debug.stopwatch', $this->createMock('Symfony\\Component\\Stopwatch\\Stopwatch'));
        $container->registerExtension($extension);
        $extension->load(array(array()), $container);
        $bundle->build($container);

        $def = new Definition(TypeMapperRegistry::class);
        $def->addMethodCall('set', array('foaf:Person', FoafPerson::class));
        $container->setDefinition('nemrod.type_mapper', $def);

        $container->addCompilerPass(new JMSSerializerResourceHandlerPass(), PassConfig::TYPE_REMOVE);
        $container->compile();

        // call type mapper to
        $container->get('nemrod.type_mapper');

        /** @var Serializer $serializer */
        $serializer = $container->get('serializer');
        $graph = new Graph();
        $resource = $graph->resource('urn:test', 'foaf:Person');

        $json = $serializer->serialize($resource, 'json');
        $this->assertJson($json);
    }

}
