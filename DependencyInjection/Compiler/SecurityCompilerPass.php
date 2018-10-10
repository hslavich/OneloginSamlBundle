<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Hslavich\OneloginSamlBundle\DependencyInjection\Configuration;

class SecurityCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig('hslavich_onelogin_saml');
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $emDefinition='doctrine.orm.default_entity_manager';
        if(!empty($config['security']) && isset($config['security']['entityManagerName'])){
            $emDefinition='doctrine.orm.'.$config['security']['entityManagerName'].'_entity_manager';
        }
       
        if ($container->hasDefinition($emDefinition)) {
            foreach ($container->findTaggedServiceIds('hslavich.saml_provider') as $id => $tags) {
                $container->getDefinition($id)->addMethodCall('setEntityManager', array(new Reference($emDefinition)));
            }
        }
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($configuration, $configs);
    }
}
