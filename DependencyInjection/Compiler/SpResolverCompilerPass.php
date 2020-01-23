<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Compiler;

use Hslavich\OneloginSamlBundle\Security\Utils\OneLoginAuthRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class SpResolverCompilerPass implements CompilerPassInterface
{
    const TAG_NAME = 'hslavich_onelogin_saml.onelogin_auth';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(OneLoginAuthRegistry::class)) {
            return;
        }

        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            $definition = $container->getDefinition($id);
            $authConfig = $definition->getArgument(0);
            $spConfig = &$authConfig['sp'];

            foreach ($tags as $tag) {
                $this->resolveIdp($spConfig['entityId'], $tag['name']);
            }
            $definition->replaceArgument(0, $authConfig);
        }
    }

    private function resolveIdp(&$value, $authName)
    {
        $value = str_replace('{idp}', $authName, $value);
    }
}
