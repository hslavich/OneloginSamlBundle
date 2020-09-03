<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Token;

/**
 * @deprecated since 2.1
 */
interface SamlTokenFactoryInterface
{
    /**
     * Creates a new SAML Token object.
     *
     * @param mixed $user
     * @param array $attributes
     * @param array $roles
     *
     * @return SamlTokenInterface
     */
    public function createToken($user, array $attributes, array $roles);
}
