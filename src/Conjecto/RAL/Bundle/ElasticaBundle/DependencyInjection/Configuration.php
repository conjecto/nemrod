<?php

namespace Conjecto\RAL\Bundle\ElasticaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * FrameworkExtension configuration structure.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('elastica');

        $this->addElasticaSearchConfigurationSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds elasticsearch configuration part.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addElasticaSearchConfigurationSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode

            ->children()
            //clients
            ->arrayNode('clients')//node 'clients' is
            ->prototype('array')//an array containing all clients definitions
            ->children() //children for each array entries
            //->arrayNode('servers')//node 'servers' is
            //->prototype('array') //an array containing definitions of all possible servers
            #->children() //children for each array entries are
            ->scalarNode('host')  // a 'host' (scalar) node
            ->end()
            ->scalarNode('port')// a 'port' (scalar) node
            ->end()
            ->end()
            //->end()
            //->end()
            //->end()
            ->end()
            ->end()

            //indexes
            ->arrayNode('indexes')
            ->prototype('array')
            ->children()
            ->scalarNode('client')
            ->end()
            ->arrayNode('types')
            ->prototype('array')
            ->children()
            ->scalarNode('type')
            ->end()
            ->scalarNode('frame')
            ->end()
            ->append($this->getPropertiesNode())
            ->end()
            ->end()
            ->end()->end()
            ->end()
            ->end()
            ->end();
    }

    //below: stuffs taken from foselasticabundle
    /**
     * Returns the array node used for "properties".
     */
    protected function getPropertiesNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('properties');

        $node
            ->useAttributeAsKey('name')
            ->prototype('variable')
            ->treatNullLike(array());

        return $node;
    }
}
