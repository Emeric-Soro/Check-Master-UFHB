<?php
/**
 * Alert Component
 * Params:
 * - type: success, danger, warning, info, primary, link (Bulma notification types)
 * - message: The alert message text (HTML allowed if $raw is true)
 * - title: (optional) Bold title before the message
 * - dismissible: (optional, default true) Show close button
 * - icon: (optional) FontAwesome icon class (auto-detected from type if not set)
 * - raw: (optional, default false) If true, message is rendered as raw HTML
 */
$alertType = $type ?? 'info';
$isDismissible = $dismissible ?? true;
$isRaw = $raw ?? false;

// Map type to Bulma color class
$colorMap = [
    'success' => 'is-success',
    'error'   => 'is-danger',
    'danger'  => 'is-danger',
    'warning' => 'is-warning',
    'info'    => 'is-info',
    'primary' => 'is-primary',
    'link'    => 'is-link',
];
$colorClass = $colorMap[$alertType] ?? 'is-info';

// Auto-detect icon from type
$defaultIcons = [
    'success' => 'fa-check-circle',
    'error'   => 'fa-exclamation-circle',
    'danger'  => 'fa-exclamation-circle',
    'warning' => 'fa-exclamation-triangle',
    'info'    => 'fa-info-circle',
    'primary' => 'fa-info-circle',
    'link'    => 'fa-info-circle',
];
$alertIcon = $icon ?? ($defaultIcons[$alertType] ?? 'fa-info-circle');
?>
<div class="notification <?= $colorClass ?> is-light cm-alert" role="alert">
    <?php if ($isDismissible): ?>
    <button class="delete cm-alert-close"></button>
    <?php endif; ?>
    <div class="is-flex is-align-items-center">
        <span class="icon mr-3">
            <i class="fas <?= $alertIcon ?>"></i>
        </span>
        <div>
            <?php if (!empty($title)): ?>
            <p class="has-text-weight-bold mb-1"><?= htmlspecialchars($title) ?></p>
            <?php endif; ?>
            <p><?= $isRaw ? ($message ?? '') : htmlspecialchars($message ?? '') ?></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cm-alert .cm-alert-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var alert = this.closest('.cm-alert');
            if (alert) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 300);
            }
        });
    });
});
</script>
