<?php

namespace Hslavich\OneloginSamlBundle\OneLogin;

use OneLogin\Saml2\Auth;

class AuthRegistry implements AuthRegistryInterface
{
    /**
     * @var array<string, Auth>
     */
    private array $services = [];

    public function addService(string $key, Auth $auth): AuthRegistryInterface
    {
        if (\array_key_exists($key, $this->services)) {
            throw new \OverflowException('Auth service with key "'.$key.'" already exists.');
        }

        $this->services[$key] = $auth;

        return $this;
    }

    public function hasService(string $key): bool
    {
        return \array_key_exists($key, $this->services);
    }

    public function getService(string $key): Auth
    {
        if ($this->hasService($key)) {
            return $this->services[$key];
        }
        throw new \OutOfBoundsException('Auth service for key "'.$key.'" does not exists.');
    }

    public function getDefaultService(): Auth
    {
        if (empty($this->services)) {
            throw new \UnderflowException('There is no configured Auth services.');
        }

        return reset($this->services);
    }
}
