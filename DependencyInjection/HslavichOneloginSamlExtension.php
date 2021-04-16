<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection;

use Hslavich\OneloginSamlBundle\DependencyInjection\Compiler\SpResolverCompilerPass;
use Hslavich\OneloginSamlBundle\Security\Utils\OneLoginAuthRegistry;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
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

        $container->setParameter('hslavich_onelogin_saml.settings', $config);
        $container->setParameter('hslavich_onelogin_saml.default_idp_name', $config['default_idp']);
        $this->loadIdentityProviders($config, $container);
    }

    private function loadIdentityProviders(array $config, ContainerBuilder $container)
    {
        $idps = $config['idps'];
        unset($config['idps']);

        $registryDef = $container->getDefinition(OneLoginAuthRegistry::class);

        foreach($idps as $id => $idpConfig) {
            $idpConfig = array_merge(
                $config,
                array('idp' => $idpConfig),
                // Merge custom SP definition of IDP with the template one
                array('sp' => array_merge($config['sp'], isset($idpConfig['sp']) ? $idpConfig['sp'] : array()))
            );

            $idpServiceId = $this->createAuthDefinition(
                $container,
                $id,
                $idpConfig,
                $config['default_idp']
            );

            $registryDef->addMethodCall('addIdpAuth', [$id, new Reference($idpServiceId)]);
            $this->createLogoutDefinition($container, $id, $idpServiceId);
        }
    }

    private function createAuthDefinition(ContainerBuilder $container, $id, array $config, $defaultIdp)
    {
        $def = new ChildDefinition(\OneLogin\Saml2\Auth::class);
        $def->setArgument(0, $config);
        $def->addTag(SpResolverCompilerPass::TAG_NAME, [
            'name' => $id,
        ]);

        $serviceId = 'onelogin_auth.'.$id;
        $container->setDefinition($serviceId, $def);

        if ($id === $defaultIdp) {
            $container->setAlias('onelogin_auth', $serviceId);
        }

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
