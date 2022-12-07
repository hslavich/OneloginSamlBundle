<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class HslavichOneloginSamlExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $container->setParameter('hslavich_onelogin_saml.settings', $config['onelogin_settings']);
        $container->setParameter('hslavich_onelogin_saml.idp_parameter_name', $config['idp_parameter_name']);
        $container->setParameter('hslavich_onelogin_saml.use_proxy_vars', $config['use_proxy_vars']);

        if (!empty($config['entityManagerName'])) {
            $container->setParameter('hslavich_onelogin_saml.entity_manager', $config['entityManagerName']);
        }
    }
}
