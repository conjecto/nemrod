<?php
namespace Devyn\Bundle\RALBundle\Tests\DependencyInjection;

use Devyn\Bundle\RALBundle\DependencyInjection\Configuration;

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

        //var_dump($children);

        $this->assertArrayHasKey('namespaces', $children);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\PrototypedArrayNode', $children['namespaces']);

        $this->assertArrayHasKey('endpoints', $children);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\PrototypedArrayNode', $children['endpoints']);

        $this->assertArrayHasKey('default_endpoint', $children);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ScalarNode', $children['default_endpoint']);
    }

}