<?php

namespace Hslavich\OneloginSamlBundle\Event;

class UserCreatedEvent extends AbstractUserEvent
{
    const NAME = 'saml_user.created';
}
