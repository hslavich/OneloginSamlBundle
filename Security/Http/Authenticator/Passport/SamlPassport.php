<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Passport;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SamlPassport extends SelfValidatingPassport implements SamlPassportInterface
{
    private $attributes;

    public function __construct(UserInterface $user, array $attributes, array $badges = [])
    {
        parent::__construct($user, $badges);

        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
