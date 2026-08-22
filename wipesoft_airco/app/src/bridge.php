<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/RemoteBridge.php';

$remoteUrl = rtrim(trim((string) ($options['remote_url'] ?? '')), '/');
$bridgeToken = trim((string) ($options['bridge_token'] ?? ''));
$pollInterval = max(2, min(30, (int) ($options['poll_interval'] ?? 3)));

if ($remoteUrl === '' || $bridgeToken === '') {
    fwrite(STDOUT, "WIPEsoft remote bridge is nog niet geconfigureerd.\n");
    exit(0);
}

try {
    $bridge = new RemoteBridge($haClient, $remoteUrl, $bridgeToken);
} catch (Throwable $exception) {
    fwrite(STDERR, 'WIPEsoft bridge configuratiefout: ' . $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "WIPEsoft remote bridge is gestart.\n");
while (true) {
    try {
        $bridge->runOnce();
        sleep($pollInterval);
    } catch (Throwable $exception) {
        fwrite(STDERR, '[' . gmdate(DATE_ATOM) . '] Bridgefout: ' . $exception->getMessage() . "\n");
        sleep(max(10, $pollInterval));
    }
}
