<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Compiler;

use Hslavich\OneloginSamlBundle\OneLogin\AuthFactory;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistryInterface;
use OneLogin\Saml2\Auth;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AuthRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $authRegistry = $container->getDefinition(AuthRegistryInterface::class);

        $oneloginSettings = $container->getParameter('hslavich_onelogin_saml.settings');
        if (!\is_array($oneloginSettings)) {
            throw new \UnexpectedValueException('OneLogin settings should be an array.');
        }

        /** @var array $settings */
        foreach ($oneloginSettings as $key => $settings) {
            $authDefinition = new Definition(Auth::class, [$settings]);
            $authDefinition->setFactory(new Reference(AuthFactory::class));
            $authRegistry->addMethodCall('addService', [$key, $authDefinition]);
        }
    }
}
