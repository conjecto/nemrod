<?php
namespace Conjecto\RAL\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SerializerMappingDriverPass
 * @package Devyn\Bundle\RdfFrameworkBundle\DependencyInjection\Compiler
 */
class SerializerMappingDriverPass implements CompilerPassInterface
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
        // replace the annotation driver class
        $container->setParameter('jms_serializer.metadata.annotation_driver.class', 'Conjecto\RAL\Bundle\Serializer\Metadata\Driver\AnnotationDriver');
    }

}
