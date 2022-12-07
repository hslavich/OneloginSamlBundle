<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Controller\SamlController;
use Hslavich\OneloginSamlBundle\EventListener\Security\SamlLogoutListener;
use Hslavich\OneloginSamlBundle\EventListener\User\UserCreatedListener;
use Hslavich\OneloginSamlBundle\EventListener\User\UserModifiedListener;
use Hslavich\OneloginSamlBundle\Idp\IdpResolver;
use Hslavich\OneloginSamlBundle\Idp\IdpResolverInterface;
use Hslavich\OneloginSamlBundle\OneLogin\AuthArgumentResolver;
use Hslavich\OneloginSamlBundle\OneLogin\AuthFactory;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistry;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistryInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;
use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use Hslavich\OneloginSamlBundle\Security\Http\Authentication\SamlAuthenticationSuccessHandler;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\SamlAuthenticator;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->load('Hslavich\\OneloginSamlBundle\\Security\\', __DIR__.'/../../Security/');


    $services->set(SamlController::class);

    $services->set(AuthFactory::class)
        ->args([
            service(RequestStack::class),
        ])

        ->set(AuthRegistryInterface::class, AuthRegistry::class)

        ->set(IdpResolverInterface::class, IdpResolver::class)
        ->args([
            param('hslavich_onelogin_saml.idp_parameter_name'),
        ])

        ->set(SamlLogoutListener::class)
        ->tag('kernel.event_listener', ['event' => LogoutEvent::class])
        ->args([
            service(IdpResolverInterface::class),
            service(AuthRegistryInterface::class),
        ])

        ->set(AuthArgumentResolver::class)
        ->args([
            service(AuthRegistryInterface::class),
            service(IdpResolverInterface::class),
        ]);

    $services->set(\Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener::class)
        ->parent(service('security.authentication.listener.abstract'))
        ->abstract()
        ->call('setAuthRegistry', [service(AuthRegistryInterface::class)])
        ->call('setIdpResolver', [service(IdpResolverInterface::class)])
    ;

    $services->set(SamlAuthenticator::class)
        ->tag('monolog.logger', ['channel' => 'security'])
        ->args([
            /* 0 */ abstract_arg('security.http_utils'),
            /* 1 */ abstract_arg('user provider'),
            /* 2 */ service(IdpResolverInterface::class),
            /* 3 */ service(AuthRegistryInterface::class),
            /* 4 */ abstract_arg('success handler'),
            /* 5 */ abstract_arg('failure handler'),
            /* 6 */ abstract_arg('options'),
            /* 7 */ null,  // user factory
            /* 8 */ service(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class)->nullOnInvalid(),
            /* 9 */ service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            /* 10 */ param('hslavich_onelogin_saml.idp_parameter_name'),
            /* 11 */ param('hslavich_onelogin_saml.use_proxy_vars'),
        ])
    ;

    $services->set(UserCreatedListener::class)
        ->abstract()
        ->args([
            service(EntityManagerInterface::class)->nullOnInvalid(),
            false,  // persist_user
        ])
    ;
    $services->set(UserModifiedListener::class)
        ->abstract()
        ->args([
            service(EntityManagerInterface::class)->nullOnInvalid(),
            false,  // persist_user
        ])
    ;

    $deprecatedAliases = [
        'hslavich_onelogin_saml.user_provider' => SamlUserProvider::class,
        'hslavich_onelogin_saml.saml_provider' => SamlProvider::class,
        'hslavich_onelogin_saml.saml_token_factory' => SamlTokenFactory::class,
        'hslavich_onelogin_saml.saml_authentication_success_handler' => SamlAuthenticationSuccessHandler::class,
        'hslavich_onelogin_saml.saml_listener' => SamlListener::class,
        'hslavich_onelogin_saml.saml_logout_listener' => SamlLogoutListener::class,
    ];
    foreach ($deprecatedAliases as $alias => $class) {
        $services->alias($alias, $class)->deprecate('hslavich/oneloginsaml-bundle', '2.1', '');
    }
};
