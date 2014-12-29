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

use Devyn\Bundle\RdfFrameworkBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest
 * @package Devyn\Bundle\RdfFrameworkBundle\Tests\DependencyInjection
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * namespaces config
     */
    public function testNamespacesConfig()
    {
        $processor = new Processor();
        $config = array(
            'namespaces' => array(
              'foo'    => 'http://purl.org/ontology/foo/',
              'bar'    => 'http://www.w3.org/ns/bar#'
            )
        );
        $config = $processor->processConfiguration(new Configuration(true), array($config));
        $this->assertEquals(
            array_merge(array('namespaces' => array(
                'foo' => array('uri' => 'http://purl.org/ontology/foo/'),
                'bar' => array('uri' => 'http://www.w3.org/ns/bar#')
              )), self::getBundleDefaultConfig()),
            $config
        );
    }

    /**
     * Default config
     */
    static protected function getBundleDefaultConfig()
    {
        return array();
    }

}
