<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Tests\Security\Http\Authenticator;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;
use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;
use Hslavich\OneloginSamlBundle\EventListener\User\UserCreatedListener;
use Hslavich\OneloginSamlBundle\EventListener\User\UserModifiedListener;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\SamlAuthenticator;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use Hslavich\OneloginSamlBundle\Tests\TestUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;
use Symfony\Component\Security\Http\HttpUtils;

class SamlAuthenticatorTest extends TestCase
{
    public function testSupports(): void
    {
        self::assertFalse($this->createSamlAuthenticatorForSupports(self::once(), false)->supports(Request::create('', 'POST')));
        self::assertFalse($this->createSamlAuthenticatorForSupports(self::never())->supports(Request::create('')));

        self::assertTrue($this->createSamlAuthenticatorForSupports(self::once(), true)->supports(Request::create('', 'POST')));
    }

    /**
     * @dataProvider getNoSessionRequests
     */
    public function testNoSessionException(Request $request, string $message): void
    {
        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $this->createMock(SamlUserProvider::class),
            $this->createMock(\OneLogin\Saml2\Auth::class),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => true],
            null,
            null,
            null
        );

        $this->expectException(SessionUnavailableException::class);
        $this->expectExceptionMessage($message);

        $authenticator->authenticate($request);
    }

    public function getNoSessionRequests(): ?\Generator
    {
        yield [Request::create(''), 'This authentication method requires a session.'];

        $request = Request::create('');
        $request->setSession(new Session());
        yield [$request, 'Your session has timed out, or you have disabled cookies.'];
    }

    public function testAuthenticationException(): void
    {
        $auth = $this->getErrorlessAuth(true);
        $auth
            ->expects(self::once())
            ->method('getLastErrorReason')
            ->willReturn('some reason')
        ;

        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $this->createMock(SamlUserProvider::class),
            $auth,
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false],
            null,
            null,
            null
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('some reason');

        $authenticator->authenticate($this->createRequestWithSession());
    }

    public function testUserNotFound(): void
    {
        $userProvider = $this->createMock(SamlUserProvider::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willThrowException(new UserNotFoundException())
        ;

        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false],
            null,
            null,
            null
        );

        $this->expectException(UserNotFoundException::class);

        $authenticator->createAuthenticatedToken(
            $authenticator->authenticate($this->createRequestWithSession()),
            'no-matter'
        );
    }

    public function testUserFactoryException(): void
    {
        $userProvider = $this->createMock(SamlUserProvider::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willThrowException(new \RuntimeException())
        ;

        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false],
            null,
            null,
            null
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The authentication failed.');

        $authenticator->createAuthenticatedToken(
            $authenticator->authenticate($this->createRequestWithSession()),
            'no-matter'
        );
    }

    public function testAuthenticationWithLoad(): void
    {
        $user = new TestUser('test', ['ROLE_USER']);

        $userProvider = $this->createMock(SamlUserProvider::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willReturn($user)
        ;

        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false],
            null,
            null,
            null
        );

        $passport = $authenticator->authenticate($this->createRequestWithSession());

        self::assertInstanceOf(UserPassportInterface::class, $passport);
        self::assertEquals($user, $passport->getUser());
    }

    public function testAuthenticationWithGenerate(): void
    {
        $user = new TestUser('test', ['ROLE_USER']);

        $userProvider = $this->createMock(SamlUserProvider::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willThrowException(new UserNotFoundException())
        ;

        $userFactory = $this->createMock(SamlUserFactoryInterface::class);
        $userFactory
            ->expects(self::once())
            ->method('createUser')
            ->with('uname', [
                'sessionIndex' => 'sess_index',
                'foo' => 'bar',
            ])
            ->willReturn($user)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($user)
        ;
        $entityManager
            ->expects(self::once())
            ->method('flush')
        ;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(UserCreatedEvent::class, new UserCreatedListener($entityManager, true));

        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false],
            $userFactory,
            $eventDispatcher,
            null
        );

        $passport = $authenticator->authenticate($this->createRequestWithSession());

        self::assertInstanceOf(UserPassportInterface::class, $passport);
        self::assertEquals($user, $passport->getUser());
    }

    public function testAuthenticationWithSamlAttributesInjection(): void
    {
        $user = $this->createMock(SamlUserInterface::class);
        $user
            ->expects(self::once())
            ->method('setSamlAttributes')
            ->with(self::equalTo([
                'foo' => 'bar',
                'sessionIndex' => 'sess_index',
            ]))
        ;

        $userProvider = $this->createMock(SamlUserProvider::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willReturn($user)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($user)
        ;
        $entityManager
            ->expects(self::once())
            ->method('flush')
        ;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(UserModifiedEvent::class, new UserModifiedListener($entityManager, true));

        $authenticator = new SamlAuthenticator(
            $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false],
            null,
            $eventDispatcher,
            null
        );

        $passport = $authenticator->authenticate($this->createRequestWithSession());
        $passport->getUser();
    }

    private function createSamlAuthenticatorForSupports(InvokedCount $checkRequestExpects, ?bool $checkRequestPathReturn = null): SamlAuthenticator
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $checkRequestPath = $httpUtils
            ->expects($checkRequestExpects)
            ->method('checkRequestPath')
        ;

        if (null !== $checkRequestPathReturn) {
            $checkRequestPath->willReturn($checkRequestPathReturn);
        }

        return new SamlAuthenticator(
            $httpUtils,
            $this->createMock(SamlUserProvider::class),
            $this->createMock(\OneLogin\Saml2\Auth::class),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['check_path' => ''],
            null,
            null,
            null
        );
    }

    private function createRequestWithSession(): Request
    {
        $request = Request::create('');
        $request->setSession(new Session());

        return $request;
    }

    /**
     * @return \OneLogin\Saml2\Auth|MockObject
     */
    private function getErrorlessAuth(bool $errors): \OneLogin\Saml2\Auth
    {
        $auth = $this->createMock(\OneLogin\Saml2\Auth::class);
        $auth
            ->expects(self::once())
            ->method('processResponse')
        ;
        $auth
            ->expects(self::once())
            ->method('getErrors')
            ->willReturn($errors)
        ;

        if (!$errors) {
            $auth
                ->expects(self::once())
                ->method('getSessionIndex')
                ->willReturn('sess_index')
            ;
            $auth
                ->expects(self::once())
                ->method('getNameId')
                ->willReturn('uname')
            ;
            $auth
                ->expects(self::once())
                ->method('getAttributes')
                ->willReturn(['foo' => 'bar'])
            ;
        }

        return $auth;
    }
}
