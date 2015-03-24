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
                ->setDefinition('nemrod.elastica.client.'.$name, new DefinitionDecorator('nemrod.elastica.client'))
                ->setArguments(array(array(
                    'host' => $client['host'],
                    'port' => $client['port'],
                )));
        }

        foreach ($config['indexes'] as $name => $types) {
            $clientRef = new Reference('nemrod.elastica.client.'.$types['client']);
            $container
                ->setDefinition('nemrod.elastica.index.'.$name, new DefinitionDecorator('nemrod.elastica.index'))
                ->setArguments(array($clientRef, $name))
                ->addTag('nemrod.elastica.name', array('name' => $name));

            foreach ($types['types'] as $typeName => $settings) {
                //type
                $container
                    ->setDefinition('nemrod.elastica.type.'.$name.'.'.$typeName, new DefinitionDecorator('nemrod.elastica.type'))
                    ->setArguments(array(new Reference('nemrod.elastica.index.'.$name), $settings['type']))
                    ->addTag('nemrod.elastica.type', array('type' => $settings['type']));

                //search service
                $container
                    ->setDefinition('nemrod.elastica.search.'.$name.'.'.$typeName, new DefinitionDecorator('nemrod.elastica.search'))
                    ->setArguments(array(new Reference('nemrod.elastica.type.'.$name.'.'.$typeName), $typeName));

                //@todo place this in a separate func ?
                //registering config to configManager
                $settings['type_service_id'] = 'nemrod.elastica.type.'.$name.'.'.$typeName;
                $confManager = $container->getDefinition('nemrod.elastica.config_manager');
                $confManager->addMethodCall('setConfig', array($settings['type'], $settings));
            }
        }

        $cacheDefinition = $container->getDefinition('nemrod.elastica.cache');
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
