<?php

namespace Hslavich\OneloginSamlBundle\Security\Logout;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\Utils\OneLoginAuthRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class SamlLogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var OneLoginAuthRegistry
     */
    private $authRegistry;

    public function __construct(OneLoginAuthRegistry $authRegistry)
    {
        $this->authRegistry = $authRegistry;
    }

    /**
     * This method is called by the LogoutListener when a user has requested
     * to be logged out. Usually, you would unset session variables, or remove
     * cookies, etc.
     *
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        if (!$token instanceof SamlTokenInterface) {
            return;
        }

        $auth = $this->authRegistry->getIdpAuth($token->getIdpName());
        if (null === $auth) {
            throw new NotFoundHttpException('Auth service not found');
        }

        try {
            $auth->processSLO();
        } catch (\OneLogin\Saml2\Error $e) {
            $sessionIndex = $token->hasAttribute('sessionIndex') ? $token->getAttribute('sessionIndex') : null;
            $auth->logout(null, array(), $token->getUsername(), $sessionIndex);
        }
    }
}
