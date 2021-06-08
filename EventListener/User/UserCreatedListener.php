<?php

namespace Hslavich\OneloginSamlBundle\EventListener\User;

use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;

class UserCreatedListener extends AbstractUserListener
{
    public function onUserCreated(UserCreatedEvent $event)
    {
        $this->handleEvent($event);
    }
}
