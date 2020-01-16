<?php

namespace Hslavich\OneloginSamlBundle\Security\Utils;

use OneLogin\Saml2\Auth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\HttpUtils;

class OneLoginAuthRegistry
{
    /**
     * @var Auth[]
     */
    protected $idpAuth = array();

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    public function __construct(HttpUtils $httpUtils)
    {
        $this->httpUtils = $httpUtils;
    }

    /**
     * @param Auth $idpAuth
     */
    public function addIdpAuth($name, Auth $idpAuth)
    {
        $this->idpAuth[$name] = $idpAuth;
    }

    /**
     * @param Auth $idpAuth
     */
    public function getIdpAuth($name)
    {
        if (!isset($this->idpAuth[$name])) {
            throw new \InvalidArgumentException(sprintf('Undefined IDP "%s"', $name));
        }

        return $this->idpAuth[$name];
    }

    /**
     * @return Auth
     */
    public function getAuthFromSession(SessionInterface $session)
    {
        return $this->getIdpAuth($session->get('auth_id'));
    }
}
