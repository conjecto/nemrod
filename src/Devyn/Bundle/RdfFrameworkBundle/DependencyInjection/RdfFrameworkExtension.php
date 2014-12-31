<?php

namespace Devyn\Bundle\RdfFrameworkBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Validator\Validation;

/**
 * FrameworkExtension.
 */
class RdfFrameworkExtension extends Extension
{
    /**
     * Responds to the app.config configuration parameter.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     * @throws LogicException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('form.xml');
        $loader->load('serializer.xml');
        $loader->load('event_listeners.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // register jsonld frames paths
        $this->registerJsonLdFramePaths($config, $container);

    }

    /**
     * Register jsonld frames paths for each bundle
     *
     * @return string
     */
    public function registerJsonLdFramePaths($config, ContainerBuilder $container)
    {
        $jsonLdFilesystemLoaderDefinition = $container->getDefinition('rdf.jsonld.frame.loader.filesystem');
        foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
            // in app
            if (is_dir($dir = $container->getParameter('kernel.root_dir').'/Resources/'.$bundle.'/frames')) {
                $this->addJsonLdFramePath($jsonLdFilesystemLoaderDefinition, $dir, $bundle);
            }

            // in bundle
            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/frames')) {
                $this->addJsonLdFramePath($jsonLdFilesystemLoaderDefinition, $dir, $bundle);
            }
        }
    }

    /**
     * Add a jsonld frame path
     *
     * @param $jsonLdFilesystemLoaderDefinition
     * @param $dir
     * @param $bundle
     */
    private function addJsonLdFramePath($jsonLdFilesystemLoaderDefinition, $dir, $bundle)
    {
        $name = $bundle;
        if ('Bundle' === substr($name, -6)) {
            $name = substr($name, 0, -6);
        }
        $jsonLdFilesystemLoaderDefinition->addMethodCall('addPath', array($dir, $name));
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'rdf_framework';
    }
}
