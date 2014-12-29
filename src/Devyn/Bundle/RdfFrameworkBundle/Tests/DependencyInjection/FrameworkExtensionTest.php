<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Devyn\Bundle\RdfFrameworkBundle\Tests\DependencyInjection;

use Devyn\Bundle\RdfFrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\Mapping\Loader\XmlFileLoader;
use Symfony\Component\Validator\Validation;

class FrameworkExtensionTest extends TestCase
{
    /**
     * Load namespaces in namespace registry
     */
    public function testNamespaces()
    {
        $configs = array(
            array(
              'namespaces' => array(
                'foo'    => 'http://purl.org/ontology/foo/',
                'bar'    => 'http://www.w3.org/ns/bar#'
              )
            )
        );

        $container = new ContainerBuilder();
        $extension = new FrameworkExtension();
        $extension->load($configs, $container);
        $definition = $container->getDefinition('rdf_namespace_registry');
        $this->assertEquals(array(
              array("set", array("foo", 'http://purl.org/ontology/foo/')),
              array("set", array("bar", 'http://www.w3.org/ns/bar#')),
        ), $definition->getMethodCalls());
    }
}
