<?php

namespace Hslavich\OneloginSamlBundle\Tests;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    private $username;
    private $password;
    private $email;
    private $name;
    private $lastname;
    private $roles;

    public function __construct($username = '', $roles = [])
    {
        $this->username = $username;
        $this->roles = $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
