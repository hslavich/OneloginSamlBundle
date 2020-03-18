<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Token;

interface SamlTokenFactoryInterface
{
    /**
     * Creates a new SAML Token object.
     *
     * @param mixed $user
     *
     * @return SamlTokenInterface
     */
    public function createToken($user, array $attributes, array $roles);
}
