<?php
$widthClass = '';
switch ($width ?? 'default') {
    case 'narrow': $widthClass = 'cm-side-panel--narrow'; break;
    case 'wide': $widthClass = 'cm-side-panel--wide'; break;
    default: $widthClass = '';
}
$panelId = $id ?? 'side-panel-' . uniqid();
?>
<div class="cm-side-panel-overlay" data-panel-id="<?= htmlspecialchars($panelId) ?>"></div>
<aside id="<?= htmlspecialchars($panelId) ?>" class="cm-side-panel <?= $widthClass ?>">
    <header class="cm-side-panel-header">
        <h3 class="cm-side-panel-title">
            <?= htmlspecialchars($title ?? 'Détails') ?>
        </h3>
        <button type="button" class="delete cm-side-panel-close" 
                data-panel-id="<?= htmlspecialchars($panelId) ?>"
                aria-label="Fermer"
                <?= !empty($close_url) ? 'data-close-url="' . htmlspecialchars($close_url) . '"' : '' ?>>
        </button>
    </header>
    
    <div class="cm-side-panel-body">
        <?php if ($loading ?? false): ?>
        <div class="cm-side-panel-loading">
            <span class="icon is-large"><i class="fas fa-spinner fa-spin fa-2x"></i></span>
            <p>Chargement...</p>
        </div>
        <?php else: ?>
        <?= $content ?? '' ?>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($footer)): ?>
    <footer class="cm-side-panel-footer">
        <?= $footer ?>
    </footer>
    <?php endif; ?>
</aside>
