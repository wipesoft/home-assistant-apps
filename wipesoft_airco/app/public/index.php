<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#102a43">
    <title>WIPEsoft Airco</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div><p class="eyebrow">Home Assistant</p><h1>Airco</h1></div>
        <div class="connection" id="connection"><span></span><b>Verbinden…</b></div>
    </header>

    <section class="grid" id="aircons" aria-live="polite">
        <?php foreach (AIRCONS as $key => $aircon): ?>
            <article class="climate-card is-loading" data-room="<?= htmlspecialchars($key) ?>">
                <div class="card-head">
                    <div><p class="room-label">Ruimte</p><h2><?= htmlspecialchars($aircon['name']) ?></h2></div>
                    <button class="power" type="button" aria-label="<?= htmlspecialchars($aircon['name']) ?> aan- of uitzetten"><span>⏻</span></button>
                </div>
                <div class="temperature">
                    <p>Momenteel <strong data-current>—</strong></p>
                    <div class="setpoint">
                        <button type="button" data-step="-0.5" aria-label="Temperatuur verlagen">−</button>
                        <div><strong data-target>—</strong><span>°C</span></div>
                        <button type="button" data-step="0.5" aria-label="Temperatuur verhogen">+</button>
                    </div>
                </div>
                <div class="controls">
                    <label>Stand<select data-mode disabled></select></label>
                    <label>Ventilator<select data-fan disabled></select></label>
                </div>
                <p class="state-line" data-state>Gegevens ophalen…</p>
            </article>
        <?php endforeach; ?>
    </section>
    <p class="message" id="message" role="status"></p>
</main>
<script>
window.APP_CONFIG = <?= json_encode(['api' => 'api.php', 'csrfToken' => $_SESSION['csrf_token']], JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="app.js" defer></script>
</body>
</html>

