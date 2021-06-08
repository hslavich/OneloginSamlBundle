<?php

namespace Hslavich\OneloginSamlBundle\EventListener\User;

use Hslavich\OneloginSamlBundle\Event\UserModifiedEvent;

class UserModifiedListener extends AbstractUserListener
{
    public function onUserModified(UserModifiedEvent $event)
    {
        $this->handleEvent($event);
    }
}
