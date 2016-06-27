<?php

namespace Tests\Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection;

use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\NemrodExtension;
use Conjecto\Nemrod\Bundle\NemrodBundle\NemrodBundle;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Serializer;
use JMS\SerializerBundle\DependencyInjection\JMSSerializerExtension;
use JMS\SerializerBundle\JMSSerializerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveParameterPlaceHoldersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\Conjecto\Nemrod\EndpointTestCase;
use Tests\Conjecto\Nemrod\Fixtures\RdfResource\FoafPerson;

/**
 *
 */
class NemrodExtensionTest extends EndpointTestCase
{
    /**
     *
     */
    public function testLoad()
    {
        $container = $this->getContainerForConfig(array(array()));
        $container->compile();
    }

    /**
     * @param array $configs
     * @param KernelInterface|null $kernel
     * @return ContainerBuilder
     */
    private function getContainerForConfig(array $configs, KernelInterface $kernel = null)
    {
        if (null === $kernel) {
            $kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');
            $kernel
                ->expects($this->any())
                ->method('getBundles')
                ->will($this->returnValue(array()))
            ;
        }

        $bundle = new NemrodBundle($kernel);
        $extension = $bundle->getContainerExtension();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', array());
        $container->setParameter('kernel.root_dir', '');
        $container->set('annotation_reader', new AnnotationReader());
        $container->set('rm', self::$manager);

        $container->registerExtension($extension);
        $extension->load($configs, $container);

        $bundle->build($container);

        return $container;
    }
}