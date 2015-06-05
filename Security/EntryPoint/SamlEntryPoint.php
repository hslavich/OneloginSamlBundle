<?php

namespace Hslavich\OneloginSamlBundle\Security\EntryPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class SamlEntryPoint implements AuthenticationEntryPointInterface
{
    protected $oneLoginAuth;

    /**
     * @param \OneLogin_Saml2_Auth $oneLoginAuth
     */
    public function __construct(\OneLogin_Saml2_Auth $oneLoginAuth)
    {
        $this->oneLoginAuth = $oneLoginAuth;
    }

    /**
     * Starts the authentication scheme.
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $this->oneLoginAuth->login();
    }
}
