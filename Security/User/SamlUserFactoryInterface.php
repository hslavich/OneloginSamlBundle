<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface SamlUserFactoryInterface
{
    /**
     * Creates a new User object from SAML Token.
     *
     * @param SamlTokenInterface $token SAML token
     * @return UserInterface
     */
    public function createUser(SamlTokenInterface $token);
}
