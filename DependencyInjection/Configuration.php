<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function is_array;
use function is_bool;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    const DEFAULT_NAME = 'default';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        if (in_array('getRootNode', get_class_methods(TreeBuilder::class))) {
            $treeBuilder = new TreeBuilder('hslavich_saml_sp');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('hslavich_saml_sp');
        }

        $idp = $rootNode
            ->children()
            ->arrayNode('idp');

        $idps = $rootNode
            ->children()
            ->arrayNode('idps')
                ->useAttributeAsKey('id')
                ->normalizeKeys(false)
                ->arrayPrototype();

        $this->configureIdp($idp);
        $this->configureIdp($idps);

        $rootNode
            ->beforeNormalization()
            ->ifTrue(static function ($v) {
                // Support single IDP for BC
                return is_array($v) && array_key_exists('idp', $v);
            })
            ->then(static function ($v) {
                $v['default_idp'] = isset($v['default_idp']) ? (string) $v['default_idp'] : self::DEFAULT_NAME;
                $v['idps'] = [$v['default_idp'] => $v['idp']];
                unset($v['idp']);

                return $v;
            })
            ->end()
            ->children()
                ->scalarNode('default_idp')->end()
                ->scalarNode('baseurl')->end()
                ->booleanNode('strict')->end()
                ->booleanNode('debug')->end()
                ->arrayNode('sp')
                    ->children()
                        ->scalarNode('entityId')->end()
                        ->scalarNode('NameIDFormat')->end()
                        ->scalarNode('x509cert')->end()
                        ->scalarNode('privateKey')->end()
                        ->arrayNode('assertionConsumerService')
                            ->children()
                                ->scalarNode('url')->end()
                                ->scalarNode('binding')->end()
                            ->end()
                        ->end()
                        ->arrayNode('attributeConsumingService')
                            ->children()
                                ->scalarNode('serviceName')->end()
                                ->scalarNode('serviceDescription')->end()
                                ->arrayNode('requestedAttributes')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')->end()
                                            ->booleanNode('isRequired')->defaultValue(false)->end()
                                            ->scalarNode('nameFormat')->end()
                                            ->scalarNode('friendlyName')->end()
                                            ->arrayNode('attributeValue')->end()
                                        ->end()
                                    ->end()
                                ->end()
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
                ->arrayNode('security')
                    ->children()
                        ->booleanNode('nameIdEncrypted')->end()
                        ->booleanNode('authnRequestsSigned')->end()
                        ->booleanNode('logoutRequestSigned')->end()
                        ->booleanNode('logoutResponseSigned')->end()
                        ->booleanNode('wantMessagesSigned')->end()
                        ->booleanNode('wantAssertionsSigned')->end()
                        ->booleanNode('wantAssertionsEncrypted')->end()
                        ->booleanNode('wantNameId')->end()
                        ->booleanNode('wantNameIdEncrypted')->end()
                        ->variableNode('requestedAuthnContext')
                            ->validate()
                                ->ifTrue(function ($v) {
                                    return !is_bool($v) && !is_array($v);
                                })
                                ->thenInvalid('Must be an array or a bool.')
                            ->end()
                        ->end()
                        ->booleanNode('signMetadata')->end()
                        ->booleanNode('wantXMLValidation')->end()
                        ->booleanNode('lowercaseUrlencoding')->end()
                        ->scalarNode('signatureAlgorithm')->end()
                        ->scalarNode('digestAlgorithm')->end()
                        ->scalarNode('entityManagerName')->end()
                    ->end()
                ->end()
                ->arrayNode('contactPerson')
                    ->children()
                        ->arrayNode('technical')
                            ->children()
                                ->scalarNode('givenName')->end()
                                ->scalarNode('emailAddress')->end()
                            ->end()
                        ->end()
                        ->arrayNode('support')
                            ->children()
                                ->scalarNode('givenName')->end()
                                ->scalarNode('emailAddress')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('organization')
                    ->prototype('array')
                         ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('displayname')->end()
                            ->scalarNode('url')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function configureIdp(ArrayNodeDefinition $node)
    {
        $node
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
                ->scalarNode('certFingerprint')->end()
                ->scalarNode('certFingerprintAlgorithm')->end()
                ->arrayNode('x509certMulti')
                    ->children()
                        ->arrayNode('signing')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('encryption')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
