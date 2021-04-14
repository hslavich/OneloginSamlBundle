<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection\Security\Factory;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Hslavich\OneloginSamlBundle\DependencyInjection\Security\Factory\SamlFactory;

class SamlFactoryTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * @dataProvider getConfigurationTests
     */
    public function testAddConfig(array $inputConfig, array $expectedConfig): void
    {
      $factory = new SamlFactory();
      $nodeDefinition = new ArrayNodeDefinition($factory->getKey());
      $factory->addConfiguration($nodeDefinition);

      $node = $nodeDefinition->getNode();
      $normalizedConfig = $node->normalize($inputConfig);
      $finalizedConfig = $node->finalize($normalizedConfig);

      self::assertArraySubset($expectedConfig, $finalizedConfig);
    }

    public function getConfigurationTests(): array
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
            ]
        ];

        $tests[] = [
            [],
            [
                'username_attribute' => null,
                'login_path' => 'saml_login',
                'check_path' => 'saml_acs',
                'user_factory' => null,
                'token_factory' => null,
                'persist_user' => false,
            ]
        ];

        return $tests;
    }

    public function testBasicCreate(): void
    {
        $container = new ContainerBuilder();
        $factory = new SamlFactory();
        $nodeDefinition = new ArrayNodeDefinition($factory->getKey());
        $factory->addConfiguration($nodeDefinition);

        $config = [
            'username_attribute' => null,
            'login_path' => 'saml_login',
            'check_path' => 'saml_acs',
        ];
        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($config);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $factory->create($container, 'test_firewall', $finalizedConfig, 'my_user_provider', null);

        $providerDefinition = $container->getDefinition('security.authentication.provider.saml.test_firewall');
        self::assertEquals([
            new Reference('my_user_provider'),
            ['persist_user' => false]
        ], $providerDefinition->getArguments());
    }
}
