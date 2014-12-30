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

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if(isset($config['namespaces'])) {
            $this->registerRdfNamespaces($config['namespaces'], $container);
        }

        if(isset($config['endpoints'])) {
            $this->registerSparqlClients($config, $container);
        }
    }

    /**
     * Load the namespaces in registry
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function registerRdfNamespaces(array $config, ContainerBuilder $container)
    {
        $registry = $container->getDefinition('rdf.namespace.registry');
        foreach($config as $prefix => $data) {
            $registry->addMethodCall('set', array($prefix, $data['uri']));
        }
    }

    /**
     * Register SPARQL clients
     */
    public function registerSparqlClients(array $config, ContainerBuilder $container)
    {
        foreach($config['endpoints'] as $name => $endpoint) {
            $container
              ->setDefinition('rdf.sparql.connection.'.$name, new DefinitionDecorator('rdf.sparql.connection'))
              ->setArguments(array(
                  $endpoint['query_uri'],
                  isset($endpoint['update_uri']) ? $endpoint['update_uri'] : null
                ));
            $container->setAlias('sparql.'.$name, 'rdf.sparql.connection.'.$name);
            if($name == $config["default_endpoint"])
                $container->setAlias('sparql', 'rdf.sparql.connection.'.$name);
        }
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'rdf_framework';
    }
}
