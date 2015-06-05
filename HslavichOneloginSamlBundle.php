<?php

namespace Hslavich\OneloginSamlBundle;

use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HslavichOneloginSamlBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SamlFactory());
    }
}
