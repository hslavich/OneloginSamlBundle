<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

interface SamlUserInterface extends UserInterface
{
    /**
     * Set SAML attributes in user object.
     *
     * @param array $attributes
     */
    public function setSamlAttributes(array $attributes);
}
