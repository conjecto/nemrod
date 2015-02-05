<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 04/02/2015
 * Time: 17:48
 */

namespace Devyn\Bundle\RALBundle\DependencyInjection\CompilerPass;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventListenerRegistrationPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $tmpDispatchers = $container->findTaggedServiceIds('ral.event_dispatcher');
        if (!$tmpDispatchers) {
            return;
        }

        $dispatchers = array();

        foreach ($tmpDispatchers as $key => $dispatcherTags) {
            foreach ($dispatcherTags as $dispatcherTag) {
                $endP = (isset($dispatcherTag['endpoint'])) ? $dispatcherTag['endpoint'] : 'default';
                $dispatchers[$endP] = $key;
            }
        }

        //finding and registering listeners
        $listeners = $container->findTaggedServiceIds('ral.resource_event_listener');
        if (!empty($listeners)) {
            foreach ($dispatchers as $endPoint => $dispatcher) {
                foreach ($listeners as $listId => $listenerTags) {
                    $listenerDef = $container->getDefinition($listId);
                    foreach ($listenerTags as $tag) {
                        if(isset ($tag['endpoint']) &&
                            isset($dispatchers[$tag['endpoint']]) &&
                            ($dispatchers[$tag['endpoint']] == $dispatcher))
                        {
                            $def = $container->getDefinition($dispatchers[$tag['endpoint']]);
                            $def->addMethodCall('addListener', array($tag['event'], array($listenerDef, $tag['method'])));
                        }
                    }
                }
            }
        }

        //finding and registering subscribers
        $subscribers = $container->findTaggedServiceIds('ral.resource_event_subscriber');
        if (!empty($subscribers)) {
            foreach ($dispatchers as $endPoint => $dispatcher) {
                foreach ($subscribers as $listId => $listenerTags) {
                    $listenerDef = $container->getDefinition($listId);
                    foreach ($listenerTags as $tag) {
                        if(isset ($tag['endpoint']) &&
                            isset($dispatchers[$tag['endpoint']]) &&
                            ($dispatchers[$tag['endpoint']] == $dispatcher))
                        {
                            $def = $container->getDefinition($dispatchers[$tag['endpoint']]);
                            $def->addMethodCall('addsubscriber', array($listenerDef));
                        }
                    }
                }
            }
        }
    }
} 