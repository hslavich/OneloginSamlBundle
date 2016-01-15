<?php

namespace Hslavich\OneloginSamlBundle\Security\User;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;

class SamlUserFactory implements SamlUserFactoryInterface
{
    protected $userClass;
    protected $mapping;

    public function __construct($userClass, array $mapping)
    {
        $this->userClass = $userClass;
        $this->mapping = $mapping;
    }

    public function createUser(SamlTokenInterface $token)
    {
        $user = new $this->userClass();
        $user->setUsername($token->getUsername());

        foreach ($this->mapping as $field => $attribute) {
            $reflection = new \ReflectionClass($this->userClass);
            $property = $reflection->getProperty($field);
            $property->setAccessible(true);
            $property->setValue($user, $this->getPropertyValue($token, $attribute));
        }

        return $user;
    }

    protected function getPropertyValue($token, $attribute)
    {
        if (is_string($attribute) && '$' == substr($attribute, 0, 1)) {
            $attributes = $token->getAttributes();

            return $attributes[substr($attribute, 1)][0];
        }

        return $attribute;
    }
}
