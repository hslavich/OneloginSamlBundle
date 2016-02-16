<?php

namespace Hslavich\OneloginSamlBundle\Tests;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    private $username;
    private $roles;

    public function __construct($username, $roles = array())
    {
        $this->username = $username;
        $this->roles = $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }
}
