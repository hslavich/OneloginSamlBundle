<?php

namespace Hslavich\OneloginSamlBundle\Security\Http\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;

class SamlAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    protected function determineTargetUrl(Request $request): string
    {
        if ($this->options['always_use_default_target_path']) {
            return (string)$this->options['default_target_path'];
        }

        $relayState = $request->get('RelayState');
        if (null !== $relayState) {
            $relayState = (string)$relayState;
            if ($relayState !== $this->httpUtils->generateUri($request, $this->options['login_path'])) {
                return $relayState;
            }
        }

        return parent::determineTargetUrl($request);
    }
}
