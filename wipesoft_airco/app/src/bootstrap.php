<?php

declare(strict_types=1);

session_name('wipesoft_airco');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once __DIR__ . '/HomeAssistantClient.php';

$options = [];
if (is_readable('/data/options.json')) {
    $options = json_decode(file_get_contents('/data/options.json') ?: '{}', true) ?: [];
}

function climateEntity(array $options, string $key, string $fallback): string
{
    $entityId = trim((string) ($options[$key] ?? $fallback));
    if (!preg_match('/^climate\.[a-z0-9_]+$/', $entityId)) {
        throw new RuntimeException("Ongeldige Home Assistant-entiteit bij {$key}.");
    }
    return $entityId;
}

define('AIRCONS', [
    'studio' => [
        'name' => 'Studio',
        'entity_id' => climateEntity($options, 'studio_entity', 'climate.airco_studio'),
    ],
    'slaapkamer' => [
        'name' => 'Slaapkamer',
        'entity_id' => climateEntity($options, 'slaapkamer_entity', 'climate.airco_slaapkamer'),
    ],
]);

$haClient = new HomeAssistantClient(
    'http://supervisor/core/api',
    getenv('SUPERVISOR_TOKEN') ?: '',
);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
