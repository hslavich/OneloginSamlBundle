<?php

declare(strict_types=1);

namespace Hslavich\OneloginSamlBundle\Tests\Security\Http\Authenticator;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\SamlAuthenticator;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Hslavich\OneloginSamlBundle\Tests\TestUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;
use Symfony\Component\Security\Http\HttpUtils;
use const false;

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
            $httpUtils = $this->createMock(HttpUtils::class),
            $this->createMock(UserProviderInterface::class),
            $this->createMock(\OneLogin\Saml2\Auth::class),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => true]
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
            $httpUtils = $this->createMock(HttpUtils::class),
            $this->createMock(UserProviderInterface::class),
            $auth,
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false]
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('some reason');

        $authenticator->authenticate($this->createRequestWithSession());
    }

    public function testUsernameNotFound(): void
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willThrowException(new UsernameNotFoundException())
        ;

        $authenticator = new SamlAuthenticator(
            $httpUtils = $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false]
        );

        $this->expectException(UsernameNotFoundException::class);

        $authenticator->authenticate($this->createRequestWithSession());
    }

    public function testUserFactoryException(): void
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willThrowException(new \RuntimeException())
        ;

        $authenticator = new SamlAuthenticator(
            $httpUtils = $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false]
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The authentication failed.');

        $authenticator->authenticate($this->createRequestWithSession());
    }

    public function testAuthenticationWithLoad(): void
    {
        $user = new TestUser('test', ['ROLE_USER']);

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user)
        ;

        $authenticator = new SamlAuthenticator(
            $httpUtils = $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['require_previous_session' => false]
        );

        $passport = $authenticator->authenticate($this->createRequestWithSession());

        self::assertInstanceOf(UserPassportInterface::class, $passport);
        self::assertEquals($user, $passport->getUser());
    }

    public function testAuthenticationWithGenerate(): void
    {
        $user = new TestUser('test', ['ROLE_USER']);

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willThrowException(new UsernameNotFoundException())
        ;

        $userFactory = $this->createMock(SamlUserFactoryInterface::class);
        $userFactory
            ->expects(self::once())
            ->method('createUser')
            ->with('uname', ['sessionIndex' => 'sess_index'])
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

        $authenticator = new SamlAuthenticator(
            $httpUtils = $this->createMock(HttpUtils::class),
            $userProvider,
            $this->getErrorlessAuth(false),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            [
                'require_previous_session' => false,
                'persist_user' => true,
            ],
            $userFactory,
            $entityManager
        );

        $passport = $authenticator->authenticate($this->createRequestWithSession());

        self::assertInstanceOf(UserPassportInterface::class, $passport);
        self::assertEquals($user, $passport->getUser());
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
            $this->createMock(UserProviderInterface::class),
            $this->createMock(\OneLogin\Saml2\Auth::class),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            ['check_path' => '']
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
        }

        return $auth;
    }
}
