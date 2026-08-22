<?php

declare(strict_types=1);

final class RemoteBridge
{
    public function __construct(
        private readonly HomeAssistantClient $homeAssistant,
        private readonly string $remoteUrl,
        private readonly string $token,
    ) {
        if (!str_starts_with($remoteUrl, 'https://')) {
            throw new RuntimeException('De externe bridge-URL moet HTTPS gebruiken.');
        }
    }

    public function runOnce(): void
    {
        $response = $this->remoteRequest('/api/bridge/poll.php', []);
        $results = [];

        foreach (($response['commands'] ?? []) as $command) {
            $id = (int) ($command['id'] ?? 0);
            try {
                $this->execute($command);
                $results[] = ['id' => $id, 'ok' => true];
            } catch (Throwable $exception) {
                $results[] = ['id' => $id, 'ok' => false, 'error' => $exception->getMessage()];
            }
        }

        if ($results !== []) {
            usleep(500000);
        }

        $this->remoteRequest('/api/bridge/report.php', [
            'commands' => $results,
            'aircons' => $this->currentStates(),
            'bridge_time' => gmdate(DATE_ATOM),
        ]);
    }

    private function execute(array $command): void
    {
        $room = (string) ($command['room'] ?? '');
        if (!isset(AIRCONS[$room])) {
            throw new RuntimeException('Onbekende ruimte.');
        }

        $states = $this->homeAssistant->getStates();
        $state = $this->findState($states, AIRCONS[$room]);
        $entityId = (string) $state['entity_id'];
        $payload = is_array($command['payload'] ?? null) ? $command['payload'] : [];
        $action = (string) ($command['action'] ?? '');
        $data = ['entity_id' => $entityId];

        switch ($action) {
            case 'turn_on':
            case 'turn_off':
                $service = $action;
                break;
            case 'set_temperature':
                $temperature = filter_var($payload['temperature'] ?? null, FILTER_VALIDATE_FLOAT);
                $minimum = (float) ($state['attributes']['min_temp'] ?? 16);
                $maximum = (float) ($state['attributes']['max_temp'] ?? 30);
                if ($temperature === false || $temperature < $minimum || $temperature > $maximum) {
                    throw new RuntimeException('Ongeldige temperatuur.');
                }
                $service = 'set_temperature';
                $data['temperature'] = round((float) $temperature * 2) / 2;
                break;
            case 'set_mode':
                $mode = (string) ($payload['mode'] ?? '');
                if (!in_array($mode, ['auto', 'cool', 'heat', 'dry', 'fan_only'], true)) {
                    throw new RuntimeException('Ongeldige bedrijfsstand.');
                }
                $service = 'set_hvac_mode';
                $data['hvac_mode'] = $mode;
                break;
            case 'set_fan':
                $fanMode = (string) ($payload['fan_mode'] ?? '');
                if (!in_array($fanMode, $state['attributes']['fan_modes'] ?? [], true)) {
                    throw new RuntimeException('Ongeldige ventilatorstand.');
                }
                $service = 'set_fan_mode';
                $data['fan_mode'] = $fanMode;
                break;
            case 'set_swing_vertical':
                $swingMode = (string) ($payload['swing_mode'] ?? '');
                if (!in_array($swingMode, $state['attributes']['swing_modes'] ?? [], true)) {
                    throw new RuntimeException('Ongeldige verticale lamellenstand.');
                }
                $service = 'set_swing_mode';
                $data['swing_mode'] = $swingMode;
                break;
            case 'set_swing_horizontal':
                $swingMode = (string) ($payload['swing_mode'] ?? '');
                if (!in_array($swingMode, $state['attributes']['swing_horizontal_modes'] ?? [], true)) {
                    throw new RuntimeException('Ongeldige horizontale lamellenstand.');
                }
                $service = 'set_horizontal_swing_mode';
                $data['swing_mode'] = $swingMode;
                break;
            default:
                throw new RuntimeException('Onbekende opdracht.');
        }

        $this->homeAssistant->callService('climate', $service, $data);
    }

    private function currentStates(): array
    {
        $states = $this->homeAssistant->getStates();
        $result = [];
        foreach (AIRCONS as $room => $aircon) {
            try {
                $state = $this->findState($states, $aircon);
                $result[$room] = $this->normalizeState($state, $aircon['name']);
            } catch (Throwable $exception) {
                $result[$room] = [
                    'name' => $aircon['name'],
                    'state' => 'unavailable',
                    'on' => false,
                    'error' => $exception->getMessage(),
                ];
            }
        }
        return $result;
    }

    private function findState(array $states, array $aircon): array
    {
        foreach ($states as $state) {
            if (($state['entity_id'] ?? '') === $aircon['entity_id']) {
                return $state;
            }
        }
        foreach ($states as $state) {
            $entityId = (string) ($state['entity_id'] ?? '');
            $name = (string) ($state['attributes']['friendly_name'] ?? '');
            if (str_starts_with($entityId, 'climate.') && strcasecmp($name, $aircon['name']) === 0) {
                return $state;
            }
        }
        throw new RuntimeException('Klimaat-entiteit niet gevonden.');
    }

    private function normalizeState(array $state, string $name): array
    {
        $attributes = $state['attributes'] ?? [];
        $value = (string) ($state['state'] ?? 'unavailable');
        return [
            'name' => $name,
            'state' => $value,
            'on' => !in_array($value, ['off', 'unavailable', 'unknown'], true),
            'available' => !in_array($value, ['unavailable', 'unknown'], true),
            'current_temperature' => $attributes['current_temperature'] ?? null,
            'target_temperature' => $attributes['temperature'] ?? null,
            'min_temperature' => $attributes['min_temp'] ?? 16,
            'max_temperature' => $attributes['max_temp'] ?? 30,
            'fan_mode' => $attributes['fan_mode'] ?? null,
            'swing_mode' => $attributes['swing_mode'] ?? null,
            'swing_horizontal_mode' => $attributes['swing_horizontal_mode'] ?? null,
            'hvac_modes' => $attributes['hvac_modes'] ?? [],
            'fan_modes' => $attributes['fan_modes'] ?? [],
            'swing_modes' => $attributes['swing_modes'] ?? [],
            'swing_horizontal_modes' => $attributes['swing_horizontal_modes'] ?? [],
            'hvac_action' => $attributes['hvac_action'] ?? null,
        ];
    }

    private function remoteRequest(string $path, array $body): array
    {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'Accept: application/json',
            ]),
            'content' => json_encode($body, JSON_THROW_ON_ERROR),
            'ignore_errors' => true,
            'timeout' => 20,
        ]]);
        $response = @file_get_contents($this->remoteUrl . $path, false, $context);
        $headers = $http_response_header ?? [];
        preg_match('/\s(\d{3})\s/', $headers[0] ?? '', $match);
        $status = isset($match[1]) ? (int) $match[1] : 0;
        $decoded = is_string($response) ? json_decode($response, true) : null;
        if ($response === false || $status < 200 || $status >= 300 || !is_array($decoded)) {
            $message = is_array($decoded) ? ($decoded['error'] ?? 'ongeldig antwoord') : 'geen geldig antwoord';
            throw new RuntimeException("Externe bridge antwoordde met {$status}: {$message}");
        }
        return $decoded;
    }
}
