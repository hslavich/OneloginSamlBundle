<?php
declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Idp;

use Symfony\Component\HttpFoundation\Request;

final class IdpResolver implements IdpResolverInterface
{
    private string $idpParameterName;

    public function __construct(string $idpParameterName
    ) {
        $this->idpParameterName =$idpParameterName;
    }

    public function resolve(Request $request): ?string
    {
        if ($request->query->has($this->idpParameterName)) {
            return (string) $request->query->get($this->idpParameterName);
        }

        if ($request->attributes->has($this->idpParameterName)) {
            return (string) $request->attributes->get($this->idpParameterName);
        }

        return null;
    }
}
