<?php
declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\OneLogin;

use Hslavich\OneloginSamlBundle\Idp\IdpResolverInterface;
use OneLogin\Saml2\Auth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Yields the OneLogin Auth instance for current request
 * (default or according to an idp parameter).
 */
final class AuthArgumentResolver implements ArgumentValueResolverInterface
{
    private AuthRegistryInterface $authRegistry;
    private IdpResolverInterface $idpResolver;

    public function __construct(
        AuthRegistryInterface $authRegistry,
        IdpResolverInterface $idpResolver
    ) {
        $this->authRegistry = $authRegistry;
        $this->idpResolver = $idpResolver;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === Auth::class;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $idp = $this->idpResolver->resolve($request);
        if ($idp && !$this->authRegistry->hasService($idp)) {
            throw new BadRequestHttpException('There is no OneLogin PHP toolkit settings for IdP "'.$idp.'". See nbgrp_onelogin_saml config ("onelogin_settings" section).');
        }

        try {
            yield $idp
                ? $this->authRegistry->getService($idp)
                : $this->authRegistry->getDefaultService();
        } catch (\RuntimeException $exception) {
            throw new ServiceUnavailableHttpException($exception->getMessage());
        }
    }
}
