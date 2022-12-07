<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\EventListener\Security;

use Hslavich\OneloginSamlBundle\Idp\IdpResolverInterface;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistryInterface;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Token\SamlTokenInterface;
use OneLogin\Saml2\Auth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SamlLogoutListener
{
    protected IdpResolverInterface $idpResolver;
    protected AuthRegistryInterface $authRegistry;

    public function __construct(IdpResolverInterface $idpResolver, $authRegistry)
    {
        $this->idpResolver = $idpResolver;
        $this->authRegistry = $authRegistry;
    }

    public function __invoke(LogoutEvent $event)
    {
        $token = $event->getToken();
        if (!$token instanceof SamlTokenInterface) {
            return;
        }
        $authService = $this->getAuthService($event->getRequest());
        if (!$authService) {
            return;
        }

        try {
            $authService->processSLO();
        } catch (\OneLogin\Saml2\Error $e) {
            if (!empty($authService->getSLOurl())) {
                $sessionIndex = $token->hasAttribute('sessionIndex') ? $token->getAttribute('sessionIndex') : null;
                $authService->logout(null, array(), $token->getUserIdentifier(), $sessionIndex);
            }
        }
    }

    private function getAuthService(Request $request): ?Auth
    {
        $idp = $this->idpResolver->resolve($request);
        if (!$idp) {
            return $this->authRegistry->getDefaultService();
        }

        if ($this->authRegistry->hasService($idp)) {
            return $this->authRegistry->getService($idp);
        }

        return null;
    }
}
