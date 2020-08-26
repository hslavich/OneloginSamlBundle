<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory;

use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;

class SamlUserProviderFactory implements UserProviderFactoryInterface
{
    protected $defaultRoles = ['ROLE_USER'];

    public function create(ContainerBuilder $container, $id, $config): void
    {
        $container
            ->setDefinition($id, new ChildDefinition(SamlUserProvider::class))
            ->addArgument($config['user_class'])
            ->addArgument($config['default_roles'])
        ;
    }

    public function getKey(): string
    {
        return 'saml';
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        $builder
            ->children()
                ->scalarNode('user_class')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('default_roles')
                    ->prototype('scalar')->end()
                    ->defaultValue($this->defaultRoles)
                ->end()
            ->end()
        ;
    }
}
