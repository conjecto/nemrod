<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\ElasticaBundle;

use Conjecto\Nemrod\Bundle\ElasticaBundle\DependencyInjection\CompilerPass\ElasticaFramingRegistrationPass;
use Conjecto\Nemrod\Bundle\ElasticaBundle\DependencyInjection\CompilerPass\ElasticaTypeRegistrationPass;
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
        $container->addCompilerPass(new ElasticaFramingRegistrationPass());
        $container->addCompilerPass(new ElasticaTypeRegistrationPass());
    }
}
