<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Provider;

use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;
use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SamlProvider implements AuthenticationProviderInterface
{
    protected $userProvider;
    protected $userFactory;
    protected $tokenFactory;
    protected $eventDispatcher;
    protected $userChecker;

    public function __construct(UserProviderInterface $userProvider, $eventDispatcher, $userChecker)
    {
        $this->userProvider = $userProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->userChecker = $userChecker;
    }

    public function setUserFactory(SamlUserFactoryInterface $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    public function setTokenFactory(SamlTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->retrieveUser($token);

        if ($user) {
            if ($user instanceof SamlUserInterface) {
                $user->setSamlAttributes($token->getAttributes());

                if ($this->eventDispatcher) {
                    if (class_exists('\Symfony\Contracts\EventDispatcher\Event')) {
                        $this->eventDispatcher->dispatch(new UserModifiedEvent($user), UserModifiedEvent::NAME);
                    } else {
                        $this->eventDispatcher->dispatch(UserModifiedEvent::NAME, new UserModifiedEvent($user));
                    }
                }
            }

            $authenticatedToken = $this->tokenFactory->createToken($user, $token->getAttributes(), $user->getRoles());
            $authenticatedToken->setAuthenticated(true);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The authentication failed.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof SamlTokenInterface;
    }

    protected function retrieveUser($token)
    {
        try {
            $user = $this->userProvider->loadUserByUsername($token->getUsername());

            return $this->checkUser($user);
        } catch (UsernameNotFoundException $e) {
            if ($this->userFactory instanceof SamlUserFactoryInterface) {
                $user = $this->generateUser($token);

                return $this->checkUser($user);
            }

            throw $e;
        }
    }

    protected function checkUser($user)
    {
        if ($user && $this->userChecker instanceof UserCheckerInterface) {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        }

        return $user;
    }

    protected function generateUser($token)
    {
        $user = $this->userFactory->createUser($token);

        if ($this->eventDispatcher) {
            if (class_exists('\Symfony\Contracts\EventDispatcher\Event')) {
                $this->eventDispatcher->dispatch(new UserCreatedEvent($user), UserCreatedEvent::NAME);
            } else {
                $this->eventDispatcher->dispatch(UserCreatedEvent::NAME, new UserCreatedEvent($user));
            }
        }

        return $user;
    }
}
