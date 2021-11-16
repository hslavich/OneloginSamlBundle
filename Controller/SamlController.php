<?php

namespace Hslavich\OneloginSamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;

class SamlController extends AbstractController
{
    public function loginAction(Request $request)
    {
        $session = $request->getSession();
        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $error = null;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if ($error instanceof \Exception) {
            throw new \RuntimeException($error->getMessage());
        }

        $firewallName = array_slice(explode('.', trim($request->attributes->get('_firewall_context'))), -1)[0];
        $this->get('onelogin_auth')->login($session->get('_security.'.$firewallName.'.target_path'));
    }

    public function metadataAction()
    {
        $auth = $this->get('onelogin_auth');
        $metadata = $auth->getSettings()->getSPMetadata();

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
}
