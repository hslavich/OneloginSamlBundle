<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Passport;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SamlPassport extends SelfValidatingPassport implements SamlPassportInterface
{
    private $attributes;

    public function __construct(UserBadge $userBadge, array $attributes, array $badges = [])
    {
        parent::__construct($userBadge, $badges);

        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
