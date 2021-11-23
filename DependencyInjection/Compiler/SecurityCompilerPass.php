<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SecurityCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $emDefinition = 'doctrine.orm.default_entity_manager';
        if ($container->hasParameter('hslavich_onelogin_saml.entity_manager')) {
            $emDefinition = 'doctrine.orm.'.$container->getParameter('hslavich_onelogin_saml.entity_manager').'_entity_manager';
        }

        if (!$container->hasDefinition($emDefinition)) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds('hslavich.saml_user_listener')) as $id) {
            $listenerDefinition = $container->getDefinition($id);
            $listenerDefinition->replaceArgument(0, new Reference($emDefinition));
        }
    }
}
