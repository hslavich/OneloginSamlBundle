<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory;

use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\SamlAuthenticationSuccessHandler;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
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
            $this->options['success_handler'] = SamlAuthenticationSuccessHandler::class;
        }
        $this->defaultFailureHandlerOptions['login_path'] = '/saml/login';
    }

    /**
     * Defines the position at which the provider is called.
     * Possible values: pre_auth, form, http, and remember_me.
     *
     * @return string
     */
    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'saml';
    }

    protected function getListenerId(): string
    {
        return SamlListener::class;
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
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId): string
    {
        $providerId = 'security.authentication.provider.saml.'.$id;
        $definition = $container->setDefinition($providerId, new ChildDefinition(SamlProvider::class))
            ->setArguments([
                new Reference($userProviderId),
                [
                    'persist_user' => $config['persist_user']
                ],
            ])
            ->addTag('hslavich.saml_provider')
        ;

        if ($config['user_factory']) {
            $definition->addMethodCall('setUserFactory', [new Reference($config['user_factory'])]);
        }

        $factoryId = $config['token_factory'] ?: SamlTokenFactoryInterface::class;
        $definition->addMethodCall('setTokenFactory', [new Reference($factoryId)]);

        return $providerId;
     }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint): ?string
    {
        $entryPointId = 'security.authentication.form_entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new ChildDefinition('security.authentication.form_entry_point'))
            ->addArgument(new Reference('security.http_utils'))
            ->addArgument($config['login_path'])
            ->addArgument($config['use_forward'])
        ;

        return $entryPointId;
    }
}
