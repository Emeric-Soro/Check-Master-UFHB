<div class="cm-form-group">
    <?php if (!empty($title)): ?>
    <div class="cm-form-group-header">
        <h3 class="cm-form-group-title">
            <?php if (!empty($icon)): ?><i class="fas <?= $icon ?>"></i><?php endif; ?>
            <?= htmlspecialchars($title) ?>
        </h3>
    </div>
    <?php endif; ?>
    <div class="cm-form-group-body">
        <div class="columns is-multiline">
            <?php if (!empty($columns)): ?>
                <?php foreach ($columns as $index => $colSize): ?>
                <div class="column is-<?= $colSize ?>">
                    <?= $content[$index] ?? '' ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="column">
                    <?= $content ?? '' ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
