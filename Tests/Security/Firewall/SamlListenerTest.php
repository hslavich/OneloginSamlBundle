<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\Firewall;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use OneLogin\Saml2\Auth;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class SamlListenerTest extends TestCase
{
    private $authenticationManager;
    private $event;
    private $sessionStrategy;
    private $tokenStorage;

    public function testHandleValidAuthenticationWithAttribute(): void
    {
        $listener = $this->getListener(['username_attribute' => 'uid']);

        $attributes = ['uid' => ['username_uid']];
        $sessionIndex = uniqid('saml', true);

        $onelogin = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $onelogin
            ->expects(self::once())
            ->method('processResponse')
        ;
        $onelogin
            ->expects(self::once())
            ->method('getAttributes')
            ->willReturn($attributes)
        ;
        $onelogin
            ->expects(self::once())
            ->method('getSessionIndex')
            ->willReturn($sessionIndex)
        ;
        $listener->setOneLoginAuth($onelogin);

        $this->authenticationManager
            ->expects(self::once())
            ->method('authenticate')
            ->with(self::callback(static function (SamlToken $token) use ($sessionIndex) {
                return $sessionIndex === $token->getAttributes()['sessionIndex']
                    && 'username_uid' === $token->getUsername();
            }))
        ;

        $listener($this->event);
    }

    public function testHandleValidAuthenticationWithEmptyOptions(): void
    {
        $listener = $this->getListener();

        $onelogin = $this->getMockBuilder(Auth::class)->disableOriginalConstructor()->getMock();
        $onelogin->expects(self::once())->method('processResponse');
        $onelogin
            ->expects(self::once())
            ->method('getAttributes')
            ->willReturn([])
        ;
        $onelogin
            ->expects(self::once())
            ->method('getNameId')
            ->willReturn('username')
        ;
        $listener->setOneLoginAuth($onelogin);

        $listener($this->event);
    }

    protected function getListener($options = []): SamlListener
    {
        return new SamlListener(
            $this->tokenStorage,
            $this->authenticationManager,
            $this->sessionStrategy,
            $this->httpUtils,
            'secured_area',
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            $options
        );
    }

    protected function setUp(): void
    {
        $this->authenticationManager = $this->createMock(AuthenticationProviderManager::class);

        $request = $this->createMock(Request::class);
        $request
            ->method('hasSession')
            ->willReturn(true)
        ;
        $request
            ->method('hasPreviousSession')
            ->willReturn(true)
        ;

        $this->event = $this->createMock(RequestEvent::class);
        $this->event
            ->method('getRequest')
            ->willReturn($request)
        ;
        $this->event
            ->method('getKernel')
            ->willReturn($this->createMock(HttpKernelInterface::class))
        ;

        $this->sessionStrategy = $this->createMock(SessionAuthenticationStrategyInterface::class);
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->httpUtils->method('checkRequestPath')
            ->willReturn(true)
        ;

        $reflection = new \ReflectionClass(SamlListener::class);
        $params = $reflection->getConstructor()->getParameters();
        $param = $params[0];
        $this->tokenStorage = $this->createMock($param->getClass()->name);
    }

    protected function tearDown(): void
    {
        $this->authenticationManager = null;
        $this->event = null;
        $this->sessionStrategy = null;
        $this->tokenStorage = null;
    }
}
