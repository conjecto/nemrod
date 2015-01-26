<?php
namespace Devyn\Bundle\RALBundle\Tests\DependencyInjection;

use Devyn\Bundle\RALBundle\DependencyInjection\RALExtension;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class RALExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerBuilder */
    private $container;

    /** @var  Extension */
    private $extension;

    private $minConfig = array(/*'kernel.bundles'=>array()*/);

    protected function setUp()
    {
        //have to manually register annotation
        AnnotationRegistry::registerFile('../../../../Component/RAL/Annotation/Resource.php');
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', array('Devyn\Bundle\RALBundle\Tests\Fixtures\TestBundle\FixtureTestBundle'));
        $this->extension = new RALExtension();
        $this->container->registerExtension($this->extension);

        $this->load(array( array('endpoints' => array('foopoint' => 'http://bar.org/sparql'), "default_endpoint" => 'foopoint', 'namespaces' => array('foo' => 'http://www.example.org/foo#'))));

    }

    protected function load($config)
    {
        $this->extension->load($config, $this->container);
    }

    public function testExtensionRegistersNamespaces()
    {
        $service = $this->getServiceDefinition('ral.namespace_registry');
        $calls = $service->getMethodCalls();

        $this->assertEquals(array("set", array('foo','http://www.example.org/foo#')), $calls[0]);
    }

    public function testExtensionRegisterClient()
    {
        $service = $this->getServiceDefinition('ral.namespace_registry');
        $calls = $service->getMethodCalls();

        $this->assertTrue($this->containerHasDefinition('ral.sparql.connection.foopoint'));
        $service = $this->getServiceDefinition('ral.sparql.connection.foopoint');
        $service->getMethodCalls();
    }

    public function testExtensionRegisterManager()
    {
        $this->assertTrue($this->containerHasAlias('rm'));
        $this->assertTrue($this->containerHasDefinition('ral.resource_manager.foopoint'));
    }

    public function testExtensionRegisterResourceMapping()
    {
        $service = $this->getServiceDefinition('ral.type_mapper');
        $calls = $service->getMethodCalls();

        $this->assertEquals(array("set",array('foo:Class','Devyn\Bundle\RALBundle\Tests\Fixtures\TestBundle\RdfResource\TestResource')),$calls[0]);
    }

    protected function tearDown()
    {
        $this->container = null;
    }

    protected function getServiceDefinition($id)
    {
        return $this->container->getDefinition($id);
    }

    protected function containerHasDefinition($id)
    {
        return $this->container->hasDefinition($id);
    }

    protected function containerHasAlias($id)
    {
        return $this->container->hasAlias($id);
    }
} 