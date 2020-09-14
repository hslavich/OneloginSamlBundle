<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserFactory implements SamlUserFactoryInterface
{
    protected $userClass;
    protected $mapping;

    public function __construct(string $userClass, array $mapping)
    {
        $this->userClass = $userClass;
        $this->mapping = $mapping;
    }

    public function createUser($username, array $attributes = []): UserInterface
    {
        if ($username instanceof SamlTokenInterface) {
            trigger_deprecation('hslavich/oneloginsaml-bundle', '2.1', 'Usage of %s is deprecated.', SamlTokenInterface::class);

            [$username, $attributes] = [$username->getUsername(), $username->getAttributes()];
        }

        $user = new $this->userClass();
        $user->setUsername($username);

        foreach ($this->mapping as $field => $attribute) {
            $reflection = new \ReflectionClass($this->userClass);
            $property = $reflection->getProperty($field);
            $property->setAccessible(true);
            $property->setValue($user, $this->getPropertyValue($attributes, $attribute));
        }

        return $user;
    }

    protected function getPropertyValue(array $attributes, $attribute)
    {
        if (\is_string($attribute) && 0 === strpos($attribute, '$')) {
            return $attributes[substr($attribute, 1)][0];
        }

        return $attribute;
    }
}
