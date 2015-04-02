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

        $serializerHelper = $container->getDefinition('nemrod.elastica.serializer_helper');
        $serializerHelper->addMethodCall('setConstructedGraphProvider', array(new Reference('nemrod.jsonld.graph_provider')));
        $serializerHelper->addMethodCall('setJsonLdFrameLoader', array(new Reference('nemrod.jsonld.frame.loader.filesystem')));
        $serializerHelper->addMethodCall('setConfig', array($config));
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'elastica';
    }
}
