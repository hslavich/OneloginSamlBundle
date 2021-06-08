<?php

namespace Hslavich\OneloginSamlBundle\EventListener\User;

use Hslavich\OneloginSamlBundle\Event\UserCreatedEvent;

class UserCreatedListener extends AbstractUserListener
{
    public function __invoke(UserCreatedEvent $event)
    {
        $this->handleEvent($event);
    }
}
