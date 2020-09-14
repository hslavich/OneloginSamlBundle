<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\EventListener\Security;

use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Token\SamlTokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SamlLogoutListener
{
    protected $samlAuth;

    public function __construct(\OneLogin\Saml2\Auth $samlAuth)
    {
        $this->samlAuth = $samlAuth;
    }

    public function __invoke(LogoutEvent $event)
    {
        $token = $event->getToken();
        if (!$token instanceof SamlTokenInterface) {
            return;
        }

        try {
            $this->samlAuth->processSLO();
        } catch (\OneLogin\Saml2\Error $e) {
            if (!empty($this->samlAuth->getSLOurl())) {
                $sessionIndex = $token->hasAttribute('sessionIndex') ? $token->getAttribute('sessionIndex') : null;
                $this->samlAuth->logout(null, array(), $token->getUsername(), $sessionIndex);
            }
        }
    }
}
