<?php

namespace Hslavich\OneloginSamlBundle\Controller;

use Hslavich\OneloginSamlBundle\Idp\IdpResolverInterface;
use Hslavich\OneloginSamlBundle\OneLogin\AuthRegistryInterface;
use OneLogin\Saml2\Auth;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;

class SamlController extends AbstractController
{
    private AuthRegistryInterface $authRegistry;
    private IdpResolverInterface $idpResolver;
    private ?LoggerInterface $logger;

    public function __construct(
        AuthRegistryInterface $authRegistry,
        IdpResolverInterface $idpResolver,
        ?LoggerInterface $logger
    ) {
        $this->authRegistry = $authRegistry;
        $this->idpResolver = $idpResolver;
        $this->logger = $logger;
    }

    public function loginAction(Request $request)
    {
        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $session = $targetPath = $error = null;

        if ($request->hasSession()) {
            $session = $request->getSession();
            $firewallName = array_slice(explode('.', trim($request->attributes->get('_firewall_context'))), -1)[0];
            $targetPath = $session->get('_security.'.$firewallName.'.target_path');
        }

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if ($error instanceof \Exception) {
            throw new \RuntimeException($error->getMessage());
        }
        $oneLoginAuth = $this->getOneLoginAuth($request);

        $oneLoginAuth->login($targetPath);
    }

    public function metadataAction(Request $request)
    {
        $oneLoginAuth = $this->getOneLoginAuth($request);
        $metadata = $oneLoginAuth->getSettings()->getSPMetadata();

        $response = new Response($metadata);
        $response->headers->set('Content-Type', 'xml');

        return $response;
    }

    public function assertionConsumerServiceAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall.');
    }

    public function singleLogoutServiceAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
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
