<?php

namespace Hslavich\OneloginSamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SamlController extends Controller
{
    public function loginAction()
    {
        $this->get('onelogin_auth')->login();
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
