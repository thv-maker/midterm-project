<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FcmNotificationService
{
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;
    private ?array $serviceAccount = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $firebaseProjectId,
        private string $firebaseServiceAccount,
        private LoggerInterface $logger,
    ) {}

    public function isConfigured(): bool
    {
        return $this->firebaseProjectId !== '' && $this->resolveServiceAccount() !== null;
    }

    public function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('FCM skipped: FIREBASE_PROJECT_ID or FIREBASE_SERVICE_ACCOUNT_B64 is not configured.');

            return false;
        }

        try {
            $accessToken = $this->getAccessToken();

            $response = $this->httpClient->request(
                'POST',
                "https://fcm.googleapis.com/v1/projects/{$this->firebaseProjectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'message' => [
                            'token' => $fcmToken,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => array_map('strval', $data),
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'orders',
                                    'sound' => 'default',
                                ],
                            ],
                            'apns' => [
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $this->logger->error('FCM send failed', [
                    'status' => $statusCode,
                    'body' => $response->getContent(false),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('FCM send exception: ' . $exception->getMessage());

            return false;
        }
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $sa = $this->resolveServiceAccount();
        if ($sa === null) {
            throw new \RuntimeException('Firebase service account is not configured.');
        }

        $now = time();
        $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64url(json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$claims}";
        openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = "{$signingInput}." . $this->base64url($signature);

        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ])->toArray();

        $this->accessToken = $response['access_token'];
        $this->tokenExpiresAt = $now + (int) ($response['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    private function resolveServiceAccount(): ?array
    {
        if ($this->serviceAccount !== null) {
            return $this->serviceAccount;
        }

        $raw = trim($this->firebaseServiceAccount);
        if ($raw === '') {
            return null;
        }

        $decoded = base64_decode($raw, true);
        if ($decoded !== false && str_starts_with(trim($decoded), '{')) {
            $raw = $decoded;
        }

        $parsed = json_decode($raw, true);
        if (!is_array($parsed) || empty($parsed['client_email']) || empty($parsed['private_key'])) {
            return null;
        }

        $this->serviceAccount = $parsed;

        return $this->serviceAccount;
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
