<?php

namespace Hslavich\OneloginSamlBundle\OneLogin;

use OneLogin\Saml2\Auth;

interface AuthRegistryInterface
{
    public function addService(string $key, Auth $auth): self;

    public function hasService(string $key): bool;

    public function getService(string $key): Auth;

    public function getDefaultService(): Auth;
}
