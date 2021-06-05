<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Compiler;

use Hslavich\OneloginSamlBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SecurityCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig('hslavich_onelogin_saml');
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (!empty($config['entityManagerName'])) {
            $this->setEntityManagerForUserListeners($container, $config['entityManagerName']);
        }
    }

    private function setEntityManagerForUserListeners(ContainerBuilder $container, $entityManagerName): void
    {
        $emDefinition = 'doctrine.orm.'.$entityManagerName.'_entity_manager';
        if (!$container->hasDefinition($emDefinition)) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds('hslavich.saml_user_listener')) as $id) {
            $listenerDefinition = $container->getDefinition($id);
            $listenerDefinition->replaceArgument(0, new Reference($emDefinition));
        }
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor())->processConfiguration($configuration, $configs);
    }
}
