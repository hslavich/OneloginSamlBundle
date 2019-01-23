<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection;

use Hslavich\OneloginSamlBundle\DependencyInjection\HslavichOneloginSamlExtension;
use Hslavich\OneloginSamlBundle\HslavichOneloginSamlBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class HslavichOneloginSamlExtensionTest extends \PHPUnit_Framework_TestCase
{
    private static $containerCache = array();

    public function testLoadIdpSettings()
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertEquals('http://id.example.com/saml2/idp/metadata.php', $settings['idp']['entityId']);
        $this->assertEquals('http://id.example.com/saml2/idp/SSOService.php', $settings['idp']['singleSignOnService']['url']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', $settings['idp']['singleSignOnService']['binding']);
        $this->assertEquals('http://id.example.com/saml2/idp/SingleLogoutService.php', $settings['idp']['singleLogoutService']['url']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', $settings['idp']['singleLogoutService']['binding']);
        $this->assertEquals('idp_x509certdata', $settings['idp']['x509cert']);
        $this->assertEquals('43:51:43:a1:b5:fc:8b:b7:0a:3a:a9:b1:0f:66:73:a8', $settings['idp']['certFingerprint']);
        $this->assertEquals('sha1', $settings['idp']['certFingerprintAlgorithm']);
        $this->assertEquals(array('<cert1-string>'), $settings['idp']['x509certMulti']['signing']);
        $this->assertEquals(array('<cert2-string>'), $settings['idp']['x509certMulti']['encryption']);
    }

    public function testLoadSpSettings()
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertEquals('http://myapp.com/app_dev.php/saml/metadata', $settings['sp']['entityId']);
        $this->assertEquals('sp_privateKeyData', $settings['sp']['privateKey']);
        $this->assertEquals('sp_x509certdata', $settings['sp']['x509cert']);
        $this->assertEquals('urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress', $settings['sp']['NameIDFormat']);
        $this->assertEquals('http://myapp.com/app_dev.php/saml/acs', $settings['sp']['assertionConsumerService']['url']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', $settings['sp']['assertionConsumerService']['binding']);
        $this->assertEquals('http://myapp.com/app_dev.php/saml/logout', $settings['sp']['singleLogoutService']['url']);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', $settings['sp']['singleLogoutService']['binding']);
    }

    public function testLoadSecuritySettings()
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertFalse($settings['security']['nameIdEncrypted']);
        $this->assertFalse($settings['security']['authnRequestsSigned']);
        $this->assertFalse($settings['security']['logoutRequestSigned']);
        $this->assertFalse($settings['security']['logoutResponseSigned']);
        $this->assertFalse($settings['security']['wantMessagesSigned']);
        $this->assertFalse($settings['security']['wantAssertionsSigned']);
        $this->assertFalse($settings['security']['wantNameIdEncrypted']);
        $this->assertTrue($settings['security']['requestedAuthnContext']);
        $this->assertFalse($settings['security']['signMetadata']);
        $this->assertFalse($settings['security']['wantXMLValidation']);
        $this->assertEquals('http://www.w3.org/2000/09/xmldsig#rsa-sha1', $settings['security']['signatureAlgorithm']);
    }

    public function testLoadBasicSettings()
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertTrue($settings['strict']);
        $this->assertFalse($settings['debug']);
        $this->assertEquals('http://myapp.com/app_dev.php/saml/', $settings['baseurl']);
    }

    public function testLoadOrganizationSettings()
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertEquals('Example', $settings['organization']['en']['name']);
        $this->assertEquals('Example', $settings['organization']['en']['displayname']);
        $this->assertEquals('http://example.com', $settings['organization']['en']['url']);
    }

    public function testLoadContactSettings()
    {
        $settings = $this->createContainerFromFile('full')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertEquals('Tech User', $settings['contactPerson']['technical']['givenName']);
        $this->assertEquals('techuser@example.com', $settings['contactPerson']['technical']['emailAddress']);
        $this->assertEquals('Support User', $settings['contactPerson']['support']['givenName']);
        $this->assertEquals('supportuser@example.com', $settings['contactPerson']['support']['emailAddress']);
    }

    public function testLoadArrayRequestedAuthnContext()
    {
        $settings = $this->createContainerFromFile('requestedAuthnContext_as_array')->getParameter('hslavich_onelogin_saml.settings');

        $this->assertSame(['foo', 'bar'], $settings['security']['requestedAuthnContext']);
    }

    protected function createContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.bundles' => array('FrameworkBundle' => HslavichOneloginSamlBundle::class),
            'kernel.bundles_metadata' => array('HslavichOneloginSamlBundle' => array('namespace' => 'Hslavich\\OneloginSamlBundle\\HslavichOneloginSamlBundle', 'path' => __DIR__.'/../..')),
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
        )));
    }

    protected function createContainerFromFile($file)
    {
        $cacheKey = md5(\get_class($this).$file);
        if (isset(self::$containerCache[$cacheKey])) {
            return self::$containerCache[$cacheKey];
        }
        $container = $this->createContainer();
        $container->registerExtension(new HslavichOneloginSamlExtension());
        $this->loadFromFile($container, $file);

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());

        $container->compile();

        return self::$containerCache[$cacheKey] = $container;
    }

    protected function loadFromFile(ContainerBuilder $container, $file)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixtures'));
        $loader->load($file.'.yaml');
    }
}
