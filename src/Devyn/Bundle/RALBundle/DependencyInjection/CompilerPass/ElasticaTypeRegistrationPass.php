<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 18/02/2015
 * Time: 17:15
 */

namespace Devyn\Bundle\RALBundle\DependencyInjection\CompilerPass;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ElasticaTypeRegistrationPass
 * @package Devyn\Bundle\RALBundle\DependencyInjection\CompilerPass
 */
class ElasticaTypeRegistrationPass implements CompilerPassInterface
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

        //foreach ($typesSevices as $id => $tagAttributes) {

            //echo $id;
            //$definition->addMethodCall('registerType', array($id, new Reference($id) )
            //);

        //}

    }


} 