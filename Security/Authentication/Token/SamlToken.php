<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * @deprecated since 2.1
 */
class SamlToken extends AbstractToken implements SamlTokenInterface
{
    /**
     * @var string
     */
    protected $idpName;

    public function getCredentials()
    {
        return null;
    }

    public function getIdpName()
    {
        return $this->idpName;
    }

    /**
     * @param string $idpName
     */
    public function setIdpName($idpName)
    {
        $this->idpName = $idpName;
    }
}
