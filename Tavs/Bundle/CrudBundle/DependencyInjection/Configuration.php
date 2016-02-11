<?php

namespace Tavs\Bundle\CrudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tavs_crud');

        $rootNode
            ->children()
                ->arrayNode('resources')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('entity')
                                ->children()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('alias')->end()
                                ->end()
                            ->end()
                            ->scalarNode('datatable')->end()
                            ->arrayNode('form')
                                ->children()
                                    ->scalarNode('type')->isRequired()->end()
                                    ->arrayNode('options')
                                        ->prototype('scalar')->end()
                                        ->useAttributeAsKey('name')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('routes')
                                ->children()
                                    ->scalarNode('index')->isRequired()->end()
                                    ->scalarNode('edit')->isRequired()->end()
                                    ->scalarNode('create')->isRequired()->end()
                                    ->scalarNode('save')->isRequired()->end()
                                ->end()
                            ->end()
                            ->arrayNode('templates')
                                ->children()
                                    ->scalarNode('index')->end()
                                    ->scalarNode('form')->end()
                                    ->scalarNode('datatable_theme')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
