<?php

namespace App\Service;

use App\Entity\ActivityLog;

class ActivityLogMercurePublisher
{
    public const TOPIC = '/activity-logs';

    public function __construct(
        private WebSocketPublisher $webSocketPublisher,
    ) {}

    public function publishCreated(ActivityLog $log): void
    {
        $createdAt = $log->getDateTime();

        $this->publishPayload([
            'type' => 'created',
            'logId' => $log->getId(),
            'userId' => $log->getUserId(),
            'username' => $log->getUsername(),
            'role' => $log->getRole(),
            'action' => $log->getAction(),
            'targetData' => $log->getTargetData(),
            'createdAt' => $createdAt?->format(DATE_ATOM),
        ]);
    }

    private function publishPayload(array $payload): void
    {
        $this->webSocketPublisher->publish(self::TOPIC, $payload);
    }
}
