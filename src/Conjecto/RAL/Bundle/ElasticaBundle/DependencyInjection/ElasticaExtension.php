<?php

namespace Conjecto\RAL\Bundle\ElasticaBundle\DependencyInjection;

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

        //register elastica indexes and mappings
        $this->registerElasticaIndexes($config, $container);
    }

    /**
     * @todo create appropriate services
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function registerElasticaIndexes(array $config, ContainerBuilder $container)
    {
        foreach ($config['clients'] as $name => $client) {
            $container
                ->setDefinition('ral.elastica.client.'.$name, new DefinitionDecorator('ral.elastica.client'))
                ->setArguments(array(array(
                    'host' => $client['host'],
                    'port' => $client['port'],
                )));
        }

        foreach ($config['indexes'] as $name => $types) {
            $clientRef = new Reference('ral.elastica.client.'.$types['client']);
            $container
                ->setDefinition('ral.elastica.index.'.$name, new DefinitionDecorator('ral.elastica.index'))
                ->setArguments(array($clientRef, $name))
                ->addTag('ral.elastica.name', array('name' => $name));

            foreach ($types['types'] as $typeName => $settings) {
                //type
                $container
                    ->setDefinition('ral.elastica.type.'.$name.'.'.$typeName, new DefinitionDecorator('ral.elastica.type'))
                    ->setArguments(array(new Reference('ral.elastica.index.'.$name), $settings['type']))
                    ->addTag('ral.elastica.type', array('type' => $settings['type']));

                //search service
                $container
                    ->setDefinition('ral.elastica.search.'.$name.'.'.$typeName, new DefinitionDecorator('ral.elastica.search'))
                    ->setArguments(array(new Reference('ral.elastica.type.'.$name.'.'.$typeName), $typeName));

                //@todo place this in a separate func ?
                //registering config to configManager
                $settings['type_service_id'] = 'ral.elastica.type.'.$name.'.'.$typeName;
                $confManager = $container->getDefinition('ral.elastica.config_manager');
                $confManager->addMethodCall('setConfig', array($settings['type'], $settings));
            }
        }

        $cacheDefinition = $container->getDefinition('ral.elastica.cache');
        $cacheDefinition->addMethodCall('setResourceManager', array(new Reference('rm')));
        $cacheDefinition->addMethodCall('setConfig', array($config));
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'elastica';
    }
}
