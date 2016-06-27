<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\CompilerPass;

use Conjecto\Nemrod\Resource;
use JMS\Serializer\GraphNavigator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Duplicate Resource handler for each mapped type
 */
class JMSSerializerResourceHandlerPass implements CompilerPassInterface
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
        if($container->has('jms_serializer.handler_registry')) {
            $handlers = $container->getDefinition('jms_serializer.handler_registry')->getArgument(1);
            $typeMapper = $container->getDefinition('nemrod.type_mapper');
            $handler = null;
            foreach($handlers[GraphNavigator::DIRECTION_SERIALIZATION] as $key => $_handler) {
                if($key == Resource::class) {
                    $handler = $_handler;
                }
            }
            foreach($typeMapper->getMethodCalls() as $call) {
                if($call[0] == 'set') {
                    $class = $call[1][1];
                    $handlers[GraphNavigator::DIRECTION_SERIALIZATION][$class] = $handler;
                }
            }
            $container->getDefinition('jms_serializer.handler_registry')->replaceArgument(1, $handlers);
        }
    }
}
