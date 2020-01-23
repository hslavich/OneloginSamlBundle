<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication\Token;

class SamlTokenFactory implements SamlTokenFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createToken($user, array $attributes, array $roles, $idpName)
    {
        $token = new SamlToken($roles);
        $token->setUser($user);
        $token->setAttributes($attributes);
        $token->setIdpName($idpName);

        return $token;
    }
}
