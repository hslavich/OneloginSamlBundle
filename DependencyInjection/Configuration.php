<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('hslavich_saml_sp');

        $rootNode
            ->children()
                ->arrayNode('idp')
                    ->children()
                        ->scalarNode('entityId')->end()
                        ->scalarNode('x509cert')->end()
                        ->arrayNode('singleSignOnService')
                            ->children()
                                ->scalarNode('url')->end()
                                ->scalarNode('binding')->end()
                            ->end()
                        ->end()
                        ->arrayNode('singleLogoutService')
                            ->children()
                                ->scalarNode('url')->end()
                                ->scalarNode('binding')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('sp')
                    ->children()
                        ->scalarNode('entityId')->end()
                        ->arrayNode('assertionConsumerService')
                            ->children()
                                ->scalarNode('url')->end()
                                ->scalarNode('binding')->end()
                            ->end()
                        ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
