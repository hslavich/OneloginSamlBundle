<?php

namespace Hslavich\OneloginSamlBundle\Tests\User;

use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;

class SamlUserProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadByUsername()
    {
        $provider = $this->getUserProvider(array('ROLE_ADMIN'));
        $user = $provider->loadUserByUsername('admin');

        $this->assertEquals('admin', $user->getUsername());
        $this->assertEquals(array('ROLE_ADMIN'), $user->getRoles());
    }

    public function testRefreshUser()
    {
        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
        $provider = $this->getUserProvider();

        $this->assertSame($user, $provider->refreshUser($user));
    }

    public function testSupportsClass()
    {
        $provider = $this->getUserProvider();

        $this->assertTrue($provider->supportsClass('Hslavich\OneloginSamlBundle\Tests\TestUser'));
        $this->assertFalse($provider->supportsClass('Symfony\Component\Security\Core\User\UserInterface'));
    }

    protected function getUserProvider($roles = array())
    {
        return new SamlUserProvider('Hslavich\OneloginSamlBundle\Tests\TestUser', $roles);
    }
}
