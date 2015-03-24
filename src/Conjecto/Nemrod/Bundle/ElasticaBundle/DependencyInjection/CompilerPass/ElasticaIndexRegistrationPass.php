<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/02/2015
 * Time: 15:17.
 */

namespace Conjecto\Nemrod\Bundle\ElasticaBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ElasticaIndexRegistrationPass.
 */
class ElasticaIndexRegistrationPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('nemrod.elastica.type_registry')) {
            return;
        }

        $definition = $container->getDefinition('nemrod.elastica.type_registry');

        $typesSevices = $container->findTaggedServiceIds('nemrod.elastica.type');

        foreach ($typesSevices as $id => $tagAttributes) {
            foreach ($tagAttributes as $tagAttr) {
                $definition->addMethodCall('registerType', array($tagAttr['type'], new Reference($id)));
            }
        }
    }
}
