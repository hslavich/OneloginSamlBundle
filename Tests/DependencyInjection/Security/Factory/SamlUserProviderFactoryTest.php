<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection\Security\Provider;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlUserProviderFactory;
use Hslavich\OneloginSamlBundle\Tests\TestUser;

class SamlUserProviderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testAddValidConfig()
    {
        $factory = new SamlUserProviderFactory();
        $nodeDefinition = new ArrayNodeDefinition('saml');
        $factory->addConfiguration($nodeDefinition);

        $config = array(
            'user_class' => TestUser::class,
            'default_roles' => array('ROLE_ADMIN')
        );

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

        $config = array('default_roles' => array('ROLE_ADMIN'));

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $finalizedConfig = $node->finalize($normalizedConfig);
    }

    public function testCreate()
    {
        $container = new ContainerBuilder();
        $factory = new SamlUserProviderFactory();

        $config = array(
            'user_class' => TestUser::class,
            'default_roles' => array('ROLE_USER')
        );

        $factory->create($container, 'test_provider', $config);

        $providerDefinition = $container->getDefinition('test_provider');
        $this->assertEquals(TestUser::class, $providerDefinition->getArgument(0));
        $this->assertEquals(array('ROLE_USER'), $providerDefinition->getArgument(1));
    }
}
