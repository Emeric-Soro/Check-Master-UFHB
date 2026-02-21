<div class="cm-editor-wrapper">
    <?php if (!empty($toolbar)): ?>
    <div class="cm-editor-toolbar">
        <?= $toolbar ?>
    </div>
    <?php endif; ?>
    
    <div class="cm-editor-main">
        <?php if (!empty($sidebar)): ?>
        <aside class="cm-editor-sidebar">
            <?= $sidebar ?>
        </aside>
        <?php endif; ?>
        
        <main class="cm-editor-content">
            <?= $content ?? '' ?>
        </main>
    </div>
    
    <?php if (!empty($actions)): ?>
    <div class="cm-editor-actions">
        <?php foreach ($actions as $action): ?>
        <button class="button <?= $action['class'] ?? 'is-primary' ?>" 
                data-action="<?= htmlspecialchars($action['action'] ?? '') ?>">
            <i class="fas <?= $action['icon'] ?? '' ?>"></i>
            <?= htmlspecialchars($action['label'] ?? '') ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
