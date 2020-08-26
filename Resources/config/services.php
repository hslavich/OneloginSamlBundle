<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Hslavich\OneloginSamlBundle\EventListener\Security\SamlLogoutListener;
use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
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

    $services->set(SamlLogoutListener::class)
        ->tag('kernel.event_listener', ['event' => LogoutEvent::class])
    ;
};
