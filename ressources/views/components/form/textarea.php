<?php $hasError = !empty($error); ?>
<div class="field cm-field cm-field-etendue <?= $hasError ? 'has-error' : '' ?>">
    <?php if (!empty($label)): ?>
    <label class="label" for="field-<?= htmlspecialchars($name) ?>">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control">
        <textarea 
            class="textarea <?= $hasError ? 'is-danger' : '' ?>" 
            name="<?= htmlspecialchars($name) ?>"
            id="field-<?= htmlspecialchars($name) ?>"
            rows="<?= $rows ?? 4 ?>"
            placeholder="<?= htmlspecialchars($placeholder ?? '') ?>"
            <?= ($required ?? false) ? 'required' : '' ?>
            <?= ($disabled ?? false) ? 'disabled' : '' ?>
            <?= ($readonly ?? false) ? 'readonly' : '' ?>
            <?php foreach ($attrs ?? [] as $attr => $val): ?>
                <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
            <?php endforeach; ?>
        ><?= htmlspecialchars($value ?? '') ?></textarea>
    </div>
    <?php if ($hasError): ?>
    <p class="help is-danger"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
