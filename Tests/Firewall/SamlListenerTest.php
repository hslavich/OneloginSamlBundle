<?php

namespace Hslavich\OneloginSamlBundle\Tests\Firewall;

use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
        $this->request
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('RelayState'))
            ->willReturn('/login/saml/example')
        ;

        $listener = $this->getListener(array('username_attribute' => 'uid'));

        $attributes = array('uid' => array('username_uid'));

        $onelogin = $this->getMockBuilder('OneLogin\Saml2\Auth')->disableOriginalConstructor()->getMock();
        $onelogin->expects($this->once())->method('processResponse');
        $onelogin
            ->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue($attributes))
        ;

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('onelogin_auth.example'))
            ->willReturn($onelogin)
        ;

        $listener->setContainer($container);

        $listener->setAuthMap(array(
            '/login/saml/example' => 'example',
        ));

        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 40300) {
            $listener($this->event);
        } else {
            $listener->handle($this->event);
        }
    }

    public function testHandleValidAuthenticationWithEmptyOptions()
    {
        $this->request
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('RelayState'))
            ->willReturn('/login/saml/example')
        ;

        $listener = $this->getListener(array());

        $onelogin = $this->getMockBuilder('OneLogin\Saml2\Auth')->disableOriginalConstructor()->getMock();
        $onelogin->expects($this->once())->method('processResponse');
        $onelogin
            ->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue(array()))
        ;
        $onelogin
            ->expects($this->once())
            ->method('getNameId')
            ->will($this->returnValue('username'))
        ;

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('onelogin_auth.example'))
            ->willReturn($onelogin)
        ;

        $listener->setContainer($container);

        $listener->setAuthMap(array(
            '/login/saml/example' => 'example',
        ));

        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 40300) {
            $listener($this->event);
        } else {
            $listener->handle($this->event);
        }
    }

    protected function getListener($options = array())
    {
        return new SamlListener(
            $this->tokenStorage,
            $this->authenticationManager,
            $this->sessionStrategy,
            $this->httpUtils,
            'secured_area',
            $this->createMock('Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface'),
            $this->createMock('Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface'),
            $options
        );
    }

    protected function setUp()
    {
        $this->httpKernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $this->authenticationManager = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
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
            $this->event = $this->createMock('Symfony\Component\HttpKernel\Event\RequestEvent', array(), array(), '', false);
        } else {
            $this->event = $this->createMock('Symfony\Component\HttpKernel\Event\GetResponseEvent', array(), array(), '', false);
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
        $this->sessionStrategy = $this->createMock('Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface');
        $this->httpUtils = $this->createMock('Symfony\Component\Security\Http\HttpUtils');
        $this->httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->will($this->returnValue(true))
        ;

        $reflection = new \ReflectionClass('Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener');
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
