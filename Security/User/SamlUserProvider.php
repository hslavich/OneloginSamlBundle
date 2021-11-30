<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SamlUserProvider implements UserProviderInterface
{
    protected $userClass;
    protected $defaultRoles;

    /**
     * @param class-string $userClass
     * @param string[]     $defaultRoles
     */
    public function __construct(string $userClass, array $defaultRoles)
    {
        if (! \is_a($userClass, UserInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('The $userClass argument must be a class implementing the %s interface.', UserInterface::class));
        }

        $this->userClass = $userClass;
        $this->defaultRoles = $defaultRoles;
    }

    public function loadUserByIdentifier($identifier): UserInterface
    {
        return new $this->userClass($identifier, $this->defaultRoles);
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof $this->userClass) {
            throw new UnsupportedUserException();
        }

        return $user;
    }

    /**
     * @param class-string $class
     */
    public function supportsClass(string $class): bool
    {
        return $this->userClass === $class || is_subclass_of($class, $this->userClass);
    }
}
