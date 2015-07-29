<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection;

use Conjecto\Nemrod\ResourceManager\Mapping\Driver\AnnotationDriver;
use Conjecto\Nemrod\Resource;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * FrameworkExtension.
 */
class NemrodExtension extends Extension
{
    /**
     * Responds to the app.config configuration parameter.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
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

        // namespaces
        if (isset($config['namespaces'])) {
            $this->registerRdfNamespaces($config['namespaces'], $container);
        }

        // sparql endpoints
        if (isset($config['endpoints'])) {
            $this->registerSparqlClients($config, $container);
        }

        // rdf resource mapping
        $this->registerResourceMappings($config, $container);

        //
        $this->registerResourceManagers($config, $container);

        // register jsonld frames paths
        $this->registerJsonLdFramePaths($config, $container);
    }

    /**
     * Load the namespaces in registry.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerRdfNamespaces(array $config, ContainerBuilder $container)
    {
        $registry = $container->getDefinition('nemrod.namespace_registry');
        foreach ($config as $prefix => $data) {
            $registry->addMethodCall('set', array($prefix, $data['uri']));
        }
    }

    /**
     * Register SPARQL clients.
     */
    public function registerSparqlClients(array $config, ContainerBuilder $container)
    {
        foreach ($config['endpoints'] as $name => $endpoint) {
            $container
                ->setDefinition('nemrod.sparql.connection.'.$name, new DefinitionDecorator('nemrod.sparql.connection'))
                ->setArguments(array(
                    $endpoint['query_uri'],
                    isset($endpoint['update_uri']) ? $endpoint['update_uri'] : null,
                ));
            $container->setAlias('sparql.'.$name, 'nemrod.sparql.connection.'.$name);
            if ($name === $config['default_endpoint']) {
                $container->setAlias('sparql', 'nemrod.sparql.connection.'.$name);
            }
        }
    }

    /**
     * Register resource managers (one per connection).
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function registerResourceManagers(array $config, ContainerBuilder $container)
    {
        foreach ($config['endpoints'] as $name => $endpoint) {

            //repository factory
            $container->setDefinition('nemrod.repository_factory.'.$name, new DefinitionDecorator('nemrod.repository_factory'));

            //persister
            $container->setDefinition('nemrod.persister.'.$name, new DefinitionDecorator('nemrod.persister'))
                ->setArguments(array($endpoint['query_uri']));

            $evd = $container->setDefinition('nemrod.resource_lifecycle_event_dispatcher.'.$name, new DefinitionDecorator('nemrod.resource_lifecycle_event_dispatcher'));
            $evd->addTag('nemrod.event_dispatcher', array('endpoint' => $name));

            $rm = $container->setDefinition('nemrod.resource_manager.'.$name, new DefinitionDecorator('nemrod.resource_manager'));
            $rm->setArguments(array(new Reference('nemrod.repository_factory.'.$name), $endpoint['query_uri']))
                //adding query builder
                ->addMethodCall('setClient', array(new Reference('nemrod.sparql.connection.'.$name)))
                //adding metadatfactory
                ->addMethodCall('setMetadataFactory', array(new Reference('nemrod.metadata_factory')))
                //adding event dispatcher
                ->addMethodCall('setEventDispatcher', array(new Reference('nemrod.resource_lifecycle_event_dispatcher.'.$name)))
                ->addMethodCall('setNamespaceRegistry', array(new Reference('nemrod.namespace_registry')));

            $rm->addMethodCall('setLogger', array(new Reference('logger')));

            //setting main alias
            if ($name === $config['default_endpoint']) {
                $container->setAlias('rm', 'nemrod.resource_manager.'.$name);
            }
        }
    }

    /**
     * Parses active bundles for resources to map.
     *
     * @param ContainerBuilder $container
     */
    private function registerResourceMappings(array $config, ContainerBuilder $container)
    {
        // registering all annotation mappings.
        $service = $container->getDefinition('nemrod.type_mapper');
        //setting default resource
        $service->addMethodCall('setDefaultResourceClass', array($container->getParameter('nemrod.resource.class')));

        // get bundles and classes
        $finder = new Finder();
        $classes = array();
        $paths = array();
        foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
            // in bundle
            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()) . DIRECTORY_SEPARATOR.'RdfResource')) {
                $paths[$bundle] = dirname($reflection->getFilename()) . DIRECTORY_SEPARATOR.'RdfResource';
                foreach($finder->in($dir) as $file) {
                    if(is_file($file)) {
                        $classes[] = $this->getClassRelativePath($file->getPathName());
                    }
                }
            }
        }
        var_dump($paths);
        var_dump($classes);
        die;
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        //adding paths to annotation driver
        $annDriver = $container->getDefinition('nemrod.metadata_annotation_driver');
        $annDriver->replaceArgument(1, $paths);

        foreach ($classes as $class) {
            $metadata = $driver->loadMetadataForClass(new \ReflectionClass($class));
            foreach ($metadata->types as $type) {
                $service->addMethodCall('set', array($type, $class));
            }
        }

        // guess class filiation
        $registry = $container->getDefinition('nemrod.filiation.builder');
        $registry->addMethodCall('guessRdfClassFiliation', array($classes));
    }

    /**
     * Register jsonld frames paths for each bundle.
     *
     * @return string
     */
    public function registerJsonLdFramePaths($config, ContainerBuilder $container)
    {
        $jsonLdFilesystemLoaderDefinition = $container->getDefinition('nemrod.jsonld.frame.loader.filesystem');
        foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
            // in app
            if (is_dir($dir = $container->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR.$bundle.DIRECTORY_SEPARATOR.'frames')) {
                $this->addJsonLdFramePath($jsonLdFilesystemLoaderDefinition, $dir, $bundle);
            }

            // in bundle
            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'frames')) {
                $this->addJsonLdFramePath($jsonLdFilesystemLoaderDefinition, $dir, $bundle);
            }
        }

        if (is_dir($dir = $container->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'frames')) {
            $jsonLdFilesystemLoaderDefinition->addMethodCall('addPath', array($dir));
        }

        $jsonLdFilesystemLoaderDefinition->addMethodCall('setFiliationBuilder', array(new Reference('nemrod.filiation.builder')));
        $jsonLdFilesystemLoaderDefinition->addMethodCall('setMetadataFactory', array(new Reference('nemrod.jsonld.metadata_factory')));
    }

    /**
     * Add a jsonld frame path.
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

    private function getClassRelativePath($filePath)
    {
        $cutName = strstr($filePath, DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);
        $cutName = substr($cutName, 5);
        $name = substr($cutName, 0, strlen($cutName) - 4);
        return str_replace(DIRECTORY_SEPARATOR, '\\', $name);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'nemrod';
    }
}
