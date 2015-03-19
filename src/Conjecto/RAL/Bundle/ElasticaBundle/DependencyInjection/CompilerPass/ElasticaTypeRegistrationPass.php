<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 18/02/2015
 * Time: 17:15.
 */

namespace Conjecto\RAL\Bundle\ElasticaBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ElasticaTypeRegistrationPass.
 */
class ElasticaTypeRegistrationPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ral.elastica.type_registry')) {
            return;
        }

        $definition = $container->getDefinition('ral.elastica.type_registry');

        $typesSevices = $container->findTaggedServiceIds('ral.elastica.type');

        foreach ($typesSevices as $id => $tagAttributes) {
            foreach ($tagAttributes as $tagAttr) {
                $definition->addMethodCall('registerType', array($tagAttr['type'], new Reference($id)));
            }
        }
    }
}
