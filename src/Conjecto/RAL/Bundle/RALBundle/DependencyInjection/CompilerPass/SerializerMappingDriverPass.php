<?php
namespace Conjecto\RAL\Bundle\RALBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SerializerMappingDriverPass.
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
        $container->setParameter('jms_serializer.metadata.annotation_driver.class', 'Conjecto\RAL\Bundle\RALBundle\Serializer\Metadata\Driver\AnnotationDriver');
    }
}
