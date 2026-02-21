<?php
/**
 * Stat Card Widget
 * Params: 
 * - title: Label
 * - value: Number/Text
 * - icon: FontAwesome class
 * - color: blue, green, info, success, etc.
 * - tendency: (optional) percentage or text
 */
$cardColor = $color ?? 'blue';
$gradientClass = "cm-gradient-{$cardColor}";
if (!in_array($cardColor, ['blue', 'green'])) {
    $gradientClass = "is-{$cardColor}";
}
?>
<div class="cm-gradient-card <?= $gradientClass ?> cm-hover-lift">
    <div class="is-flex is-justify-content-space-between is-align-items-center">
        <div class="cm-stat-icon" style="background: rgba(255,255,255,0.2);">
            <i class="fas fa-<?= $icon ?? 'chart-bar' ?> has-text-white"></i>
        </div>
        <div class="has-text-right">
            <h3 class="is-size-2 has-text-weight-bold has-text-white"><?= $value ?? '0' ?></h3>
            <p class="is-size-7 has-text-white" style="opacity:0.9;"><?= htmlspecialchars($title ?? '') ?></p>
        </div>
    </div>
</div>
