<?php

namespace Hslavich\OneloginSamlBundle\Tests\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactory;
use Hslavich\OneloginSamlBundle\Tests\TestUser;

class SamlUserFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testUserMapping()
    {
        $map = [
            'password' => 'notused',
            'email' => '$mail',
            'name' => '$cn',
            'lastname' => '$sn',
            'roles' => ['ROLE_USER'],
        ];

        $token = $this->createMock(SamlToken::class);
        $token->method('getUsername')->willReturn('admin');
        $token->method('getAttributes')->willReturn([
            'mail' => ['email@mail.com'],
            'cn' => ['testname'],
            'sn' => ['testlastname'],
        ]);

        $factory = new SamlUserFactory(TestUser::class, $map);
        $user = $factory->createUser($token);

        $this->assertEquals('admin', $user->getUsername());
        $this->assertEquals('email@mail.com', $user->getEmail());
        $this->assertEquals('testname', $user->getName());
        $this->assertEquals('testlastname', $user->getLastname());
        $this->assertEquals('notused', $user->getPassword());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }
}
