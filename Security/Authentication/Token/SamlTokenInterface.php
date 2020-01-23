<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface SamlTokenInterface extends TokenInterface
{
    /**
     * @return string
     */
    public function getIdpName();
}
