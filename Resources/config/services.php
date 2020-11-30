<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

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

    $services->set(\Hslavich\OneloginSamlBundle\Controller\SamlController::class);

    $services->set(\Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener::class)
        ->parent(service('security.authentication.listener.abstract'))
        ->abstract()
        ->call('setOneLoginAuth', [service(\OneLogin\Saml2\Auth::class)])
    ;

    $services->set(\Hslavich\OneloginSamlBundle\Security\Http\Authenticator\SamlAuthenticator::class)
        ->tag('monolog.logger', ['channel' => 'security'])
        ->args([
            /* 0 */ abstract_arg('security.http_utils'),
            /* 1 */ abstract_arg('user provider'),
            /* 2 */ service(\OneLogin\Saml2\Auth::class),
            /* 3 */ abstract_arg('success handler'),
            /* 4 */ abstract_arg('failure handler'),
            /* 5 */ abstract_arg('options'),
            /* 6 */ null,  // user factory
            /* 7 */ service(\Doctrine\ORM\EntityManagerInterface::class)->nullOnInvalid(),
            /* 8 */ service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
        ])
    ;

    $services->set(\Hslavich\OneloginSamlBundle\EventListener\Security\SamlLogoutListener::class)
        ->tag('kernel.event_listener', ['event' => \Symfony\Component\Security\Http\Event\LogoutEvent::class])
    ;

    $deprecatedAliases = [
        'hslavich_onelogin_saml.user_provider' => \Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider::class,
        'hslavich_onelogin_saml.saml_provider' => \Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider::class,
        'hslavich_onelogin_saml.saml_token_factory' => \Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory::class,
        'hslavich_onelogin_saml.saml_authentication_success_handler' => \Hslavich\OneloginSamlBundle\Security\Http\Authentication\SamlAuthenticationSuccessHandler::class,
        'hslavich_onelogin_saml.saml_listener' => \Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener::class,
        'hslavich_onelogin_saml.saml_logout_listener' => \Hslavich\OneloginSamlBundle\EventListener\Security\SamlLogoutListener::class,
    ];
    foreach ($deprecatedAliases as $alias => $class) {
        $services->alias($alias, $class)->deprecate('hslavich/oneloginsaml-bundle', '2.1', '');
    }
};
