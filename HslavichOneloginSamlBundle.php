<?php

namespace Hslavich\OneloginSamlBundle;

use Hslavich\OneloginSamlBundle\DependencyInjection\Compiler\SecurityCompilerPass;
use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlFactory;
use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlUserProviderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HslavichOneloginSamlBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SamlFactory());
        $extension->addUserProviderFactory(new SamlUserProviderFactory());

        $container->addCompilerPass(new SecurityCompilerPass());
    }
}
