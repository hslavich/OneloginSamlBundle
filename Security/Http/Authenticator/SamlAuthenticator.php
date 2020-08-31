<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Security\Http\Authenticator;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

class SamlAuthenticator extends AbstractAuthenticator
{
    private $httpUtils;
    private $userProvider;
    private $oneLoginAuth;
    private $successHandler;
    private $failureHandler;
    private $options;
    private $userFactory;
    private $logger;
    private $entityManager;

    public function __construct(
        HttpUtils $httpUtils,
        UserProviderInterface $userProvider,
        \OneLogin\Saml2\Auth $oneLoginAuth,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options,
        ?SamlUserFactoryInterface $userFactory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->httpUtils = $httpUtils;
        $this->userProvider = $userProvider;
        $this->oneLoginAuth = $oneLoginAuth;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->options = $options;
        $this->userFactory = $userFactory;
        $this->logger = $logger;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST')
            && $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    public function authenticate(Request $request): PassportInterface
    {
        if (!$request->hasSession()) {
            throw new SessionUnavailableException('This authentication method requires a session.');
        }

        if ($this->options['require_previous_session'] && !$request->hasPreviousSession()) {
            throw new SessionUnavailableException('Your session has timed out, or you have disabled cookies.');
        }

        $this->oneLoginAuth->processResponse();

        if ($this->oneLoginAuth->getErrors()) {
            if (null !== $this->logger) {
                $this->logger->error($this->oneLoginAuth->getLastErrorReason());
            }
            throw new AuthenticationException($this->oneLoginAuth->getLastErrorReason());
        }

        $user = $this->retrieveUser();

        return new SelfValidatingPassport($user);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    protected function retrieveUser(): UserInterface
    {
        $attributes = $this->extractAttributes();
        $username = $this->extractUsername($attributes);

        try {
            $user = $this->userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $exception) {
            if (!$this->userFactory instanceof SamlUserFactoryInterface) {
                throw $exception;
            }

            $user = $this->generateUser($username, $attributes);
        } catch (\Throwable $exception) {
            throw new AuthenticationException('The authentication failed.', 0, $exception);
        }

        if ($user instanceof SamlUserInterface) {
            $user->setSamlAttributes($attributes);
        }

        return $user;
    }

    protected function extractAttributes(): array
    {
        if (isset($this->options['use_attribute_friendly_name']) && $this->options['use_attribute_friendly_name']) {
            $attributes = $this->oneLoginAuth->getAttributesWithFriendlyName();
        } else {
            $attributes = $this->oneLoginAuth->getAttributes();
        }
        $attributes['sessionIndex'] = $this->oneLoginAuth->getSessionIndex();

        return $attributes;
    }

    protected function extractUsername(array $attributes): string
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

        return $this->oneLoginAuth->getNameId();
    }

    protected function generateUser(string $username, array $attributes): UserInterface
    {
        $user = $this->userFactory->createUser($username, $attributes);

        if ($this->options['persist_user'] && $this->entityManager) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }
}