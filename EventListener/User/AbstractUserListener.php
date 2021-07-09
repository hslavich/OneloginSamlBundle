<?php

namespace Hslavich\OneloginSamlBundle\EventListener\User;

use Doctrine\ORM\EntityManagerInterface;
use Hslavich\OneloginSamlBundle\Event\AbstractUserEvent;

abstract class AbstractUserListener
{
    protected $entryManager;
    protected $needPersist;

    public function __construct(?EntityManagerInterface $entryManager, bool $needPersist)
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
