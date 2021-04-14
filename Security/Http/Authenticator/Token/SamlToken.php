<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Token;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class SamlToken extends PostAuthenticationToken implements SamlTokenInterface
{
    public function __construct(UserInterface $user, string $firewallName, array $roles, array $attributes)
    {
        parent::__construct($user, $firewallName, $roles);

        $this->setAttributes($attributes);
    }
}
