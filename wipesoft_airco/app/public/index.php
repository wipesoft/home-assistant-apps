<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

function icon(string $name): string
{
    $icons = [
        'power' => '<path d="M12 2v10M6.3 5.7a8 8 0 1 0 11.4 0"/>',
        'thermometer' => '<path d="M14 14.8V5a4 4 0 0 0-8 0v9.8a6 6 0 1 0 8 0Z"/><path d="M10 12v6"/>',
        'wind' => '<path d="M3 8h10a3 3 0 1 0-3-3M3 12h15a3 3 0 1 1-3 3M3 16h7"/>',
        'sparkles' => '<path d="m12 3-1.2 3.1L8 7.5l2.8 1.4L12 12l1.2-3.1L16 7.5l-2.8-1.4L12 3ZM5 13l-.8 2.2L2 16l2.2.8L5 19l.8-2.2L8 16l-2.2-.8L5 13ZM18 13l-.7 1.7-1.8.8 1.8.8L18 18l.7-1.7 1.8-.8L18 13Z"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($icons[$name] ?? '') . '</svg>';
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#07111f">
    <title>WIPEsoft Climate</title>
    <link rel="stylesheet" href="style.css?v=0.3.0">
    <link rel="stylesheet" href="interaction.css?v=0.3.0">
</head>
<body>
<div class="ambient ambient-one"></div><div class="ambient ambient-two"></div>
<main class="shell">
    <header class="topbar">
        <div class="brand"><span class="brand-mark"><?= icon('wind') ?></span><div><p class="eyebrow">WIPEsoft climate</p><h1>Mijn comfort</h1></div></div>
        <div class="connection" id="connection"><span></span><b>Verbinden…</b></div>
    </header>
    <section class="welcome">
        <div><span class="welcome-icon"><?= icon('sparkles') ?></span><p>Slim binnenklimaat</p></div>
        <strong>Precies de juiste luchtstroom,<br>in iedere ruimte.</strong>
    </section>
    <section class="grid" id="aircons" aria-live="polite">
        <?php foreach (AIRCONS as $key => $aircon): ?>
            <article class="climate-card is-loading" data-room="<?= htmlspecialchars($key) ?>" data-mode="off">
                <div class="card-glow"></div>
                <div class="card-head">
                    <div><p class="room-label"><span></span> Klimaatzone</p><h2><?= htmlspecialchars($aircon['name']) ?></h2><p class="activity" data-state>Gegevens ophalen…</p></div>
                    <button class="power" type="button" aria-label="<?= htmlspecialchars($aircon['name']) ?> aan- of uitzetten"><?= icon('power') ?><span>—</span></button>
                </div>
                <div class="climate-display">
                    <div class="room-reading"><span><?= icon('thermometer') ?> Kamertemperatuur</span><strong data-current>—</strong></div>
                    <div class="thermostat" data-thermostat><div class="thermostat-inner"><span>Gewenst</span><div><strong data-target>—</strong><small>°</small></div></div></div>
                    <div class="temp-buttons"><button type="button" data-step="-0.5" aria-label="Temperatuur verlagen">−</button><button type="button" data-step="0.5" aria-label="Temperatuur verhogen">+</button></div>
                </div>
                <section class="control-block">
                    <div class="section-title"><span>Bedrijfsstand</span><small data-mode-label>Uitgeschakeld</small></div>
                    <div class="mode-picker" data-mode-picker></div>
                </section>
                <section class="control-block">
                    <div class="section-title"><span>Ventilator</span><small data-fan-label>—</small></div>
                    <div class="fan-picker" data-fan-picker></div>
                </section>
                <section class="airflow-panel">
                    <div class="section-title"><span>Luchtstroom</span><small>Lamellen</small></div>
                    <div class="airflow-content">
                        <div class="airflow-visual" aria-hidden="true"><div class="indoor-unit"><i></i><i></i><i></i></div><div class="air-stream stream-1"></div><div class="air-stream stream-2"></div><div class="air-stream stream-3"></div><span data-airflow-caption>Gericht</span></div>
                        <div class="swing-controls">
                            <label><span>Verticaal</span><div class="select-wrap"><select data-swing-vertical disabled></select><?= icon('chevron') ?></div></label>
                            <label><span>Horizontaal</span><div class="select-wrap"><select data-swing-horizontal disabled></select><?= icon('chevron') ?></div></label>
                        </div>
                    </div>
                </section>
            </article>
        <?php endforeach; ?>
    </section>
    <div class="toast" id="message" role="status" aria-live="polite"></div>
    <footer><span>●</span> Lokaal verbonden met Home Assistant</footer>
</main>
<script>window.APP_CONFIG = <?= json_encode(['api' => 'api.php', 'csrfToken' => $_SESSION['csrf_token']], JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="app.js?v=0.3.0" defer></script>
</body>
</html>
