<?php
$type = strtolower((string) ($type ?? 'info'));
$title = (string) ($title ?? '');
$message = (string) ($message ?? '');

$allowed = ['success', 'info', 'warning', 'danger'];
if (!in_array($type, $allowed, true)) {
    $type = 'info';
}
if ($message === '') {
    return;
}
?>
<div class="cm-alert is-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" role="alert">
    <span class="cm-alert__icon">
        <i class="fas <?= $type === 'success' ? 'fa-circle-check' : ($type === 'danger' ? 'fa-circle-exclamation' : ($type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info')) ?>" aria-hidden="true"></i>
    </span>
    <div class="cm-alert__content">
        <?php if ($title !== ''): ?>
        <span class="cm-alert__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <span class="cm-alert__message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</div>
