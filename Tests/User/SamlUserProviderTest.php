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

    protected function getUserProvider($roles)
    {
        return new SamlUserProvider('Hslavich\OneloginSamlBundle\Tests\TestUser', $roles);
    }
}
