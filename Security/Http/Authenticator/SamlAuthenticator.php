<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator;

use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;
use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;
use Hslavich\OneloginSamlBundle\Idp\IdpResolverInterface;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistryInterface;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Passport\Badge\SamlAttributesBadge;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SamlAuthenticator implements InteractiveAuthenticatorInterface, AuthenticationEntryPointInterface
{
    private HttpUtils $httpUtils;
    private UserProviderInterface $userProvider;
    private AuthenticationSuccessHandlerInterface $successHandler;
    private AuthenticationFailureHandlerInterface $failureHandler;
    private array $options;
    private ?SamlUserFactoryInterface $userFactory;
    private ?EventDispatcherInterface $eventDispatcher;
    private ?LoggerInterface $logger;

    private AuthRegistryInterface $authRegistry;
    private IdpResolverInterface $idpResolver;
    private bool $useProxyVars;
    private string $idpParameterName;

    public function __construct(
        HttpUtils $httpUtils,
        UserProviderInterface $userProvider,
        IdpResolverInterface $idpResolver,
        AuthRegistryInterface $authRegistry,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options,
        ?SamlUserFactoryInterface $userFactory,
        ?EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger,
        ?bool $useProxyVars,
        ?string $idpParameterName
    ) {
        $this->httpUtils = $httpUtils;
        $this->userProvider = $userProvider;
        $this->idpResolver = $idpResolver;
        $this->authRegistry = $authRegistry;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->options = $options;
        $this->userFactory = $userFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->useProxyVars = $useProxyVars;
        $this->idpParameterName = $idpParameterName;
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST')
            && $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $uri = $this->httpUtils->generateUri($request, (string) $this->options['login_path']);
        $idp = $this->idpResolver->resolve($request);
        if ($idp) {
            $uri .= '?'.$this->idpParameterName.'='.$idp;
        }
        
        return new RedirectResponse($uri);
    }

    public function authenticate(Request $request): Passport
    {
        if (!$request->hasSession()) {
            throw new SessionUnavailableException('This authentication method requires a session.');
        }

        if ($this->options['require_previous_session'] && !$request->hasPreviousSession()) {
            throw new SessionUnavailableException('Your session has timed out, or you have disabled cookies.');
        }

        $oneLoginAuth = $this->getOneLoginAuth($request);
        Utils::setProxyVars($this->useProxyVars);

        $oneLoginAuth->processResponse();
        
        if ($oneLoginAuth->getErrors()) {
            $errorReason = $oneLoginAuth->getLastErrorReason() ?? 'Undefined OneLogin auth error.';
            if (null !== $this->logger) {
                $this->logger->error($errorReason);
            }
            throw new AuthenticationException($errorReason);
        }

        return $this->createPassport($oneLoginAuth);
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        if (!$passport instanceof Passport) {
            throw new LogicException(sprintf('Passport should be an instance of "%s".', Passport::class));
        }

        return $this->createToken($passport, $firewallName);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        if (!$passport->hasBadge(SamlAttributesBadge::class)) {
            throw new LogicException(sprintf('Passport should contains a "%s" badge.', SamlAttributesBadge::class));
        }

        /** @var SamlAttributesBadge $badge */
        $badge = $passport->getBadge(SamlAttributesBadge::class);

        return new SamlToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles(), $badge->getAttributes());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    public function isInteractive(): bool
    {
        return true;
    }

    protected function createPassport(Auth $oneLoginAuth): Passport
    {
        $attributes = $this->extractAttributes($oneLoginAuth);
        $username = $this->extractUsername($attributes, $oneLoginAuth);

        $userBadge = new UserBadge(
            $username,
            function ($identifier) use ($attributes) {
                try {
                    $user = $this->userProvider->loadUserByIdentifier($identifier);
                } catch (UserNotFoundException $exception) {
                    if (!$this->userFactory instanceof SamlUserFactoryInterface) {
                        throw $exception;
                    }

                    $user = $this->generateUser($identifier, $attributes);
                } catch (\Throwable $exception) {
                    throw new AuthenticationException('The authentication failed.', 0, $exception);
                }

                if ($user instanceof SamlUserInterface) {
                    $user->setSamlAttributes($attributes);
                    if ($this->eventDispatcher) {
                        $this->eventDispatcher->dispatch(new UserModifiedEvent($user));
                    }
                }

                return $user;
            }
        );

        return new SelfValidatingPassport($userBadge, [new SamlAttributesBadge($attributes)]);
    }

    protected function extractAttributes(Auth $oneLoginAuth): array
    {
        if (isset($this->options['use_attribute_friendly_name']) && $this->options['use_attribute_friendly_name']) {
            $attributes = $oneLoginAuth->getAttributesWithFriendlyName();
        } else {
            $attributes = $oneLoginAuth->getAttributes();
        }

        $attributes['sessionIndex'] = $oneLoginAuth->getSessionIndex();

        return $attributes;
    }

    protected function extractUsername(array $attributes, Auth $oneLoginAuth): string
    {
        if (isset($this->options['username_attribute'])) {
            if (!\array_key_exists($this->options['username_attribute'], $attributes)) {
                if (null !== $this->logger) {
                    $this->logger->error('Found attributes: '.print_r($attributes, true));
                }
                throw new \RuntimeException('Attribute "'.$this->options['username_attribute'].'" not found in SAML data');
            }

            return $attributes[$this->options['username_attribute']][0];
        }

        return $oneLoginAuth->getNameId();
    }

    protected function generateUser(string $username, array $attributes): UserInterface
    {
        $user = $this->userFactory->createUser($username, $attributes);

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
        }

        return $user;
    }

    private function getOneLoginAuth(Request $request): Auth
    {
        try {
            $idp = $this->idpResolver->resolve($request);
            $authService = $idp
                ? $this->authRegistry->getService($idp)
                : $this->authRegistry->getDefaultService();
        } catch (\RuntimeException $exception) {
            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage());
            }

            throw new AuthenticationServiceException($exception->getMessage());
        }

        return $authService;
    }
}
