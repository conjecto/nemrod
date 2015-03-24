<?php

namespace Conjecto\Nemrod\Bundle\NemrodBundle;

use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\CompilerPass\EventListenerRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 */
class NemrodBundle extends Bundle
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
