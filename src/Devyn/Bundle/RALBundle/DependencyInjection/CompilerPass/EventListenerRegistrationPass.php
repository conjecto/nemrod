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
        $dispatchers = $container->findTaggedServiceIds('ral.event_dispatcher');
        if (!$dispatchers) {
            return;
        }

        $listeners = $container->findTaggedServiceIds('ral.resource_event_listener');
        if (!$listeners) {
            return;
        }
        echo "a";

        foreach ($dispatchers as $dispId => $dispatcher) {
            echo "disp: ".$dispId;
            var_dump($dispatcher);
            echo "listeners";
            foreach ($listeners as $listId => $listenerTags) {
                foreach ($listenerTags as $tag) {
                    if(isset ($tag['endpoint'])) {
                        //$container->getDefinition()
                        //@todo listener added to right dispatcher
                    }
                }
            }
            echo "<br/>";
        }
    }

} 