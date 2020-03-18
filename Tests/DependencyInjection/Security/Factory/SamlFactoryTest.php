<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection\Security\Provider;

use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SamlFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getConfigurationTests
     */
    public function testAddConfig(array $inputConfig, array $expectedConfig)
    {
        $factory = new SamlFactory();
        $nodeDefinition = new ArrayNodeDefinition($factory->getKey());
        $factory->addConfiguration($nodeDefinition);

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertArraySubset($expectedConfig, $finalizedConfig);
    }

    public function getConfigurationTests()
    {
        $tests = [];

        $tests[] = [
            [
                'username_attribute' => 'uid',
                'login_path' => '/my_login',
                'check_path' => '/my_login_check',
                'user_factory' => 'my_user_factory',
                'token_factory' => 'my_token_factory',
                'persist_user' => true,
            ],
            [
                'username_attribute' => 'uid',
                'login_path' => '/my_login',
                'check_path' => '/my_login_check',
                'user_factory' => 'my_user_factory',
                'token_factory' => 'my_token_factory',
                'persist_user' => true,
            ],
        ];

        $tests[] = [
            [],
            [
                'username_attribute' => null,
                'login_path' => '/saml/login',
                'check_path' => '/saml/acs',
                'user_factory' => null,
                'token_factory' => null,
                'persist_user' => false,
            ],
        ];

        return $tests;
    }

    public function testBasicCreate()
    {
        $container = new ContainerBuilder();
        $factory = new SamlFactory();
        $nodeDefinition = new ArrayNodeDefinition($factory->getKey());
        $factory->addConfiguration($nodeDefinition);

        $config = [
            'username_attribute' => null,
            'login_path' => '/saml/login',
            'check_path' => '/saml/acs',
        ];
        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $factory->create($container, 'test_firewall', $finalizedConfig, 'my_user_provider', null);

        $providerDefinition = $container->getDefinition('security.authentication.provider.saml.test_firewall');
        $this->assertEquals([
            'index_0' => new Reference('my_user_provider'),
            0 => ['persist_user' => false],
        ], $providerDefinition->getArguments());
    }
}
