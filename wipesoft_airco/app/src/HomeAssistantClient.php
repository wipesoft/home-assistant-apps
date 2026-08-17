<?php

declare(strict_types=1);

final class HomeAssistantClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
    ) {
    }

    public function getState(string $entityId): array
    {
        return $this->request('GET', '/states/' . rawurlencode($entityId));
    }

    public function getStates(): array
    {
        return $this->request('GET', '/states');
    }

    public function callService(string $domain, string $service, array $data): array
    {
        return $this->request('POST', "/services/{$domain}/{$service}", $data);
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        if ($this->token === '') {
            throw new RuntimeException('De interne Home Assistant-toegang is niet beschikbaar.');
        }

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
        ];

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 12,
            ],
        ];

        if ($body !== null) {
            $options['http']['content'] = json_encode($body, JSON_THROW_ON_ERROR);
        }

        $response = @file_get_contents(
            rtrim($this->baseUrl, '/') . $path,
            false,
            stream_context_create($options),
        );

        $responseHeaders = $http_response_header ?? [];
        preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $statusMatch);
        $status = isset($statusMatch[1]) ? (int) $statusMatch[1] : 0;

        if ($response === false) {
            $error = error_get_last()['message'] ?? 'onbekende verbindingsfout';
            throw new RuntimeException('Home Assistant is niet bereikbaar: ' . $error);
        }

        $decoded = json_decode($response, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) ? ($decoded['message'] ?? 'Onbekende fout') : 'Onbekende fout';
            throw new RuntimeException("Home Assistant antwoordde met {$status}: {$message}");
        }

        return is_array($decoded) ? $decoded : [];
    }
}
