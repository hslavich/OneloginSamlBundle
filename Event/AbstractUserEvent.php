<?php

namespace Hslavich\OneloginSamlBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractUserEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    private $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
