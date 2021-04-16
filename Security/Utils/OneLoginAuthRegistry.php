<?php

namespace Hslavich\OneloginSamlBundle\Security\Utils;

use OneLogin\Saml2\Auth;

class OneLoginAuthRegistry
{
    /**
     * @var Auth[]
     */
    protected $idpAuth = array();

    /**
     * @var string
     */
    private $defaultIdp;

    public function __construct($defaultIdp)
    {
        $this->defaultIdp = $defaultIdp;
    }

    /**
     * @param Auth $idpAuth
     */
    public function addIdpAuth($name, Auth $idpAuth)
    {
        $this->idpAuth[$name] = $idpAuth;
    }

    /**
     * @param string|null $name
     * @return Auth
     */
    public function getIdpAuth($name = null)
    {
        if (null === $name) {
            $name = $this->defaultIdp;
        }

        if (!isset($this->idpAuth[$name])) {
            throw new \InvalidArgumentException(sprintf('Undefined IDP "%s"', $name));
        }

        return $this->idpAuth[$name];
    }
}
