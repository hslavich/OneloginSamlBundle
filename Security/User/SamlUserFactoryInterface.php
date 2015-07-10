<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Symfony\Component\Security\Core\User\UserInterface;

interface SamlUserFactoryInterface
{
    /**
     * Creates a new User object from SAML Token.
     *
     * @param SamlToken $token SAML token
     * @return UserInterface
     */
    public function createUser(SamlToken $token);
}
