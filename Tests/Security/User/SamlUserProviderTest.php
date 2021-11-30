<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\User;

use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use Hslavich\OneloginSamlBundle\Tests\TestUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserProviderTest extends TestCase
{
    public function testUnsupportedUserClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $userClass argument must be a class implementing the Symfony\\Component\\Security\\Core\\User\\UserInterface interface.');

        new SamlUserProvider(\stdClass::class, ['ROLE_ADMIN']);
    }

    public function testLoadByUsername(): void
    {
        $provider = $this->getUserProvider(['ROLE_ADMIN']);
        $user = $provider->loadUserByIdentifier('admin');

        self::assertEquals('admin', $user->getUserIdentifier());
        self::assertEquals(['ROLE_ADMIN'], $user->getRoles());
    }

    public function testRefreshUser(): void
    {
        $user = new TestUser();
        $provider = $this->getUserProvider();

        self::assertSame($user, $provider->refreshUser($user));
    }

    public function testRefreshUnsupportedUserException(): void
    {
        $user = $this->createMock(UserInterface::class);
        $provider = $this->getUserProvider();

        $this->expectException(UnsupportedUserException::class);
        $provider->refreshUser($user);
    }

    public function testSupportsClass(): void
    {
        $provider = $this->getUserProvider();

        self::assertTrue($provider->supportsClass(TestUser::class));
        self::assertFalse($provider->supportsClass(UserInterface::class));
    }

    protected function getUserProvider($roles = []): SamlUserProvider
    {
        return new SamlUserProvider(TestUser::class, $roles);
    }
}
