<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Passport;

use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;

interface SamlPassportInterface extends UserPassportInterface
{
    public function getAttributes(): array;
}
