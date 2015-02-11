<?php

namespace Devyn\Bundle\RALBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('ral');

        $this->addNamespaceSection($rootNode);
        $this->addEndpointsSection($rootNode);
        $this->addElasticaSearchConfigurationSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addNamespaceSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('namespaces')
                    ->prototype('array')
                        ->beforeNormalization()
                        ->ifString()
                            ->then(function($v) { return array('uri'=> $v); })
                        ->end()
                        ->children()
                            ->scalarNode('uri')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addEndpointsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('endpoints')
                    ->prototype('array')
                        ->beforeNormalization()
                        ->ifString()
                            ->then(function($v) { return array('query_uri'=> $v); })
                        ->end()
                        ->children()
                            ->scalarNode('query_uri')->isRequired()->end()
                            ->scalarNode('update_uri')->end()
                        ->end()
                    ->end()
                ->end()
                // default_endpoint
                ->scalarNode('default_endpoint')->end()
            ->end()
        ;
    }

    /**
     * Adds elasticsearch configuration part
     * @param ArrayNodeDefinition $rootNode
     */
    private function addElasticaSearchConfigurationSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('elasticsearch')
                    ->prototype('array')
                    ->children()
                        ->arrayNode('types')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('class')
                                ->end()
                                ->scalarNode('frame')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ->end();
    }

}
