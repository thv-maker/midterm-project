<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebSocketPublisher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $broadcastUrl,
        private string $broadcastSecret,
        private LoggerInterface $logger,
    ) {}

    public function publish(string $topic, array $data): void
    {
        try {
            $response = $this->httpClient->request('POST', $this->broadcastUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->broadcastSecret,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'topic' => $topic,
                    'data' => $data,
                ],
                'timeout' => 3,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getContent(false);

            if ($status < 200 || $status >= 300) {
                $this->logger->warning('WebSocket broadcast failed', [
                    'topic' => $topic,
                    'status' => $status,
                    'body' => $body,
                    'url' => $this->broadcastUrl,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('WebSocket broadcast error: {message}', [
                'topic' => $topic,
                'message' => $e->getMessage(),
                'url' => $this->broadcastUrl,
            ]);
        }
    }
}
