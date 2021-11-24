<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SamlUserProvider implements UserProviderInterface
{
    protected $userClass;
    protected $defaultRoles;

    public function __construct($userClass, array $defaultRoles)
    {
        $this->userClass = $userClass;
        $this->defaultRoles = $defaultRoles;
    }

    public function loadUserByIdentifier($identifier)
    {
        return new $this->userClass($identifier, $this->defaultRoles);
    }

    public function loadUserByUsername(string $username)
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof $this->userClass) {
            throw new UnsupportedUserException();
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return $this->userClass === $class || is_subclass_of($class, $this->userClass);
    }
}
