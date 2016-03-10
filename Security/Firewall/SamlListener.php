<?php

namespace Hslavich\OneloginSamlBundle\Security\Firewall;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

class SamlListener extends AbstractAuthenticationListener
{
    protected $oneLoginAuth;

    /**
     * @param \OneLogin_Saml2_Auth $oneLoginAuth
     */
    public function setOneLoginAuth(\OneLogin_Saml2_Auth $oneLoginAuth)
    {
        $this->oneLoginAuth = $oneLoginAuth;
    }

    /**
     * Performs authentication.
     *
     * @param Request $request A Request instance
     *
     * @return TokenInterface|Response|null The authenticated token, null if full authentication is not possible, or a Response
     *
     * @throws AuthenticationException if the authentication fails
     */
    protected function attemptAuthentication(Request $request)
    {
        $this->oneLoginAuth->processResponse();
        if ($this->oneLoginAuth->getErrors()) {
            throw new AuthenticationException($this->oneLoginAuth->getLastErrorReason());
        }

        $attributes = $this->oneLoginAuth->getAttributes();
        $token = new SamlToken();
        $token->setAttributes($attributes);

        if (isset($this->options['username_attribute'])) {
            $username = $attributes[$this->options['username_attribute']][0];
        } else {
            $username = $this->oneLoginAuth->getNameId();
        }
        $token->setUser($username);

        return $this->authenticationManager->authenticate($token);
    }
}
