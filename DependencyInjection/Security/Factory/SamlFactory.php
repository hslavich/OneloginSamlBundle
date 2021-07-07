<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory;

use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;
use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class SamlFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->addOption('username_attribute');
        $this->addOption('use_attribute_friendly_name', false);
        $this->addOption('check_path', '/saml/acs');
        $this->addOption('user_factory');
        $this->addOption('token_factory');
        $this->addOption('persist_user', false);

        if (!isset($this->options['success_handler'])) {
            $this->options['success_handler'] = 'hslavich_onelogin_saml.saml_authentication_success_handler';
        }
        $this->defaultFailureHandlerOptions['login_path'] = '/saml/login';
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

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        $this->createUserListeners($container, $id, $config);

        return parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);
    }

    protected function createUserListeners(ContainerBuilder $container, $id, $config)
    {
        $definitionClassname = $this->getDefinitionClassname();

        $container->setDefinition('hslavich_onelogin_saml.user_created_listener'.$id, new $definitionClassname('hslavich_onelogin_saml.user_created_listener'))
            ->replaceArgument(1, $config['persist_user'])
            ->addTag('hslavich.saml_user_listener')
            ->addTag('kernel.event_listener', ['event' => UserCreatedEvent::NAME, 'method' => 'onUserCreated'])
        ;

        $container->setDefinition('hslavich_onelogin_saml.user_modified_listener'.$id, new $definitionClassname('hslavich_onelogin_saml.user_modified_listener'))
            ->replaceArgument(1, $config['persist_user'])
            ->addTag('hslavich.saml_user_listener')
            ->addTag('kernel.event_listener', ['event' => UserModifiedEvent::NAME, 'method' => 'onUserModified'])
        ;
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
        $definitionClassname = $this->getDefinitionClassname();
        $definition = $container->setDefinition($providerId, new $definitionClassname('hslavich_onelogin_saml.saml_provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE))
        ;

        if ($config['user_factory']) {
            $definition->addMethodCall('setUserFactory', array(new Reference($config['user_factory'])));
        }

        $factoryId = $config['token_factory'] ?: 'hslavich_onelogin_saml.saml_token_factory';
        $definition->addMethodCall('setTokenFactory', array(new Reference($factoryId)));

        return $providerId;
     }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);
        $this->createLogoutHandler($container, $id);

        return $listenerId;
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        $entryPointId = 'security.authentication.form_entry_point.'.$id;
        $definitionClassname = $this->getDefinitionClassname();
        $container
            ->setDefinition($entryPointId, new $definitionClassname('security.authentication.form_entry_point'))
            ->addArgument(new Reference('security.http_utils'))
            ->addArgument($config['login_path'])
            ->addArgument($config['use_forward'])
        ;

        return $entryPointId;
    }

    protected function createLogoutHandler($container, $id)
    {
        if ($container->hasDefinition('security.logout_listener.'.$id)) {
            $logoutListener = $container->getDefinition('security.logout_listener.'.$id);
            $logoutListener->addMethodCall('addHandler', array(new Reference('hslavich_onelogin_saml.saml_logout')));
        }
    }

    private function getDefinitionClassname()
    {
        return class_exists(ChildDefinition::class) ? ChildDefinition::class : DefinitionDecorator::class;
    }
}
