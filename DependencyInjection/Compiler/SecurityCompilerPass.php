<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Compiler;

use Hslavich\OneloginSamlBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class SecurityCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig('hslavich_onelogin_saml');
        $config = $this->processConfiguration(new Configuration(), $configs);

        $emDefinition = 'doctrine.orm.default_entity_manager';
        if (!empty($config['entityManagerName'])) {
            $emDefinition = 'doctrine.orm.'.$config['entityManagerName'].'_entity_manager';
        }

        foreach (array_keys($container->findTaggedServiceIds('hslavich.saml_authenticator')) as $id) {
            $serviceDefinition = $container->getDefinition($id);
            $serviceDefinition->replaceArgument(7, new Reference($emDefinition, ContainerInterface::NULL_ON_INVALID_REFERENCE));
        }

        foreach (array_keys($container->findTaggedServiceIds('hslavich.saml_provider')) as $id) {
            $serviceDefinition = $container->getDefinition($id);
            if ($container->hasDefinition($emDefinition)) {
                $serviceDefinition->addMethodCall('setEntityManager', [new Reference($emDefinition)]);
            }
        }
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor())->processConfiguration($configuration, $configs);
    }
}
