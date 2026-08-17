<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function outputJson(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizeState(array $state, array $aircon): array
{
    $attributes = $state['attributes'] ?? [];
    return [
        'name' => $aircon['name'],
        'entity_id' => $aircon['entity_id'],
        'state' => $state['state'] ?? 'unavailable',
        'on' => ($state['state'] ?? 'off') !== 'off',
        'current_temperature' => $attributes['current_temperature'] ?? null,
        'target_temperature' => $attributes['temperature'] ?? null,
        'fan_mode' => $attributes['fan_mode'] ?? null,
        'hvac_modes' => $attributes['hvac_modes'] ?? [],
        'fan_modes' => $attributes['fan_modes'] ?? [],
    ];
}

function findAirconState(array $states, array $aircon): array
{
    foreach ($states as $state) {
        if (($state['entity_id'] ?? '') === $aircon['entity_id']) {
            return $state;
        }
    }

    foreach ($states as $state) {
        $entityId = (string) ($state['entity_id'] ?? '');
        $friendlyName = (string) ($state['attributes']['friendly_name'] ?? '');
        if (str_starts_with($entityId, 'climate.') && strcasecmp($friendlyName, $aircon['name']) === 0) {
            return $state;
        }
    }

    throw new RuntimeException("Geen klimaat-entiteit gevonden voor {$aircon['name']}.");
}

function resolvedAircon(array $aircon, array $state): array
{
    $aircon['entity_id'] = (string) $state['entity_id'];
    return $aircon;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $states = $haClient->getStates();
        $result = [];
        foreach (AIRCONS as $key => $aircon) {
            $state = findAirconState($states, $aircon);
            $result[$key] = normalizeState($state, resolvedAircon($aircon, $state));
        }
        outputJson(['ok' => true, 'aircons' => $result]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        outputJson(['ok' => false, 'error' => 'Methode niet toegestaan.'], 405);
    }

    $input = json_decode(file_get_contents('php://input') ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    if (!hash_equals($_SESSION['csrf_token'], (string) ($input['csrf_token'] ?? ''))) {
        outputJson(['ok' => false, 'error' => 'Ongeldige sessie. Vernieuw de pagina.'], 403);
    }

    $room = (string) ($input['room'] ?? '');
    if (!isset(AIRCONS[$room])) {
        outputJson(['ok' => false, 'error' => 'Onbekende ruimte.'], 422);
    }

    $states = $haClient->getStates();
    $currentState = findAirconState($states, AIRCONS[$room]);
    $aircon = resolvedAircon(AIRCONS[$room], $currentState);
    $entityId = $aircon['entity_id'];
    $action = (string) ($input['action'] ?? '');
    $service = '';
    $data = ['entity_id' => $entityId];

    switch ($action) {
        case 'turn_on':
        case 'turn_off':
            $service = $action;
            break;
        case 'set_temperature':
            $temperature = filter_var($input['temperature'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($temperature === false || $temperature < 16 || $temperature > 30) {
                outputJson(['ok' => false, 'error' => 'Kies een temperatuur tussen 16 en 30 °C.'], 422);
            }
            $service = 'set_temperature';
            $data['temperature'] = round((float) $temperature * 2) / 2;
            break;
        case 'set_mode':
            $mode = (string) ($input['mode'] ?? '');
            if (!in_array($mode, ['auto', 'cool', 'heat', 'dry', 'fan_only'], true)) {
                outputJson(['ok' => false, 'error' => 'Ongeldige stand.'], 422);
            }
            $service = 'set_hvac_mode';
            $data['hvac_mode'] = $mode;
            break;
        case 'set_fan':
            $fanMode = trim((string) ($input['fan_mode'] ?? ''));
            if ($fanMode === '' || strlen($fanMode) > 80) {
                outputJson(['ok' => false, 'error' => 'Ongeldige ventilatorstand.'], 422);
            }
            $service = 'set_fan_mode';
            $data['fan_mode'] = $fanMode;
            break;
        default:
            outputJson(['ok' => false, 'error' => 'Onbekende opdracht.'], 422);
    }

    $haClient->callService('climate', $service, $data);
    usleep(400000);
    outputJson([
        'ok' => true,
        'aircon' => normalizeState($haClient->getState($entityId), $aircon),
    ]);
} catch (Throwable $exception) {
    outputJson(['ok' => false, 'error' => $exception->getMessage()], 502);
}
