<?php

namespace Hslavich\OneloginSamlBundle\Tests\DependencyInjection;

use Hslavich\OneloginSamlBundle\DependencyInjection\HslavichOneloginSamlExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

class HslavichOneloginSamlExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    public function testLoadIdpSettings()
    {
        $this->createConfig();
        $settings = $this->config->getParameter('hslavich_onelogin_saml.settings');

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
        $this->createConfig();
        $settings = $this->config->getParameter('hslavich_onelogin_saml.settings');

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
        $this->createConfig();
        $settings = $this->config->getParameter('hslavich_onelogin_saml.settings');

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
        $this->createConfig();
        $settings = $this->config->getParameter('hslavich_onelogin_saml.settings');

        $this->assertTrue($settings['strict']);
        $this->assertFalse($settings['debug']);
    }

    public function testLoadOrganizationSettings()
    {
        $this->createConfig();
        $settings = $this->config->getParameter('hslavich_onelogin_saml.settings');

        $this->assertEquals('Example', $settings['organization']['en']['name']);
        $this->assertEquals('Example', $settings['organization']['en']['displayname']);
        $this->assertEquals('http://example.com', $settings['organization']['en']['url']);
    }

    public function testLoadContactSettings()
    {
        $this->createConfig();
        $settings = $this->config->getParameter('hslavich_onelogin_saml.settings');

        $this->assertEquals('Tech User', $settings['contactPerson']['technical']['givenName']);
        $this->assertEquals('techuser@example.com', $settings['contactPerson']['technical']['emailAddress']);
        $this->assertEquals('Support User', $settings['contactPerson']['support']['givenName']);
        $this->assertEquals('supportuser@example.com', $settings['contactPerson']['support']['emailAddress']);
    }

    protected function createConfig()
    {
        $this->config = new ContainerBuilder();
        $loader = new HslavichOneloginSamlExtension();
        $config = $this->getConfig();
        $loader->load(array($config), $this->config);
    }

    protected function getConfig()
    {
        $yaml = <<<EOF
idp:
    entityId: 'http://id.example.com/saml2/idp/metadata.php'
    singleSignOnService:
        url: 'http://id.example.com/saml2/idp/SSOService.php'
        binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
    singleLogoutService:
        url: 'http://id.example.com/saml2/idp/SingleLogoutService.php'
        binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
    x509cert: 'idp_x509certdata'
    certFingerprint: '43:51:43:a1:b5:fc:8b:b7:0a:3a:a9:b1:0f:66:73:a8'
    certFingerprintAlgorithm: 'sha1'
    x509certMulti:
        signing: ['<cert1-string>']
        encryption: ['<cert2-string>']
sp:
    entityId: 'http://myapp.com/app_dev.php/saml/metadata'
    privateKey: 'sp_privateKeyData'
    x509cert: 'sp_x509certdata'
    NameIDFormat: 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'
    assertionConsumerService:
        url: 'http://myapp.com/app_dev.php/saml/acs'
        binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
    singleLogoutService:
        url: 'http://myapp.com/app_dev.php/saml/logout'
        binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
strict: true
debug: false
security:
    nameIdEncrypted:       false
    authnRequestsSigned:   false
    logoutRequestSigned:   false
    logoutResponseSigned:  false
    wantMessagesSigned:    false
    wantAssertionsSigned:  false
    wantNameIdEncrypted:   false
    requestedAuthnContext: true
    signMetadata: false
    wantXMLValidation: false
    signatureAlgorithm: 'http://www.w3.org/2000/09/xmldsig#rsa-sha1'
contactPerson:
    technical:
        givenName: 'Tech User'
        emailAddress: 'techuser@example.com'
    support:
        givenName: 'Support User'
        emailAddress: 'supportuser@example.com'
organization:
    en:
        name: 'Example'
        displayname: 'Example'
        url: 'http://example.com'
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    protected function tearDown()
    {
        unset($this->config);
    }
}
