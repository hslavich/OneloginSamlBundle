<?php

namespace Hslavich\OneloginSamlBundle\Tests\Firewall;

use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use OneLogin\Saml2\Auth;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;

class SamlListenerTest extends TestCase
{
    private $httpKernel;
    private $authenticationManager;
    private $dispatcher;
    private $event;
    private $sessionStrategy;
    private $request;
    private $tokenStorage;

    public function testHandleValidAuthenticationWithAttribute(): void
    {
        $listener = $this->getListener(['username_attribute' => 'uid']);

        $attributes = ['uid' => ['username_uid']];

        $onelogin = $this->getMockBuilder(Auth::class)->disableOriginalConstructor()->getMock();
        $onelogin->expects(self::once())->method('processResponse');
        $onelogin
            ->expects(self::once())
            ->method('getAttributes')
            ->willReturn($attributes)
        ;
        $listener->setOneLoginAuth($onelogin);

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
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->authenticationManager = $this->createMock(AuthenticationProviderManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->request
            ->method('hasSession')
            ->willReturn(true)
        ;
        $this->request
            ->method('hasPreviousSession')
            ->willReturn(true)
        ;

        $this->event = $this->createMock(RequestEvent::class);
        $this->event
            ->method('getRequest')
            ->willReturn($this->request)
        ;
        $this->event
            ->method('getKernel')
            ->willReturn($this->httpKernel)
        ;
        $this->sessionStrategy = $this->createMock(SessionAuthenticationStrategyInterface::class);
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->httpUtils
            ->method('checkRequestPath')
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
        $this->dispatcher = null;
        $this->event = null;
        $this->sessionStrategy = null;
        $this->httpUtils = null;
        $this->request = null;
        $this->tokenStorage = null;
    }
}
