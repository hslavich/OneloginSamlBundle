<?php

namespace Hslavich\OneloginSamlBundle\Tests\Authentication\Provider;

use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;

class SamlProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticate()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

        $provider = $this->getProvider($user);
        $token = $provider->authenticate($this->getSamlToken());

        $this->assertInstanceOf('Hslavich\\OneloginSamlBundle\\Security\\Authentication\\Token\\SamlToken', $token);
        $this->assertEquals(array('foo' => 'bar'), $token->getAttributes());
        $this->assertEquals(array(), $token->getRoles());
        $this->assertTrue($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());
    }

    protected function getSamlToken()
    {
        $token = $this->getMock('Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken', array('getUsername'), array(), '', false);
        $token->expects($this->once())->method('getUsername')->will($this->returnValue('admin'));
        $token->setAttributes(array('foo' => 'bar'));

        return $token;
    }

    protected function getProvider($user)
    {
        $userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $userProvider->expects($this->once())->method('loadUserByUsername')->will($this->returnValue($user));

        $provider = new SamlProvider($userProvider);
        $provider->setTokenFactory(new SamlTokenFactory());

        return $provider;
    }
}
