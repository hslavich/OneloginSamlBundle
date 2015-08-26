<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class SamlFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->addOption('username_attribute', 'uid');
        $this->addOption('login_check', '/saml/acs');
        $this->addOption('login_path', '/saml/login');
        $this->addOption('user_factory');
        $this->addOption('persist_user', false);
    }

    /**
     * Defines the position at which the provider is called.
     * Possible values: pre_auth, form, http, and remember_me.
     *
     * @return string
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'saml';
    }

    protected function getListenerId()
    {
        return 'hslavich_onelogin_saml.saml_listener';
    }

    /**
     * Subclasses must return the id of a service which implements the
     * AuthenticationProviderInterface.
     *
     * @param ContainerBuilder $container
     * @param string $id The unique id of the firewall
     * @param array $config The options array for this listener
     * @param string $userProviderId The id of the user provider
     *
     * @return string never null, the id of the authentication provider
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.saml.'.$id;
        $definition = $container->setDefinition($providerId, new DefinitionDecorator('hslavich_onelogin_saml.saml_provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->addArgument(array(
                 'persist_user' => $config['persist_user']
            ))
            ->addTag('hslavich.saml_provider')
        ;

        if ($config['user_factory']) {
            $definition->addMethodCall('setUserFactory', array(new Reference($config['user_factory'])));
        }

        return $providerId;
     }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);
        $this->createLogoutHandler($container, $id, $config);

        return $listenerId;
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        $entryPointId = 'security.authentication.form_entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('security.authentication.form_entry_point'))
            ->addArgument(new Reference('security.http_utils'))
            ->addArgument($config['login_path'])
            ->addArgument($config['use_forward'])
        ;

        return $entryPointId;
    }

    protected function createLogoutHandler($container, $id, $config)
    {
        if ($container->hasDefinition('security.logout_listener.'.$id)) {
            $logoutListener = $container->getDefinition('security.logout_listener.'.$id);
            $samlListenerId = 'hslavich_onelogin_saml.saml_logout';
            $container
                ->setDefinition($samlListenerId, new DefinitionDecorator('saml.security.http.logout'))
                ->replaceArgument(2, array_intersect_key($config, $this->options));
            $logoutListener->addMethodCall('addHandler', array(new Reference($samlListenerId)));
        }
    }

}
