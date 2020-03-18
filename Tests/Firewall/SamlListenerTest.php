<?php

namespace Hslavich\OneloginSamlBundle\Tests\Firewall;

use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;

class SamlProviderTest extends \PHPUnit_Framework_TestCase
{
    private $httpKernel;
    private $authenticationManager;
    private $dispatcher;
    private $event;
    private $sessionStrategy;
    private $request;
    private $tokenStorage;

    public function testHandleValidAuthenticationWithAttribute()
    {
        $listener = $this->getListener(['username_attribute' => 'uid']);

        $attributes = ['uid' => ['username_uid']];

        $onelogin = $this->getMockBuilder(\OneLogin\Saml2\Auth::class)->disableOriginalConstructor()->getMock();
        $onelogin->expects($this->once())->method('processResponse');
        $onelogin
            ->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue($attributes))
        ;
        $listener->setOneLoginAuth($onelogin);

        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 40300) {
            $listener($this->event);
        } else {
            $listener->handle($this->event);
        }
    }

    public function testHandleValidAuthenticationWithEmptyOptions()
    {
        $listener = $this->getListener([]);

        $onelogin = $this->getMockBuilder(\OneLogin\Saml2\Auth::class)->disableOriginalConstructor()->getMock();
        $onelogin->expects($this->once())->method('processResponse');
        $onelogin
            ->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue([]))
        ;
        $onelogin
            ->expects($this->once())
            ->method('getNameId')
            ->will($this->returnValue('username'))
        ;
        $listener->setOneLoginAuth($onelogin);

        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 40300) {
            $listener($this->event);
        } else {
            $listener->handle($this->event);
        }
    }

    protected function getListener($options = [])
    {
        return new SamlListener(
            $this->tokenStorage,
            $this->authenticationManager,
            $this->sessionStrategy,
            $this->httpUtils,
            'secured_area',
            $this->createMock(\Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface::class),
            $this->createMock(\Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface::class),
            $options
        );
    }

    protected function setUp()
    {
        $this->httpKernel = $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class);
        $this->authenticationManager = $this->getMockBuilder(
            \Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager::class
        )
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->dispatcher = $this->createMock(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class);
        $this->request = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $this->request
            ->expects($this->any())
            ->method('hasSession')
            ->will($this->returnValue(true))
        ;
        $this->request
            ->expects($this->any())
            ->method('hasPreviousSession')
            ->will($this->returnValue(true))
        ;

        if (class_exists('Symfony\Component\HttpKernel\Event\RequestEvent')) {
            $this->event = $this->createMock(\Symfony\Component\HttpKernel\Event\RequestEvent::class, [], [], '', false);
        } else {
            $this->event = $this->createMock(\Symfony\Component\HttpKernel\Event\GetResponseEvent::class, [], [], '', false);
        }
        $this->event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request))
        ;
        $this->event
            ->expects($this->any())
            ->method('getKernel')
            ->will($this->returnValue($this->httpKernel))
        ;
        $this->sessionStrategy = $this->createMock(
            \Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface::class
        );
        $this->httpUtils = $this->createMock(\Symfony\Component\Security\Http\HttpUtils::class);
        $this->httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->will($this->returnValue(true))
        ;

        $reflection = new \ReflectionClass(SamlListener::class);
        $params = $reflection->getConstructor()->getParameters();
        $param = $params[0];
        $this->tokenStorage = $this->createMock($param->getClass()->name);
    }

    protected function tearDown()
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
