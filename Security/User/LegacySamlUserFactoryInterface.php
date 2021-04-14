<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;

/**
 * @deprecated since 2.1
 */
interface LegacySamlUserFactoryInterface
{
    public function createUser(SamlTokenInterface $token);
}
