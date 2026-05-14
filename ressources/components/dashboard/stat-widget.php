<?php
$value = (string) ($value ?? '0');
$label = (string) ($label ?? '');
$subtitle = (string) ($subtitle ?? '');
$icon = (string) ($icon ?? 'fa-chart-line');
$url = (string) ($url ?? '');
$ajax = !isset($ajax) || $ajax;
$color = strtolower((string) ($color ?? 'primary'));
$allowed = ['primary', 'success', 'warning', 'info', 'danger'];
if (!in_array($color, $allowed, true)) {
    $color = 'primary';
}

$tag = $url !== '' ? 'a' : 'article';
$attrs = $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' : '';
if ($url !== '' && $ajax) {
    $attrs .= ' data-cm-ajax-link="true"';
}
$clickableClass = $url !== '' ? ' is-clickable' : '';
?>
<<?= $tag . $attrs ?> class="cm-stat-card is-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . $clickableClass ?>">
    <span class="cm-stat-card__icon">
        <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
    </span>
    <div class="cm-stat-card__content">
        <div class="cm-stat-card__value"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if ($label !== ''): ?><div class="cm-stat-card__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($subtitle !== ''): ?><div class="cm-stat-card__subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
</<?= $tag ?>>
