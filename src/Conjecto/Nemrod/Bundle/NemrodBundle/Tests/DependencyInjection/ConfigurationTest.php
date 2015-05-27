<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Bundle\NemrodBundle\Tests\DependencyInjection\Configuration;

use Conjecto\Nemrod\Bundle\NemrodBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function setUp()
    {
        $this->configuration = new Configuration(array());
    }

    public function testEmptyConfigContainsFormatMappingOptionNode()
    {
        $tree = $this->configuration->getConfigTreeBuilder()->buildTree();
        $children = $tree->getChildren();

        $this->assertArrayHasKey('namespaces', $children);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\PrototypedArrayNode', $children['namespaces']);

        $this->assertArrayHasKey('endpoints', $children);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\PrototypedArrayNode', $children['endpoints']);

        $this->assertArrayHasKey('default_endpoint', $children);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ScalarNode', $children['default_endpoint']);
    }

    /**
     * namespaces config.
     */
    public function testNamespacesConfig()
    {
        $processor = new Processor();
        $config = array(
            'namespaces' => array(
                'foo'    => 'http://purl.org/ontology/foo/',
                'bar'    => 'http://www.w3.org/ns/bar#',
            ),
        );
        $config = $processor->processConfiguration(new Configuration(true), array($config));
        $this->assertEquals(
            array_merge(array('namespaces' => array(
                'foo' => array('uri' => 'http://purl.org/ontology/foo/'),
                'bar' => array('uri' => 'http://www.w3.org/ns/bar#'),
            ),    'endpoints' => array()), self::getBundleDefaultConfig()),
            $config
        );
    }

    /**
     * Default config.
     */
    protected static function getBundleDefaultConfig()
    {
        return array();
    }
}
