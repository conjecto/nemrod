<?php

namespace Conjecto\RAL\Bundle\ElasticaBundle;

use Conjecto\RAL\Bundle\ElasticaBundle\DependencyInjection\CompilerPass\ElasticaIndexRegistrationPass;
use Conjecto\RAL\Bundle\ElasticaBundle\DependencyInjection\CompilerPass\ElasticaTypeRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 */
class ElasticaBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ElasticaTypeRegistrationPass());
        $container->addCompilerPass(new ElasticaIndexRegistrationPass());
    }
}
