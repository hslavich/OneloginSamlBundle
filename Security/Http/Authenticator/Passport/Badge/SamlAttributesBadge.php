<?php declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class SamlAttributesBadge implements BadgeInterface
{
    private $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
