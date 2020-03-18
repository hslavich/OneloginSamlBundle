<?php

namespace Hslavich\OneloginSamlBundle\Tests\Authentication\Provider;

use Hslavich\OneloginSamlBundle\Security\Authentication\Provider\SamlProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class SamlProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $provider = $this->getProvider();

        $this->assertTrue($provider->supports($this->createMock(
            \Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken::class
        )));
        $this->assertFalse($provider->supports($this->createMock(
            \Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class
        )));
    }

    public function testAuthenticate()
    {
        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $user->expects($this->once())->method('getRoles')->willReturn([]);

        $provider = $this->getProvider($user);
        $token = $provider->authenticate($this->getSamlToken());

        $this->assertInstanceOf(\Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken::class, $token);
        $this->assertEquals(['foo' => 'bar'], $token->getAttributes());
        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 40300) {
            $this->assertEquals([], $token->getRoleNames());
        } else {
            $this->assertEquals([], $token->getRoles());
        }
        $this->assertTrue($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testAuthenticateInvalidUser()
    {
        $provider = $this->getProvider();
        $provider->authenticate($this->getSamlToken());
    }

    public function testAuthenticateWithUserFactory()
    {
        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $user->expects($this->once())->method('getRoles')->willReturn([]);

        $userFactory = $this->createMock(\Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface::class);
        $userFactory->expects($this->once())->method('createUser')->willReturn($user);

        $provider = $this->getProvider(null, $userFactory);
        $token = $provider->authenticate($this->getSamlToken());

        $this->assertInstanceOf(\Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken::class, $token);
        $this->assertEquals(['foo' => 'bar'], $token->getAttributes());
        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 40300) {
            $this->assertEquals([], $token->getRoleNames());
        } else {
            $this->assertEquals([], $token->getRoles());
        }
        $this->assertTrue($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());
    }

    public function testSamlAttributesInjection()
    {
        $user = $this->createMock(\Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface::class);
        $user->expects($this->once())->method('getRoles')->willReturn([]);
        $user->expects($this->once())->method('setSamlAttributes')->with($this->equalTo(['foo' => 'bar']));

        $provider = $this->getProvider($user);
        $provider->authenticate($this->getSamlToken());
    }

    public function testPersistUser()
    {
        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $user->expects($this->once())->method('getRoles')->willReturn([]);

        $userFactory = $this->createMock(\Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface::class);
        $userFactory->expects($this->once())->method('createUser')->willReturn($user);

        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class, ['persist', 'flush']);
        $entityManager->expects($this->once())->method('persist')->with($this->equalTo($user));

        $provider = $this->getProvider(null, $userFactory, $entityManager, true);
        $provider->authenticate($this->getSamlToken());
    }

    protected function getSamlToken()
    {
        $token = $this->createMock(\Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken::class);
        $token->expects($this->once())->method('getUsername')->willReturn('admin');
        $token->method('getAttributes')->willReturn(['foo' => 'bar']);

        return $token;
    }

    protected function getProvider($user = null, $userFactory = null, $entityManager = null, $persist = false)
    {
        $userProvider = $this->createMock(\Symfony\Component\Security\Core\User\UserProviderInterface::class);
        if ($user) {
            $userProvider->method('loadUserByUsername')->willReturn($user);
        } else {
            $userProvider->method('loadUserByUsername')->will($this->throwException(new UsernameNotFoundException()));
        }

        $provider = new SamlProvider($userProvider, ['persist_user' => $persist]);
        $provider->setTokenFactory(new SamlTokenFactory());

        if ($userFactory) {
            $provider->setUserFactory($userFactory);
        }

        if ($entityManager) {
            $provider->setEntityManager($entityManager);
        }

        return $provider;
    }
}
