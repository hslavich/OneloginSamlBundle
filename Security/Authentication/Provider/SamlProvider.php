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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated since 2.1
 */
class SamlProvider implements AuthenticationProviderInterface
{
    protected $userProvider;
    protected $userFactory;
    protected $tokenFactory;
    protected $eventDispatcher;

    protected $userChecker;

    public function __construct(UserProviderInterface $userProvider, ?EventDispatcherInterface $eventDispatcher)
    {
        $this->userProvider = $userProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setUserFactory(SamlUserFactoryInterface $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    public function setTokenFactory(SamlTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setUserChecker(UserCheckerInterface $userChecker)
    {
        $this->userChecker = $userChecker;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->retrieveUser($token);

        if ($user) {
            if ($user instanceof SamlUserInterface) {
                $user->setSamlAttributes($token->getAttributes());
                if ($this->eventDispatcher) {
                    $this->eventDispatcher->dispatch(new UserModifiedEvent($user));
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
            $user = $this->userProvider->loadUserByIdentifier($token->getUserIdentifier());

            return $this->checkUser($user);
        } catch (UserNotFoundException $e) {
            if ($this->userFactory instanceof SamlUserFactoryInterface) {
                $user = $this->generateUser($token);

                return $this->checkUser($user);
            }

            throw $e;
        }
    }

    protected function generateUser($token)
    {
        $user = $this->userFactory->createUser($token);
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
        }

        return $user;
    }

    protected function checkUser($user)
    {
        if ($this->userChecker instanceof UserCheckerInterface) {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);

        }

        return $user;
    }
}
