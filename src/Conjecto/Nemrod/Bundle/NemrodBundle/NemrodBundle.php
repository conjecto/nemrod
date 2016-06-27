<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle;

use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\CompilerPass\EventListenerRegistrationPass;
use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\CompilerPass\JMSSerializerResourceHandlerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
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
        $container->addCompilerPass(new JMSSerializerResourceHandlerPass(), PassConfig::TYPE_REMOVE);
    }
}
