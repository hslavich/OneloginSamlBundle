<?php

namespace Hslavich\OneloginSamlBundle\Tests\Firewall;

use Hslavich\OneloginSamlBundle\Security\Firewall\SamlListener;
use Symfony\Component\HttpFoundation\Request;

class SamlProviderTest extends \PHPUnit_Framework_TestCase
{
    private $authenticationManager;
    private $dispatcher;
    private $event;
    private $sessionStrategy;
    private $request;
    private $tokenStorage;

    public function testHandleValidAuthenticationWithAttribute()
    {
        $listener = $this->getListener(array('username_attribute' => 'uid'));

        $attributes = array('uid' => array('username_uid'));

        $onelogin = $this->getMockBuilder('OneLogin_Saml2_Auth')->disableOriginalConstructor()->getMock();
        $onelogin->expects($this->once())->method('processResponse');
        $onelogin
            ->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue($attributes))
        ;
        $listener->setOneLoginAuth($onelogin);

        $listener->handle($this->event);
    }

    public function testHandleValidAuthenticationWithEmptyOptions()
    {
        $listener = $this->getListener(array());

        $onelogin = $this->getMockBuilder('OneLogin_Saml2_Auth')->disableOriginalConstructor()->getMock();
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
        $listener->setOneLoginAuth($onelogin);

        $listener->handle($this->event);
    }

    protected function getListener($options = array())
    {
        return new SamlListener(
            $this->tokenStorage,
            $this->authenticationManager,
            $this->sessionStrategy,
            $this->httpUtils,
            'secured_area',
            $this->getMock('Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface'),
            $this->getMock('Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface'),
            $options
        );
    }

    protected function setUp()
    {
        $this->authenticationManager = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
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

        $this->event = $this->getMock('Symfony\Component\HttpKernel\Event\GetResponseEvent', array(), array(), '', false);
        $this->event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request))
        ;
        $this->sessionStrategy = $this->getMock('Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface');
        $this->httpUtils = $this->getMock('Symfony\Component\Security\Http\HttpUtils');
        $this->httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->will($this->returnValue(true))
        ;
        $this->tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
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
