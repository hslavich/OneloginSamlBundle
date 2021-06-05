<?php

namespace Hslavich\OneloginSamlBundle\EventListener\User;

use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;

class UserModifiedListener extends AbstractUserListener
{
    public function __invoke(UserModifiedEvent $event)
    {
        $this->handleEvent($event);
    }
}
