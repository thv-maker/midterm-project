<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FcmNotificationService
{
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $firebaseProjectId,
        private string $firebaseServiceAccount,
    ) {}

    public function send(string $fcmToken, string $title, string $body, array $data = []): void
    {
        if (!$this->firebaseProjectId || !$this->firebaseServiceAccount) {
            return;
        }

        try {
            $accessToken = $this->getAccessToken();

            $this->httpClient->request(
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
                            ],
                        ],
                    ],
                ]
            )->getContent(); // trigger the request
        } catch (\Throwable) {
            // Silently fail — notifications are non-critical
        }
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $sa = json_decode(base64_decode($this->firebaseServiceAccount), true);

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

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
