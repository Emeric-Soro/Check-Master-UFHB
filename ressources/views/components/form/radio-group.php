<div class="field cm-field">
    <?php if (!empty($label)): ?>
    <label class="label">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control <?= ($inline ?? false) ? 'cm-radio-inline' : '' ?>">
        <?php foreach ($options ?? [] as $optValue => $optLabel): ?>
        <label class="radio <?= ($disabled ?? false) ? 'is-disabled' : '' ?>">
            <input 
                type="radio"
                name="<?= htmlspecialchars($name) ?>"
                value="<?= htmlspecialchars($optValue) ?>"
                <?= ((string)$optValue === (string)($value ?? '')) ? 'checked' : '' ?>
                <?= ($required ?? false) ? 'required' : '' ?>
                <?= ($disabled ?? false) ? 'disabled' : '' ?>
            >
            <?= htmlspecialchars($optLabel) ?>
        </label>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
