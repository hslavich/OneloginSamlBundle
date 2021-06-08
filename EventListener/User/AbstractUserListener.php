<?php

namespace Hslavich\OneloginSamlBundle\EventListener\User;

use Hslavich\OneloginSamlBundle\Event\AbstractUserEvent;

abstract class AbstractUserListener
{
    protected $entryManager;
    protected $needPersist;

    public function __construct($entryManager, $needPersist = false)
    {
        $this->entryManager = $entryManager;
        $this->needPersist = $needPersist;
    }

    protected function handleEvent(AbstractUserEvent $event): void
    {
        if ($this->needPersist && $this->entryManager) {
            $this->entryManager->persist($event->getUser());
            $this->entryManager->flush();
        }
    }
}
