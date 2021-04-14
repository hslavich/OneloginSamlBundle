<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactory;
use Hslavich\OneloginSamlBundle\Tests\TestUser;
use PHPUnit\Framework\TestCase;

class SamlUserFactoryTest extends TestCase
{
    public function testUserMapping(): void
    {
        $map = [
            'password' => 'notused',
            'email' => '$mail',
            'name' => '$cn',
            'lastname' => '$sn',
            'roles' => ['ROLE_USER']
        ];

        $token = $this->createMock(SamlToken::class);
        $token->method('getUsername')->willReturn('admin');
        $token->method('getAttributes')->willReturn([
            'mail' => ['email@mail.com'],
            'cn' => ['testname'],
            'sn' => ['testlastname']
        ]);

        $factory = new SamlUserFactory(TestUser::class, $map);
        $user = $factory->createUser($token);

        self::assertEquals('admin', $user->getUsername());
        self::assertEquals('email@mail.com', $user->getEmail());
        self::assertEquals('testname', $user->getName());
        self::assertEquals('testlastname', $user->getLastname());
        self::assertEquals('notused', $user->getPassword());
        self::assertEquals(['ROLE_USER'], $user->getRoles());
    }

}
