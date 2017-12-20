<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ChildDefinition;

class SamlUserProviderFactory implements UserProviderFactoryInterface
{
    protected $defaultRoles = array('ROLE_USER');

    public function create(ContainerBuilder $container, $id, $config)
    {
        $definitionClassname = $this->getDefinitionClassname();
        $container
            ->setDefinition($id, new $definitionClassname('hslavich_onelogin_saml.user_provider'))
            ->addArgument($config['user_class'])
            ->addArgument($config['default_roles'])
        ;
    }

    public function getKey()
    {
        return 'saml';
    }

    public function addConfiguration(NodeDefinition $builder)
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

    private function getDefinitionClassname()
    {
        return class_exists(ChildDefinition::class) ? ChildDefinition::class : DefinitionDecorator::class;
    }
}
