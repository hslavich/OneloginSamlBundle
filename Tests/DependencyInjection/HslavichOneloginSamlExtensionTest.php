<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection;

use Hslavich\OneloginSamlBundle\DependencyInjection\HslavichOneloginSamlExtension;
use Hslavich\OneloginSamlBundle\HslavichOneloginSamlBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class HslavichOneloginSamlExtensionTest extends TestCase
{
    private static $containerCache = [];

    public function testLoadIdpSettings(): void
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        self::assertEquals('http://id.example.com/saml2/idp/metadata.php', $settings['idp']['entityId']);
        self::assertEquals('http://id.example.com/saml2/idp/SSOService.php', $settings['idp']['singleSignOnService']['url']);
        self::assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', $settings['idp']['singleSignOnService']['binding']);
        self::assertEquals('http://id.example.com/saml2/idp/SingleLogoutService.php', $settings['idp']['singleLogoutService']['url']);
        self::assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', $settings['idp']['singleLogoutService']['binding']);
        self::assertEquals('idp_x509certdata', $settings['idp']['x509cert']);
        self::assertEquals('43:51:43:a1:b5:fc:8b:b7:0a:3a:a9:b1:0f:66:73:a8', $settings['idp']['certFingerprint']);
        self::assertEquals('sha1', $settings['idp']['certFingerprintAlgorithm']);
        self::assertEquals(['<cert1-string>'], $settings['idp']['x509certMulti']['signing']);
        self::assertEquals(['<cert2-string>'], $settings['idp']['x509certMulti']['encryption']);
    }

    public function testLoadSpSettings(): void
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        self::assertEquals('http://myapp.com/app_dev.php/saml/metadata', $settings['sp']['entityId']);
        self::assertEquals('sp_privateKeyData', $settings['sp']['privateKey']);
        self::assertEquals('sp_x509certdata', $settings['sp']['x509cert']);
        self::assertEquals('urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress', $settings['sp']['NameIDFormat']);
        self::assertEquals('http://myapp.com/app_dev.php/saml/acs', $settings['sp']['assertionConsumerService']['url']);
        self::assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', $settings['sp']['assertionConsumerService']['binding']);
        self::assertEquals('http://myapp.com/app_dev.php/saml/logout', $settings['sp']['singleLogoutService']['url']);
        self::assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', $settings['sp']['singleLogoutService']['binding']);
    }

    public function testLoadSecuritySettings(): void
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        self::assertFalse($settings['security']['nameIdEncrypted']);
        self::assertFalse($settings['security']['authnRequestsSigned']);
        self::assertFalse($settings['security']['logoutRequestSigned']);
        self::assertFalse($settings['security']['logoutResponseSigned']);
        self::assertFalse($settings['security']['wantMessagesSigned']);
        self::assertFalse($settings['security']['wantAssertionsSigned']);
        self::assertFalse($settings['security']['wantNameIdEncrypted']);
        self::assertTrue($settings['security']['requestedAuthnContext']);
        self::assertFalse($settings['security']['signMetadata']);
        self::assertFalse($settings['security']['wantXMLValidation']);
        self::assertEquals('http://www.w3.org/2000/09/xmldsig#rsa-sha1', $settings['security']['signatureAlgorithm']);       
        self::assertFalse($settings['security']['relaxDestinationValidation']);
        self::assertTrue($settings['security']['destinationStrictlyMatches']);
        self::assertFalse($settings['security']['rejectUnsolicitedResponsesWithInResponseTo']);
    }

    public function testLoadBasicSettings(): void
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        self::assertTrue($settings['strict']);
        self::assertFalse($settings['debug']);
        self::assertEquals('http://myapp.com/app_dev.php/saml/', $settings['baseurl']);
    }

    public function testLoadOrganizationSettings(): void
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        self::assertEquals('Example', $settings['organization']['en']['name']);
        self::assertEquals('Example', $settings['organization']['en']['displayname']);
        self::assertEquals('http://example.com', $settings['organization']['en']['url']);
    }

    public function testLoadContactSettings(): void
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        self::assertEquals('Tech User', $settings['contactPerson']['technical']['givenName']);
        self::assertEquals('techuser@example.com', $settings['contactPerson']['technical']['emailAddress']);
        self::assertEquals('Support User', $settings['contactPerson']['support']['givenName']);
        self::assertEquals('supportuser@example.com', $settings['contactPerson']['support']['emailAddress']);
    }

    public function testLoadArrayRequestedAuthnContext(): void
    {
        $settings = $this->createContainerFromFile('requestedAuthnContext_as_array')->getParameter('hslavich_onelogin_saml.settings');

        self::assertSame(['foo', 'bar'], $settings['security']['requestedAuthnContext']);
    }

    protected function createContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.bundles' => ['FrameworkBundle' => HslavichOneloginSamlBundle::class],
            'kernel.bundles_metadata' => ['HslavichOneloginSamlBundle' => ['namespace' => HslavichOneloginSamlBundle::class, 'path' => __DIR__.'/../..']],
            'kernel.cache_dir' => __DIR__,
            'kernel.project_dir' => __DIR__,
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => __DIR__,
            'kernel.container_class' => 'testContainer',
            'container.build_hash' => 'Abc1234',
            'container.build_id' => hash('crc32', 'Abc123423456789'),
            'container.build_time' => 23456789,
        ]));
    }

    protected function createContainerFromFile($file): ContainerBuilder
    {
        $cacheKey = md5(\get_class($this).$file);
        if (isset(self::$containerCache[$cacheKey])) {
            return self::$containerCache[$cacheKey];
        }
        $container = $this->createContainer();
        $container->registerExtension(new HslavichOneloginSamlExtension());
        $this->loadFromFile($container, $file);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);

        $container->compile();

        return self::$containerCache[$cacheKey] = $container;
    }

    protected function loadFromFile(ContainerBuilder $container, $file): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixtures'));
        $loader->load($file.'.yaml');
    }
}
