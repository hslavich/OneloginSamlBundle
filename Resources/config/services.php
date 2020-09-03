<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hslavich\OneloginSamlBundle\EventListener\Security\SamlLogoutListener;
use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;
use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use Hslavich\OneloginSamlBundle\Security\Http\Authentication\SamlAuthenticationSuccessHandler;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\SamlAuthenticator;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\Security\Http\Event\LogoutEvent;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->load('Hslavich\\OneloginSamlBundle\\Security\\', __DIR__.'/../../Security/');

    $services->set(\OneLogin\Saml2\Auth::class)
        ->args(['%hslavich_onelogin_saml.settings%'])
    ;

    $services->set(SamlListener::class)
        ->parent(service('security.authentication.listener.abstract'))
        ->abstract()
        ->call('setOneLoginAuth', [service(\OneLogin\Saml2\Auth::class)])
    ;

    $services->set(SamlAuthenticator::class)
        ->tag('monolog.logger', ['channel' => 'security'])
        ->args([
            /* 0 */ new AbstractArgument('security.http_utils'),
            /* 1 */ new AbstractArgument('user provider'),
            /* 2 */ service(\OneLogin\Saml2\Auth::class),
            /* 3 */ new AbstractArgument('success handler'),
            /* 4 */ new AbstractArgument('failure handler'),
            /* 5 */ new AbstractArgument('options'),
            /* 6 */ new AbstractArgument('user factory'),
        ])
    ;

    $services->set(SamlLogoutListener::class)
        ->tag('kernel.event_listener', ['event' => LogoutEvent::class])
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
