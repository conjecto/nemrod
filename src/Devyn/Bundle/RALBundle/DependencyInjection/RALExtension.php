<?php

namespace Devyn\Bundle\RALBundle\DependencyInjection;

use Devyn\Component\RAL\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
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
class RALExtension extends Extension
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

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // namespaces
        if(isset($config['namespaces'])) {
            $this->registerRdfNamespaces($config['namespaces'], $container);
        }

        // sparql endpoints
        if(isset($config['endpoints'])) {
            $this->registerSparqlClients($config, $container);
        }

        // rdf resource mapping
        $this->registerResourceMappings($config, $container);

        //
        $this->registerResourceManagers($config, $container);

        //register elastica indexes and mappings
        $this->registerElasticaIndexes($config, $container);
    }

    /**
     * Load the namespaces in registry
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function registerRdfNamespaces(array $config, ContainerBuilder $container)
    {
        $registry = $container->getDefinition('ral.namespace_registry');
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
              ->setDefinition('ral.sparql.connection.'.$name, new DefinitionDecorator('ral.sparql.connection'))
              ->setArguments(array(
                  $endpoint['query_uri'],
                  isset($endpoint['update_uri']) ? $endpoint['update_uri'] : null
                ));
            $container->setAlias('sparql.'.$name, 'ral.sparql.connection.'.$name);
            if($name == $config["default_endpoint"])
                $container->setAlias('sparql', 'ral.sparql.connection.'.$name);
        }
    }

    /**
     * Register resource managers (one per connection)
     * @param array $config
     * @param ContainerBuilder $container
     */
    public function registerResourceManagers(array $config, ContainerBuilder $container)
    {
        foreach($config['endpoints'] as $name => $endpoint) {

            //repository factory
            $container->setDefinition('ral.repository_factory.'.$name, new DefinitionDecorator('ral.repository_factory'))
                ->setArguments(array($name));

            //persister
            $container->setDefinition('ral.persister.'.$name, new DefinitionDecorator('ral.persister'))
                ->setArguments(array($endpoint['query_uri']));

            $evd = $container->setDefinition('ral.resource_lifecycle_event_dispatcher.'.$name, new DefinitionDecorator('ral.resource_lifecycle_event_dispatcher'));
            $evd->addTag('ral.event_dispatcher', array("endpoint" => $name));

            $rm = $container->setDefinition('ral.resource_manager.'.$name, new DefinitionDecorator('ral.resource_manager'));
            $rm->setArguments(array(new Reference('ral.repository_factory.'.$name),$endpoint['query_uri']))
                //adding query builder
                ->addMethodCall('setClient', array(new Reference('ral.sparql.connection.'.$name)))
                //adding metadatfactory
                ->addMethodCall('setMetadataFactory', array(new Reference('ral.metadata_factory')))
                //adding event dispatcher
                ->addMethodCall('setEventDispatcher', array(new Reference('ral.resource_lifecycle_event_dispatcher.'.$name)));

            $rm->addMethodCall('setLogger', array(new Reference('logger')));

            //setting main alias
            if($name == $config["default_endpoint"]){
                $container->setAlias('rm', 'ral.resource_manager.'.$name);
            }
        }
    }

    /**
     * Parses active bundles for resources to map
     *
     * @param ContainerBuilder $container
     */
    private function registerResourceMappings(array $config, ContainerBuilder $container)
    {
        $paths = array();

        // foreach bundle, get the rdf resource path
        foreach ($container->getParameter('kernel.bundles') as $bundle=>$class) {
            //@todo check mapping type (annotation is the only one used for now)
            // building resource dir path
            $refl = new \ReflectionClass($class);
            $path = pathinfo($refl->getFileName());
            $resourcePath = $path['dirname'] . '\\RdfResource\\';
            //adding dir path to driver known pathes
            if(is_dir($resourcePath)) {
                $paths[$refl->getNamespaceName()] = $resourcePath;
            }
        }

        // registering all annotation mappings.
        $service = $container->getDefinition('ral.type_mapper');

        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        //adding paths to annotation driver
        $annDriver = $container->getDefinition('ral.metadata_annotation_driver');
        $annDriver->replaceArgument(1,$paths);

        $classes = $driver->getAllClassNames();

        foreach($classes as $class) {

            $metadata = $driver->loadMetadataForClass(new \ReflectionClass($class));
            foreach($metadata->types as $type) {
                $service->addMethodCall('set', array($type, $class));
            }
        }
    }

    /**
     * @todo create appropriate services
     * @param array $config
     * @param ContainerBuilder $container
     */
    public function registerElasticaIndexes(array $config, ContainerBuilder $container){

        foreach($config['elasticsearch']['clients'] as $name => $client) {
            $container
                ->setDefinition('ral.elasticsearch_client.'.$name, new DefinitionDecorator('ral.elasticsearch_client'))
                ->setArguments(array(array(
                    'host' => $client['host'],
                    'port' => $client['port']
                )));

        }

        foreach($config['elasticsearch']['indexes'] as $name => $index) {

            //@todo use default client
            $clientRef = null;
            if (isset($index['client'])) {
                $clientRef = new Reference('ral.elasticsearch_client.'.$index['client']);

                $container
                    ->setDefinition('ral.elasticsearch_index.'.$name, new DefinitionDecorator('ral.elasticsearch_index'))
                    ->setArguments(array($clientRef, $name));
                }

                foreach($index['types'] as $typeName => $type) {

                    $clientRef = null;

                        $clientRef = new Reference('ral.elasticsearch_type.'.$typeName);
                    }
                    $container
                        ->setDefinition('ral.elasticsearch_type.'.$typeName, new DefinitionDecorator('ral.elasticsearch_index'))
                        ->setArguments(array(new Reference('ral.elasticsearch_index.'.$name), $typeName));

            }
        }



    /**
     * @return string
     */
    public function getAlias()
    {
        return 'ral';
    }
}
