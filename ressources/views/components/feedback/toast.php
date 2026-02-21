<?php
/**
 * Toast Notification Component
 * Params (via $toast array from layout, or via extract from ComponentHelper):
 * - type: success, danger, warning, info (Bulma notification types)
 * - message: The toast message text
 * - title: (optional) Bold title
 * - duration: (optional, default 5000) Auto-dismiss duration in ms (0 = no auto-dismiss)
 * - icon: (optional) FontAwesome icon class (auto-detected from type if not set)
 */
// Support both direct variable usage (from layout foreach) and extracted params
$toastType = $toast['type'] ?? $type ?? 'info';
$toastMessage = $toast['message'] ?? $message ?? '';
$toastTitle = $toast['title'] ?? $title ?? '';
$toastDuration = $toast['duration'] ?? $duration ?? 5000;
$toastIcon = $toast['icon'] ?? $icon ?? null;

// Map type to Bulma color class
$colorMap = [
    'success' => 'is-success',
    'error'   => 'is-danger',
    'danger'  => 'is-danger',
    'warning' => 'is-warning',
    'info'    => 'is-info',
];
$colorClass = $colorMap[$toastType] ?? 'is-info';

// Auto-detect icon
$defaultIcons = [
    'success' => 'fa-check-circle',
    'error'   => 'fa-exclamation-circle',
    'danger'  => 'fa-exclamation-circle',
    'warning' => 'fa-exclamation-triangle',
    'info'    => 'fa-info-circle',
];
$iconClass = $toastIcon ?? ($defaultIcons[$toastType] ?? 'fa-info-circle');

$toastId = 'toast-' . uniqid();
?>
<div id="<?= $toastId ?>" class="notification <?= $colorClass ?> cm-toast" 
     data-duration="<?= (int)$toastDuration ?>" role="status" aria-live="polite">
    <button class="delete cm-toast-close"></button>
    <div class="is-flex is-align-items-center">
        <span class="icon mr-2">
            <i class="fas <?= $iconClass ?>"></i>
        </span>
        <div>
            <?php if (!empty($toastTitle)): ?>
            <p class="has-text-weight-bold"><?= htmlspecialchars($toastTitle) ?></p>
            <?php endif; ?>
            <p><?= htmlspecialchars($toastMessage) ?></p>
        </div>
    </div>
</div>
