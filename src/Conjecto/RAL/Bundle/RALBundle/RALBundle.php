<?php

namespace Conjecto\RAL\Bundle\RALBundle;

use Conjecto\RAL\Bundle\RALBundle\DependencyInjection\CompilerPass\EventListenerRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 */
class RALBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EventListenerRegistrationPass());
    }
}
