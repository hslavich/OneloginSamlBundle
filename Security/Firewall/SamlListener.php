<?php

namespace Hslavich\OneloginSamlBundle\Security\Firewall;

use Hslavich\OneloginSamlBundle\Idp\IdpResolverInterface;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistryInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use OneLogin\Saml2\Auth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

/**
 * @deprecated since 2.1
 */
class SamlListener extends AbstractAuthenticationListener
{
    private AuthRegistryInterface $authRegistry;
    private IdpResolverInterface $idpResolver;

    public function setAuthRegistry(AuthRegistryInterface $authRegistry)
    {
        $this->authRegistry = $authRegistry;
    }

    public function setIdpResolver(IdpResolverInterface $idpResolver)
    {
        $this->idpResolver = $idpResolver;
    }

    /**
     * Performs authentication.
     *
     * @param Request $request A Request instance
     * @return TokenInterface|Response|null The authenticated token, null if full authentication is not possible, or a Response
     *
     * @throws AuthenticationException if the authentication fails
     * @throws \Exception if attribute set by "username_attribute" option not found
     */
    protected function attemptAuthentication(Request $request)
    {
        $oneLoginAuth = $this->getOneLoginAuth($request);

        $oneLoginAuth->processResponse();
        if ($oneLoginAuth->getErrors()) {
            if (null !== $this->logger) {
                $this->logger->error($oneLoginAuth->getLastErrorReason());
            }
            throw new AuthenticationException($oneLoginAuth->getLastErrorReason());
        }

        if (isset($this->options['use_attribute_friendly_name']) && $this->options['use_attribute_friendly_name']) {
            $attributes = $oneLoginAuth->getAttributesWithFriendlyName();
        } else {
            $attributes = $oneLoginAuth->getAttributes();
        }
        $attributes['sessionIndex'] = $oneLoginAuth->getSessionIndex();
        $token = new SamlToken();
        $token->setAttributes($attributes);

        if (isset($this->options['username_attribute'])) {
            if (!array_key_exists($this->options['username_attribute'], $attributes)) {
                if (null !== $this->logger) {
                    $this->logger->error(sprintf("Found attributes: %s", print_r($attributes, true)));
                }
                throw new \RuntimeException(sprintf("Attribute '%s' not found in SAML data", $this->options['username_attribute']));
            }

            $username = $attributes[$this->options['username_attribute']][0];
        } else {
            $username = $oneLoginAuth->getNameId();
        }
        $token->setUser($username);

        return $this->authenticationManager->authenticate($token);
    }

    private function getOneLoginAuth(Request $request): Auth
    {
        try {
            $idp = $this->idpResolver->resolve($request);
            $authService = $idp
                ? $this->authRegistry->getService($idp)
                : $this->authRegistry->getDefaultService();
        } catch (\RuntimeException $exception) {
            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage());
            }

            throw new AuthenticationServiceException($exception->getMessage());
        }

        return $authService;
    }
}
