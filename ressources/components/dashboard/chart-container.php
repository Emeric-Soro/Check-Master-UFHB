<?php
$chart_id = (string) ($chart_id ?? ('cmChart_' . bin2hex(random_bytes(4))));
$title = (string) ($title ?? 'Graphique');
$subtitle = (string) ($subtitle ?? '');
$type = (string) ($type ?? 'bar');
$height = strtolower((string) ($height ?? 'md'));
$data = is_array($data ?? null) ? $data : ['labels' => [], 'datasets' => []];
$options = is_array($options ?? null) ? $options : [];

$height_map = [
    'sm' => 'is-sm',
    'md' => 'is-md',
    'lg' => 'is-lg',
    'xl' => 'is-xl',
];
$height_class = $height_map[$height] ?? $height_map['md'];
?>
<section class="cm-chart-container">
    <header class="cm-chart-container__header">
        <h3 class="cm-chart-container__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
        <?php if ($subtitle !== ''): ?>
        <p class="cm-chart-container__subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </header>

    <div class="cm-chart-container__body <?= htmlspecialchars($height_class, ENT_QUOTES, 'UTF-8') ?>">
        <canvas id="<?= htmlspecialchars($chart_id, ENT_QUOTES, 'UTF-8') ?>"></canvas>
    </div>
</section>

<script>
(function () {
    var canvas = document.getElementById(<?= json_encode($chart_id) ?>);
    if (!canvas || typeof window.Chart !== 'function') {
        return;
    }

    var ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    new Chart(ctx, {
        type: <?= json_encode($type) ?>,
        data: <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        options: <?= json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    });
})();
</script>
