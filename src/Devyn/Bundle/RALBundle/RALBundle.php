<?php

/*
 * This file is part of the Devyn package.
 *
 * (c) Conjecto
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Bundle\RALBundle;

use Devyn\Bundle\RALBundle\DependencyInjection\CompilerPass\ElasticaTypeRegistrationPass;
use Devyn\Bundle\RALBundle\DependencyInjection\CompilerPass\EventListenerRegistrationPass;
use Devyn\Bundle\RdfFrameworkBundle\DependencyInjection\Compiler\RdfNamespacePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\DependencyInjection\RegisterListenersPass;

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
        $container->addCompilerPass(new ElasticaTypeRegistrationPass());
    }
}
