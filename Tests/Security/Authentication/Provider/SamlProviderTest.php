<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\Authentication\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SamlProviderTest extends TestCase
{
    public function testSupports(): void
    {
        $provider = $this->getProvider();

        self::assertTrue($provider->supports($this->createMock(SamlToken::class)));
        self::assertFalse($provider->supports($this->createMock(TokenInterface::class)));
    }

    public function testAuthenticate(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([])
        ;

        $provider = $this->getProvider($user);
        $token = $provider->authenticate($this->getSamlToken());

        self::assertInstanceOf(SamlToken::class, $token);
        self::assertEquals(['foo' => 'bar'], $token->getAttributes());
        self::assertEquals([], $token->getRoleNames());
        self::assertTrue($token->isAuthenticated());
        self::assertSame($user, $token->getUser());
    }

    public function testAuthenticateInvalidUser(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $provider = $this->getProvider();
        $provider->authenticate($this->getSamlToken());
    }

    public function testAuthenticateWithUserFactory(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([])
        ;

        $userFactory = $this->createMock(SamlUserFactoryInterface::class);
        $userFactory
            ->expects(self::once())
            ->method('createUser')
            ->willReturn($user)
        ;

        $provider = $this->getProvider(null, $userFactory);
        $token = $provider->authenticate($this->getSamlToken());

        self::assertInstanceOf(SamlToken::class, $token);
        self::assertEquals(['foo' => 'bar'], $token->getAttributes());
        self::assertEquals([], $token->getRoleNames());
        self::assertTrue($token->isAuthenticated());
        self::assertSame($user, $token->getUser());
    }

    public function testSamlAttributesInjection(): void
    {
        $user = $this->createMock(SamlUserInterface::class);
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([])
        ;
        $user
            ->expects(self::once())
            ->method('setSamlAttributes')
            ->with(self::equalTo(['foo' => 'bar']))
        ;

        $provider = $this->getProvider($user);
        $provider->authenticate($this->getSamlToken());
    }

    public function testPersistUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([])
        ;

        $userFactory = $this->createMock(SamlUserFactoryInterface::class);
        $userFactory
            ->expects(self::once())
            ->method('createUser')
            ->willReturn($user)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::equalTo($user))
        ;

        $provider = $this->getProvider(null, $userFactory, $entityManager, true);
        $provider->authenticate($this->getSamlToken());

    }

    protected function getSamlToken(): SamlToken
    {
        $token = $this->createMock(SamlToken::class);
        $token
            ->expects(self::once())
            ->method('getUsername')
            ->willReturn('admin')
        ;
        $token
            ->method('getAttributes')
            ->willReturn(['foo' => 'bar'])
        ;

        return $token;
    }

    protected function getProvider($user = null, $userFactory = null, $entityManager = null, $persist = false): SamlProvider
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        if ($user) {
            $userProvider
                ->method('loadUserByUsername')
                ->willReturn($user)
            ;
        } else {
            $userProvider
                ->method('loadUserByUsername')
                ->will(self::throwException(new UsernameNotFoundException()))
            ;
        }

        $provider = new SamlProvider($userProvider, ['persist_user' => $persist]);
        $provider->setTokenFactory(new SamlTokenFactory());

        if ($userFactory) {
            $provider->setUserFactory($userFactory);
        }

        if ($entityManager) {
            $provider->setEntityManager($entityManager);
        }

        return $provider;
    }
}
