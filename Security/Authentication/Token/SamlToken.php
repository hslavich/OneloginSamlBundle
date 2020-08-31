<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * @deprecated since 2.1
 */
class SamlToken extends AbstractToken implements SamlTokenInterface
{
    public function getCredentials()
    {
        return null;
    }
}
