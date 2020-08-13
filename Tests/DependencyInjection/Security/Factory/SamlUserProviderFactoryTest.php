<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection\Security\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlUserProviderFactory;
use Hslavich\OneloginSamlBundle\Tests\TestUser;

class SamlUserProviderFactoryTest extends TestCase
{
    public function testAddValidConfig(): void
    {
        $factory = new SamlUserProviderFactory();
        $nodeDefinition = new ArrayNodeDefinition('saml');
        $factory->addConfiguration($nodeDefinition);

        $config = [
            'user_class' => TestUser::class,
            'default_roles' => ['ROLE_ADMIN']
        ];

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $finalizedConfig = $node->finalize($normalizedConfig);

        self::assertEquals($config, $finalizedConfig);
    }

    public function testAddInvalidConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $factory = new SamlUserProviderFactory();
        $nodeDefinition = new ArrayNodeDefinition('saml');
        $factory->addConfiguration($nodeDefinition);

        $config = ['default_roles' => ['ROLE_ADMIN']];

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $node->finalize($normalizedConfig);
    }

    public function testCreate(): void
    {
        $container = new ContainerBuilder();
        $factory = new SamlUserProviderFactory();

        $config = [
            'user_class' => TestUser::class,
            'default_roles' => ['ROLE_USER']
        ];

        $factory->create($container, 'test_provider', $config);

        $providerDefinition = $container->getDefinition('test_provider');
        self::assertEquals(TestUser::class, $providerDefinition->getArgument(0));
        self::assertEquals(['ROLE_USER'], $providerDefinition->getArgument(1));
    }
}
