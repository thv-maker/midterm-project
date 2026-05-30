<?php

namespace App\Service;

use App\Entity\User;

class UserMercurePublisher
{
    public const TOPIC = '/users';

    public function __construct(
        private WebSocketPublisher $webSocketPublisher,
    ) {}

    public function publishCreated(User $user): void
    {
        $this->publish($user, 'created');
    }

    public function publishUpdated(User $user): void
    {
        $this->publish($user, 'updated');
    }

    public function publishDeleted(int $userId, ?string $email): void
    {
        $this->publishPayload([
            'type' => 'deleted',
            'userId' => $userId,
            'email' => $email,
        ]);
    }

    private function publish(User $user, string $type): void
    {
        $this->publishPayload([
            'type' => $type,
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
        ]);
    }

    private function publishPayload(array $payload): void
    {
        $this->webSocketPublisher->publish(self::TOPIC, $payload);
    }
}
