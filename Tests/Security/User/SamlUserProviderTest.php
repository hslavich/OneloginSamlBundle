<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\User;

use Hslavich\OneloginSamlBundle\Security\User\SamlUserProvider;
use Hslavich\OneloginSamlBundle\Tests\TestUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserProviderTest extends TestCase
{
    public function testLoadByUsername(): void
    {
        $provider = $this->getUserProvider(['ROLE_ADMIN']);
        $user = $provider->loadUserByUsername('admin');

        self::assertEquals('admin', $user->getUsername());
        self::assertEquals(['ROLE_ADMIN'], $user->getRoles());
    }

    public function testRefreshUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $provider = $this->getUserProvider();

        self::assertSame($user, $provider->refreshUser($user));
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
