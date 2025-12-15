<?php
// src/EventListener/LogoutListener.php

namespace App\EventListener;

use App\Entity\User;
use App\Service\ActivityLoggerService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutListener
{
    private ActivityLoggerService $activityLogger;

    public function __construct(ActivityLoggerService $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function __invoke(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if (!$token) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Log the logout activity
        $this->activityLogger->logLogout($user);
    }
}