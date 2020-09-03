<?php

namespace Hslavich\OneloginSamlBundle\Tests\Security\Http\Authentication;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Http\Authentication\SamlAuthenticationSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\HttpUtils;

class SamlAuthenticationSuccessHandlerTest extends TestCase
{
    public function testWithAlwaysUseDefaultTargetPath(): void
    {
        $httpUtils = new HttpUtils($this->getUrlGenerator());
        $handler = new SamlAuthenticationSuccessHandler($httpUtils, ['always_use_default_target_path' => true]);
        $defaultTargetPath = $httpUtils->generateUri($this->getRequest('/sso/login'), $this->getOption($handler, 'default_target_path', '/'));
        $response = $handler->onAuthenticationSuccess($this->getRequest('/login', 'http://localhost/relayed'), $this->getSamlToken());
        self::assertTrue($response->isRedirect($defaultTargetPath), 'SamlAuthenticationSuccessHandler does not honor the always_use_default_target_path option.');
    }

    public function testRelayState(): void
    {
        $handler = new SamlAuthenticationSuccessHandler(new HttpUtils($this->getUrlGenerator()), ['always_use_default_target_path' => false]);
        $response = $handler->onAuthenticationSuccess($this->getRequest('/sso/login', 'http://localhost/relayed'), $this->getSamlToken());
        self::assertTrue($response->isRedirect('http://localhost/relayed'), 'SamlAuthenticationSuccessHandler is not processing the RelayState parameter properly.');
    }

    public function testWithoutRelayState(): void
    {
        $httpUtils = new HttpUtils($this->getUrlGenerator());
        $handler = new SamlAuthenticationSuccessHandler($httpUtils, ['always_use_default_target_path' => false]);
        $defaultTargetPath = $httpUtils->generateUri($this->getRequest('/sso/login'), $this->getOption($handler, 'default_target_path', '/'));
        $response = $handler->onAuthenticationSuccess($this->getRequest(), $this->getSamlToken());
        self::assertTrue($response->isRedirect($defaultTargetPath));
    }

    public function testRelayStateLoop(): void
    {
        $httpUtils = new HttpUtils($this->getUrlGenerator());
        $handler = new SamlAuthenticationSuccessHandler($httpUtils, ['always_use_default_target_path' => false]);
        $loginPath = $httpUtils->generateUri($this->getRequest('/sso/login'), $this->getOption($handler, 'login_path', '/login'));
        $response = $handler->onAuthenticationSuccess($this->getRequest($loginPath), $this->getSamlToken());
        self::assertNotTrue($response->isRedirect($loginPath), 'SamlAuthenticationSuccessHandler causes a redirect loop when RelayState points to login_path.');
    }


    private function getUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static function ($name) {
                return (string)$name;
            })
        ;

        return $urlGenerator;
    }

    private function getRequest($path = '/', $relayState = null): Request
    {
        $params = [];
        if (null !== $relayState) {
            $params['RelayState'] = $relayState;
        }
        return Request::create($path, 'get', $params);
    }

    private function getSamlToken(): SamlToken
    {
        $token = $this->createMock(SamlToken::class);
        $token
            ->method('getUsername')
            ->willReturn('admin')
        ;
        $token
            ->method('getAttributes')
            ->willReturn(['foo' => 'bar'])
        ;

        return $token;
    }

    private function getOption($handler, $name, $default = null)
    {
        $reflection = new \ReflectionObject($handler);
        $options = $reflection->getProperty('options');
        $options->setAccessible(true);
        $arr = $options->getValue($handler);
        if (!is_array($arr) || !isset($arr[$name])) {
            return $default;
        }
        return $arr[$name];
    }
}
