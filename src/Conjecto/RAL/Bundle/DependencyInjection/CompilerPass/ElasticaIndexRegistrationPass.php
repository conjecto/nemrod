<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/02/2015
 * Time: 15:17
 */

namespace Conjecto\RAL\Bundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ElasticaIndexRegistrationPass
 * @package Conjecto\RAL\Bundle\DependencyInjection\CompilerPass
 */
class ElasticaIndexRegistrationPass implements CompilerPassInterface
{

    /**
     * @param ContainerBuilder $container
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ral.elasticsearch_type_registry')) {
            return;
        }

        $definition = $container->getDefinition('ral.elasticsearch_type_registry');

        $typesSevices = $container->findTaggedServiceIds('ral.elasticsearch_type');

        foreach ($typesSevices as $id => $tagAttributes) {

            foreach ($tagAttributes as $tagAttr) {

                $definition->addMethodCall('registerType', array($tagAttr['type'], new Reference($id)));
            }

        }
    }
} 