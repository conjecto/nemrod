<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle\Tests\DependencyInjection;

use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\NemrodExtension;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class NemrodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerBuilder */
    private $container;

    /** @var  Extension */
    private $extension;

    private $minConfig = array(/*'kernel.bundles'=>array()*/);

    protected function setUp()
    {
        //have to manually register annotation
        AnnotationRegistry::registerFile(__DIR__.'/../../../../ResourceManager/Annotation/Resource.php');
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', array('Conjecto\Nemrod\Bundle\NemrodBundle\Tests\Fixtures\TestBundle\FixtureTestBundle'));
        $this->container->setParameter('kernel.root_dir', __DIR__."/../../../../");

        $this->extension = new NemrodExtension();
        $this->container->registerExtension($this->extension);

        $this->load(array( array('endpoints' => array('foopoint' => 'http://bar.org/sparql'), "default_endpoint" => 'foopoint', 'namespaces' => array('foo' => 'http://www.example.org/foo#'))));
    }

    protected function load($config)
    {
        $this->extension->load($config, $this->container);
    }

    public function testExtensionRegistersNamespaces()
    {
        $service = $this->getServiceDefinition('nemrod.namespace_registry');
        $calls = $service->getMethodCalls();

        $this->assertEquals(array("set", array('foo', 'http://www.example.org/foo#')), $calls[0]);
    }

    public function testExtensionRegisterClient()
    {
        $service = $this->getServiceDefinition('nemrod.namespace_registry');
        $calls = $service->getMethodCalls();

        $this->assertTrue($this->containerHasDefinition('nemrod.sparql.connection.foopoint'));
        $service = $this->getServiceDefinition('nemrod.sparql.connection.foopoint');
        $service->getMethodCalls();
    }

    public function testExtensionRegisterManager()
    {
        $this->assertTrue($this->containerHasAlias('rm'));
        $this->assertTrue($this->containerHasDefinition('nemrod.resource_manager.foopoint'));
    }

    public function testExtensionRegisterResourceMapping()
    {
        $service = $this->getServiceDefinition('nemrod.type_mapper');
        $calls = $service->getMethodCalls();

        $this->assertEquals(array (array("setDefaultResourceClass", array("Conjecto\\Nemrod\\Resource")), array("set", array('foo:Class', 'Conjecto\Nemrod\Bundle\NemrodBundle\Tests\Fixtures\TestBundle\RdfResource\TestResource'))), $calls);
    }

    /**
     * Load namespaces in namespace registry.
     */
    public function testNamespaces()
    {
        $configs = array(
            array(
                'namespaces' => array(
                    'foo'    => 'http://purl.org/ontology/foo/',
                    'bar'    => 'http://www.w3.org/ns/bar#',
                ),
            ),
        );

        $extension = new NemrodExtension();
        $extension->load($configs, $this->container);
        $definition = $this->container->getDefinition('nemrod.namespace_registry');

        $this->assertEquals(array(
            array("set", array("foo", 'http://purl.org/ontology/foo/')),
            array("set", array("bar", 'http://www.w3.org/ns/bar#')),
        ), $definition->getMethodCalls());
    }

    public function testResourceMapping()
    {
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
