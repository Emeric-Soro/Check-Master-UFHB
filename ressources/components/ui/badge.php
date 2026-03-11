<?php
$text = (string) ($text ?? '');
$type = strtolower((string) ($type ?? 'info'));
$allowed = ['primary', 'success', 'info', 'warning', 'danger', 'light'];
if (!in_array($type, $allowed, true)) {
    $type = 'info';
}
if ($text === '') {
    return;
}
?>
<span class="cm-badge is-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
    <?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?>
</span>
