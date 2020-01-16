<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection;

use Hslavich\OneloginSamlBundle\Security\Utils\OneLoginAuthRegistry;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HslavichOneloginSamlExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('hslavich_onelogin_saml.settings', $config);
        $this->loadIdentityProviders($config, $container);
    }

    private function loadIdentityProviders(array $config, ContainerBuilder $container)
    {
        $idps = $config['idps'];
        unset($config['idps']);

        $registryDef = $container->getDefinition(OneLoginAuthRegistry::class);

        foreach($idps as $id => $idpConfig) {
            $idpServiceId = $this->createAuthDefinition($container, $id, array_merge($config, ['idp' => $idpConfig]));

            $registryDef->addMethodCall('addIdpAuth', [$id, new Reference($idpServiceId)]);
            $this->createLogoutDefinition($container, $id, $idpServiceId);
        }
    }

    private function createAuthDefinition(ContainerBuilder $container, $id, array $config)
    {
        $namespace = 'onelogin_auth';
        $def = new ChildDefinition($namespace);
        $def->replaceArgument(0, $config);

        $serviceId = $namespace . '.' . $id;
        $container->setDefinition($serviceId, $def);

        return $serviceId;
    }

    private function createLogoutDefinition(ContainerBuilder $container, $id, $authId)
    {
        $namespace = 'hslavich_onelogin_saml.saml_logout';
        $def = new ChildDefinition($namespace);
        $def->setArgument(0, new Reference($authId));

        $serviceId = $namespace . '.' . $id;
        $container->setDefinition($serviceId, $def);

        return $serviceId;
    }
}
