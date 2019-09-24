<?php

namespace Hslavich\OneloginSamlBundle\Security\Firewall;

use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use OneLogin\Saml2\Auth;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

class SamlListener extends AbstractAuthenticationListener implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $authMap = [];
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param array $authMap
     */
    public function setAuthMap(array $authMap): void
    {
        $this->authMap = $authMap;
    }

    /**
     * Sets the container.
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
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
        if (($relayState = $request->get('RelayState', null)) === null) {
            throw new \Exception("Missing RelayState to determine IDP");
        }
        $path = parse_url($relayState, PHP_URL_PATH);
        if (!isset($this->authMap[$path])) {
            throw new \Exception(sprintf("Unknown IDP '%s'", $path));
        }
        $idp = $this->authMap[$path];
        /** @var Auth $oneLoginAuth */
        $oneLoginAuth = $this->container->get('onelogin_auth.' . $idp);

        $oneLoginAuth->processResponse();
        if ($oneLoginAuth->getErrors()) {
            $this->logger->error($oneLoginAuth->getLastErrorReason());
            throw new AuthenticationException($oneLoginAuth->getLastErrorReason());
        }

        $attributes = [];
        if (isset($this->options['use_attribute_friendly_name']) && $this->options['use_attribute_friendly_name']) {
            $attributes = $oneLoginAuth->getAttributesWithFriendlyName();
        } else {
            $attributes = $oneLoginAuth->getAttributes();
        }
        $attributes['sessionIndex'] = $oneLoginAuth->getSessionIndex();
        $attributes['idp'] = $idp;
        $token = new SamlToken();
        $token->setAttributes($attributes);

        if (isset($this->options['username_attribute'])) {
            if (!array_key_exists($this->options['username_attribute'], $attributes)) {
                $this->logger->error(sprintf("Found attributes: %s", print_r($attributes, true)));
                throw new \Exception(sprintf("Attribute '%s' not found in SAML data", $this->options['username_attribute']));
            }

            $username = $attributes[$this->options['username_attribute']][0];
        } else {
            $username = $oneLoginAuth->getNameId();
        }
        $token->setUser($username);

        return $this->authenticationManager->authenticate($token);
    }

}
