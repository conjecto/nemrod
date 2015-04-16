<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 02/04/2015
 * Time: 11:41.
 */
namespace Conjecto\Nemrod\Bundle\ElasticaBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class ElasticaFramingRegistrationPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->getExtensionConfig('elastica')[0];
        $jsonLdFrameLoader = $container->get('nemrod.jsonld.frame.loader.filesystem');

        foreach ($config['indexes'] as $name => $types) {
            $clientRef = new Reference('nemrod.elastica.client.'.$types['client']);
            $container
                ->setDefinition('nemrod.elastica.index.'.$name, new DefinitionDecorator('nemrod.elastica.index'))
                ->setArguments(array($clientRef, $name))
                ->addTag('nemrod.elastica.name', array('name' => $name));

            foreach ($types['types'] as $typeName => $settings) {
                $frame = $jsonLdFrameLoader->load($settings['frame']);
                $settings['frame'] = $frame;
                if (isset($frame['@type']) || isset($settings['type'])) {
                    $type = '';
                    if (isset($settings['type'])) {
                        $type = $settings['type'];
                    } elseif (isset($frame['@type'])) {
                        $type = $frame['@type'];
                    }

                    //type
                    $container
                        ->setDefinition('nemrod.elastica.type.'.$name.'.'.$typeName, new DefinitionDecorator('nemrod.elastica.type'))
                        ->setArguments(array(new Reference('nemrod.elastica.index.'.$name), $type))
                        ->addTag('nemrod.elastica.type', array('type' => $type));

                    //search service
                    $container
                        ->setDefinition('nemrod.elastica.search.'.$name.'.'.$typeName, new DefinitionDecorator('nemrod.elastica.search'))
                        ->setArguments(array(new Reference('nemrod.elastica.type.'.$name.'.'.$typeName), $typeName));

                    //@todo place this in a separate func ?
                    //registering config to configManager
                    $settings['type_service_id'] = 'nemrod.elastica.type.'.$name.'.'.$typeName;
                    $confManager = $container->getDefinition('nemrod.elastica.config_manager');
                    $confManager->addMethodCall('setConfig', array($type, $settings));
                }
            }
        }
    }
}
