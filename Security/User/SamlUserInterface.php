<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

interface SamlUserInterface
{
    /**
     * Set SAML attributes in user object.
     *
     * @param array $attributes
     */
    public function setSamlAttributes(array $attributes);
}
