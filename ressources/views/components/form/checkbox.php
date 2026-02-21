<div class="field cm-field">
    <div class="control">
        <label class="checkbox">
            <input 
                type="checkbox"
                name="<?= htmlspecialchars($name) ?>"
                value="<?= htmlspecialchars($value ?? '1') ?>"
                <?= ($checked ?? false) ? 'checked' : '' ?>
                <?= ($disabled ?? false) ? 'disabled' : '' ?>
                <?php foreach ($attrs ?? [] as $attr => $val): ?>
                    <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
                <?php endforeach; ?>
            >
            <?= htmlspecialchars($label ?? '') ?>
        </label>
    </div>
    <?php if (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
