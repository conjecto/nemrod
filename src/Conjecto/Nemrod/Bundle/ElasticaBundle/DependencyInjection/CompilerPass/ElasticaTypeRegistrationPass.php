<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\ElasticaBundle\DependencyInjection\CompilerPass;

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
        if (!$container->hasDefinition('nemrod.elastica.type_registry')) {
            return;
        }

        $definition = $container->getDefinition('nemrod.elastica.type_registry');

        $typesSevices = $container->findTaggedServiceIds('nemrod.elastica.type');

        foreach ($typesSevices as $id => $tagAttributes) {
            foreach ($tagAttributes as $tagAttr) {
                $definition->addMethodCall('registerType', array($tagAttr['name'], new Reference($id)));
            }
        }
    }
}
