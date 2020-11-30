<?php

namespace Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory;

use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use Hslavich\OneloginSamlBundle\Security\Http\Authentication\SamlAuthenticationSuccessHandler;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\SamlAuthenticator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\HttpUtils;

class SamlFactory implements SecurityFactoryInterface, AuthenticatorFactoryInterface
{
    protected $options = [
        'check_path' => 'saml_acs',
        'use_forward' => false,
        'require_previous_session' => false,
        'login_path' => 'saml_login',

        'username_attribute' => null,
        'use_attribute_friendly_name' => false,
        'user_factory' => null,
        'token_factory' => null,
        'persist_user' => false,
        'success_handler' => SamlAuthenticationSuccessHandler::class,
    ];

    protected $defaultSuccessHandlerOptions = [
        'always_use_default_target_path' => false,
        'default_target_path' => '/',
        'login_path' => 'saml_login',
        'target_path_parameter' => '_target_path',
        'use_referer' => false,
    ];

    protected $defaultFailureHandlerOptions = [
        'failure_path' => null,
        'failure_forward' => false,
        'login_path' => 'saml_login',
        'failure_path_parameter' => '_failure_path',
    ];

    public function addConfiguration(NodeDefinition $node): void
    {
        $builder = $node->children();

        $builder
            ->scalarNode('provider')->end()
            ->booleanNode('remember_me')->defaultTrue()->end()
            ->scalarNode('success_handler')->end()
            ->scalarNode('failure_handler')->end()
        ;

        foreach (array_merge($this->options, $this->defaultSuccessHandlerOptions, $this->defaultFailureHandlerOptions) as $name => $default) {
            if (\is_bool($default)) {
                $builder->booleanNode($name)->defaultValue($default);
            } else {
                $builder->scalarNode($name)->defaultValue($default);
            }
        }
    }

    final public function addOption(string $name, $default = null): void
    {
        $this->options[$name] = $default;
    }

    public function create(ContainerBuilder $container, string $id, array $config, string $userProviderId, ?string $defaultEntryPointId): array
    {
        trigger_deprecation('hslavich/oneloginsaml-bundle', '2.1', 'Usage of security authentication listener is deprecated, option "security.enable_authenticator_manager" should be set to true.');

        $authProviderId = $this->createAuthProvider($container, $id, $config, $userProviderId);

        $listenerId = $this->createListener($container, $id, $config);

        // add remember-me aware tag if requested
        if ($config['remember_me']) {
            $container
                ->getDefinition($listenerId)
                ->addTag('security.remember_me_aware', ['id' => $id, 'provider' => $userProviderId])
            ;
        }

        $entryPointId = $this->createEntryPoint($container, $id, $config);

        return [$authProviderId, $listenerId, $entryPointId];
    }

    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'saml';
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticatorId = 'security.authenticator.saml.'.$firewallName;
        $authenticator = (new ChildDefinition(SamlAuthenticator::class))
            ->addTag('hslavich.saml_authenticator')
            ->replaceArgument(0, new Reference(HttpUtils::class))
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(3, new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->replaceArgument(4, new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->replaceArgument(5, array_intersect_key($config, $this->options))
        ;

        if ($config['user_factory']) {
            $authenticator->replaceArgument(6, new Reference($config['user_factory']));
        }

        $container->setDefinition($authenticatorId, $authenticator);

        return $authenticatorId;
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId): string
    {
        $providerId = 'security.authentication.provider.saml.'.$id;
        $definition = $container->setDefinition($providerId, new ChildDefinition(SamlProvider::class))
            ->addTag('hslavich.saml_provider')
            ->setArguments([
                new Reference($userProviderId),
                [
                    'persist_user' => $config['persist_user'],
                ],
            ])
        ;

        if ($config['user_factory']) {
            $definition->addMethodCall('setUserFactory', [new Reference($config['user_factory'])]);
        }

        $factoryId = $config['token_factory'] ?: SamlTokenFactoryInterface::class;
        $definition->addMethodCall('setTokenFactory', [new Reference($factoryId)]);

        return $providerId;
    }

    protected function createListener(ContainerBuilder $container, string $id, array $config): string
    {
        $listenerId = $this->getListenerId();
        $listener = (new ChildDefinition($listenerId))
            ->replaceArgument(4, $id)
            ->replaceArgument(5, new Reference($this->createAuthenticationSuccessHandler($container, $id, $config)))
            ->replaceArgument(6, new Reference($this->createAuthenticationFailureHandler($container, $id, $config)))
            ->replaceArgument(7, array_intersect_key($config, $this->options))
        ;

        $listenerId .= '.'.$id;
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }

    protected function getListenerId(): string
    {
        return SamlListener::class;
    }

    protected function createEntryPoint(ContainerBuilder $container, string $id, array $config): ?string
    {
        $entryPointId = 'security.authentication.form_entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new ChildDefinition('security.authentication.form_entry_point'))
            ->addArgument(new Reference(HttpUtils::class))
            ->addArgument($config['login_path'])
            ->addArgument($config['use_forward'])
        ;

        return $entryPointId;
    }

    protected function createAuthenticationSuccessHandler(ContainerBuilder $container, string $id, array $config): string
    {
        $successHandlerId = $this->getSuccessHandlerId($id);
        $options = array_intersect_key($config, $this->defaultSuccessHandlerOptions);

        $successHandler = $container->setDefinition($successHandlerId, new ChildDefinition('security.authentication.custom_success_handler'));
        $successHandler->replaceArgument(0, new Reference($config['success_handler']));
        $successHandler->replaceArgument(1, $options);
        $successHandler->replaceArgument(2, $id);

        return $successHandlerId;
    }

    protected function createAuthenticationFailureHandler(ContainerBuilder $container, string $id, array $config): string
    {
        $id = $this->getFailureHandlerId($id);
        $options = array_intersect_key($config, $this->defaultFailureHandlerOptions);

        if (isset($config['failure_handler'])) {
            $failureHandler = $container->setDefinition($id, new ChildDefinition('security.authentication.custom_failure_handler'));
            $failureHandler->replaceArgument(0, new Reference($config['failure_handler']));
            $failureHandler->replaceArgument(1, $options);
        } else {
            $failureHandler = $container->setDefinition($id, new ChildDefinition('security.authentication.failure_handler'));
            $failureHandler->addMethodCall('setOptions', [$options]);
        }

        return $id;
    }

    protected function getSuccessHandlerId(string $id): string
    {
        return 'security.authentication.success_handler.'.$id.'.'.str_replace('-', '_', $this->getKey());
    }

    protected function getFailureHandlerId(string $id): string
    {
        return 'security.authentication.failure_handler.'.$id.'.'.str_replace('-', '_', $this->getKey());
    }
}
