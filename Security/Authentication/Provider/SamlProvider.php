<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @deprecated since 2.1
 */
class SamlProvider implements AuthenticationProviderInterface
{
    protected $userProvider;

    /**
     * @var SamlUserFactoryInterface
     */
    protected $userFactory;

    /**
     * @var SamlTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    protected $options;

    public function __construct(UserProviderInterface $userProvider, array $options = array())
    {
        $this->userProvider = $userProvider;
        $this->options = array_merge(array(
            'persist_user' => false
        ), $options);
    }

    public function setUserFactory(SamlUserFactoryInterface $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    public function setTokenFactory(SamlTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param SamlTokenInterface $token
     */
    public function authenticate(TokenInterface $token)
    {
        $user = $this->retrieveUser($token);

        if ($user) {
            if ($user instanceof SamlUserInterface) {
                $user->setSamlAttributes($token->getAttributes());
            }

            $authenticatedToken = $this->tokenFactory->createToken(
                $user,
                $token->getAttributes(),
                $user->getRoles(),
                $token->getIdpName()
            );
            $authenticatedToken->setAuthenticated(true);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The authentication failed.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof SamlTokenInterface;
    }

    /**
     * @param SamlTokenInterface $token
     */
    protected function retrieveUser($token)
    {
        try {
            return $this->userProvider->loadUserByUsername($token->getUsername(), $token->getIdpName());
        } catch (UsernameNotFoundException $e) {
            if ($this->userFactory instanceof SamlUserFactoryInterface) {
                return $this->generateUser($token);
            }

            throw $e;
        }
    }

    protected function generateUser($token)
    {
        $user = $this->userFactory->createUser($token);

        if ($this->options['persist_user'] && $this->entityManager) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }
}
