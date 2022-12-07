<?php
declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Idp;

use Symfony\Component\HttpFoundation\Request;

/**
 * Represents the interface of service that resolves the request IdP.
 */
interface IdpResolverInterface
{
    /**
     * Returns IdP name for specified request.
     */
    public function resolve(Request $request): ?string;
}
