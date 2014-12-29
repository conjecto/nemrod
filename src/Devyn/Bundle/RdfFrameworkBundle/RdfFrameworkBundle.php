<?php

/*
 * This file is part of the Devyn package.
 *
 * (c) Conjecto
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Bundle\RdfFrameworkBundle;

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
class RdfFrameworkBundle extends Bundle
{

}
