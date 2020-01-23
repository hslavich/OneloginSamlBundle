<?php

namespace Hslavich\OneloginSamlBundle\Security\Authentication;

use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;

class SamlAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    protected function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        $relayState = $request->get('RelayState');
        if (null !== $relayState && $relayState !== $this->httpUtils->generateUri($request, $this->options['login_path'])) {
            return $relayState;
        }

        return parent::determineTargetUrl($request);
    }
}
