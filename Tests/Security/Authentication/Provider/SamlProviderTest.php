<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\Authentication\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;
use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;
use Hslavich\OneloginSamlBundle\EventListener\User\UserCreatedListener;
use Hslavich\OneloginSamlBundle\EventListener\User\UserModifiedListener;
use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
        $this->expectException(UserNotFoundException::class);
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

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::equalTo($user))
        ;
        $entityManager
            ->expects(self::once())
            ->method('flush')
        ;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(UserModifiedEvent::class, new UserModifiedListener($entityManager, true));

        $provider = $this->getProvider($user, null, $eventDispatcher);
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
        $entityManager
            ->expects(self::once())
            ->method('flush')
        ;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(UserCreatedEvent::class, new UserCreatedListener($entityManager, true));

        $provider = $this->getProvider(null, $userFactory, $eventDispatcher);
        $provider->authenticate($this->getSamlToken());

    }

    public function testAuthenticateCheckerInvalidUser()
    {
        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');

        $userChecker = $this->createMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        $exception = new \Exception('This user is valid in SSO but invalid in app');
        $userChecker->expects($this->once())->method('checkPreAuth')->willThrowException($exception);

        $provider = $this->getProvider($user, null, null, $userChecker);

        $this->expectExceptionMessage('This user is valid in SSO but invalid in app');

        $provider->authenticate($this->getSamlToken());
    }

    public function testAuthenticateUserCheckerPostAuth()
    {
        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())->method('getRoles')->willReturn(array());

        $userChecker = $this->createMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        $userChecker->expects($this->once())->method('checkPostAuth');

        $provider = $this->getProvider($user, null, null, $userChecker);

        $token = $provider->authenticate($this->getSamlToken());

        $this->assertSame($user, $token->getUser());
    }

    protected function getSamlToken(): SamlToken
    {
        $token = $this->createMock(SamlToken::class);
        $token
            ->expects(self::once())
            ->method('getUserIdentifier')
            ->willReturn('admin')
        ;
        $token
            ->method('getAttributes')
            ->willReturn(['foo' => 'bar'])
        ;

        return $token;
    }

    protected function getProvider(
        $user = null,
        $userFactory = null,
        EventDispatcherInterface $eventDispatcher = null,
        UserCheckerInterface $userChecker = null
    ): SamlProvider {
        $userProvider = $this->createMock(SamlUserProvider::class);
        if ($user) {
            $userProvider
                ->method('loadUserByIdentifier')
                ->willReturn($user)
            ;
        } else {
            $userProvider
                ->method('loadUserByIdentifier')
                ->will(self::throwException(new UserNotFoundException()))
            ;
        }

        $provider = new SamlProvider($userProvider, $eventDispatcher);
        $provider->setTokenFactory(new SamlTokenFactory());

        if ($userFactory) {
            $provider->setUserFactory($userFactory);
        }

        if ($userChecker) {
            $provider->setUserChecker($userChecker);
        }

        return $provider;
    }
}
