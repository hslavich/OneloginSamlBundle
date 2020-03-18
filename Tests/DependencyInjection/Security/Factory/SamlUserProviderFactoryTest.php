<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection\Security\Provider;

use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlUserProviderFactory;
use Hslavich\OneloginSamlBundle\Tests\TestUser;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SamlUserProviderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testAddValidConfig()
    {
        $factory = new SamlUserProviderFactory();
        $nodeDefinition = new ArrayNodeDefinition('saml');
        $factory->addConfiguration($nodeDefinition);

        $config = [
            'user_class' => TestUser::class,
            'default_roles' => ['ROLE_ADMIN'],
        ];

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertEquals($config, $finalizedConfig);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testAddInvalidConfig()
    {
        $factory = new SamlUserProviderFactory();
        $nodeDefinition = new ArrayNodeDefinition('saml');
        $factory->addConfiguration($nodeDefinition);

        $config = ['default_roles' => ['ROLE_ADMIN']];

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $finalizedConfig = $node->finalize($normalizedConfig);
    }

    public function testCreate()
    {
        $container = new ContainerBuilder();
        $factory = new SamlUserProviderFactory();

        $config = [
            'user_class' => TestUser::class,
            'default_roles' => ['ROLE_USER'],
        ];

        $factory->create($container, 'test_provider', $config);

        $providerDefinition = $container->getDefinition('test_provider');
        $this->assertEquals(TestUser::class, $providerDefinition->getArgument(0));
        $this->assertEquals(['ROLE_USER'], $providerDefinition->getArgument(1));
    }
}
