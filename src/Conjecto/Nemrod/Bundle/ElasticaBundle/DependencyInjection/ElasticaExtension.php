<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\ElasticaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * FrameworkExtension.
 */
class ElasticaExtension extends Extension
{
    /**
     * Definition of elastica clients as configured by this extension.
     *
     * @var array
     */
    private $clients = array();

    /**
     * An array of indexes as configured by the extension.
     *
     * @var array
     */
    private $indexConfigs = array();

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

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (empty($config['clients']) || empty($config['indexes'])) {
            // No Clients or indexes are defined
            return;
        }

        if (empty($config['default_client'])) {
            $keys = array_keys($config['clients']);
            $config['default_client'] = reset($keys);
        }

        if (empty($config['default_index'])) {
            $keys = array_keys($config['indexes']);
            $config['default_index'] = reset($keys);
        }


        $this->loadClients($config['clients'], $container);
        $container->setAlias('nemrod.elastica.client', sprintf('nemrod.elastica.client.%s', $config['default_client']));

        $this->loadIndexes($config['indexes'], $container);
        $container->setAlias('nemrod.elastica.index', sprintf('nemrod.elastica.index.%s', $config['default_index']));

        //register elastica indexes and mappings
        //$this->registerElasticaIndexes($config, $container);

        // register jsonld frames paths
        $this->registerJsonLdFramePaths($config, $container);
    }

    /**
     * Loads the configured clients.
     *
     * @param array $clients An array of clients configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @return array
     */
    private function loadClients(array $clients, ContainerBuilder $container)
    {
        foreach ($clients as $name => $clientConfig) {
            $clientId = sprintf('nemrod.elastica.client.%s', $name);

            $clientDef = new DefinitionDecorator('nemrod.elastica.client.abstract');
            $clientDef->replaceArgument(0, $clientConfig);

            $clientDef->addTag('nemrod.elastica.client');

            $container->setDefinition($clientId, $clientDef);

            $this->clients[$name] = array(
                'id' => $clientId,
                'reference' => new Reference($clientId)
            );
        }
    }

    /**
     * Loads the configured indexes.
     *
     * @param array $indexes An array of indexes configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @throws \InvalidArgumentException
     * @return array
     */
    private function loadIndexes(array $indexes, ContainerBuilder $container)
    {
        $confManager = $container->getDefinition('nemrod.elastica.config_manager');
        $indexRegistry = $container->getDefinition('nemrod.elastica.index_registry');

        foreach ($indexes as $name => $index) {
            $indexId = sprintf('nemrod.elastica.index.%s', $name);
            $indexName = isset($index['index_name']) ? $index['index_name']: $name;

            $indexDef = new DefinitionDecorator('nemrod.elastica.index.abstract');
            $indexDef->replaceArgument(0, $indexName);
            $indexDef->addTag('nemrod.elastica.index', array(
                'name' => $name,
            ));

            if (isset($index['client'])) {
                $clientId = 'nemrod.elastica.client.' . $index['client'];
                $indexDef->setFactory(array(new Reference($clientId), 'getIndex'));
            }

            $container->setDefinition($indexId, $indexDef);
            $reference = new Reference($indexId);

            $this->indexConfigs[$name] = array(
                'elasticsearch_name' => $indexName,
                'reference' => $reference,
                'name' => $name,
                'settings' => $index['settings'],
                //'type_prototype' => isset($index['type_prototype']) ? $index['type_prototype'] : array(),
                //'use_alias' => $index['use_alias'],
            );

            $confManager->addMethodCall('setIndexConfigurationArray', array($name, $this->indexConfigs[$name]));

            $indexRegistry->addMethodCall('registerIndex', array($name, $reference));
        }
    }

    /**
     * Register jsonld frames paths for each bundle.
     *
     * @return string
     */
    public function registerJsonLdFramePaths($config, ContainerBuilder $container)
    {
        $jsonLdFilesystemLoaderDefinition = $container->getDefinition('nemrod.elastica.jsonld.frame.loader.filesystem');
        $jsonLdFilesystemLoaderDefinition->addMethodCall('setFiliationBuilder', array(new Reference('nemrod.filiation.builder')));
        $jsonLdFilesystemLoaderDefinition->addMethodCall('setMetadataFactory', array(new Reference('nemrod.jsonld.metadata_factory')));

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

        if (is_dir($dir = $container->getParameter('kernel.root_dir').'/Resources/frames')) {
            $jsonLdFilesystemLoaderDefinition->addMethodCall('addPath', array($dir));
        }

        $serializerHelper = $container->getDefinition('nemrod.elastica.serializer_helper');
        $serializerHelper->addMethodCall('setJsonLdFrameLoader', array(new Reference('nemrod.elastica.jsonld.frame.loader.filesystem')));
        $serializerHelper->addMethodCall('setConfig', array($config));
        $jsonLdFilesystemLoaderDefinition->addMethodCall('setContainer', array(new Reference('service_container')));
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

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'elastica';
    }
}
