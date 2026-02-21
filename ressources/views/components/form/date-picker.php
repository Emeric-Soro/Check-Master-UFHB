<?php $hasError = !empty($error); ?>
<div class="field cm-field cm-field-standard <?= $hasError ? 'has-error' : '' ?>">
    <?php if (!empty($label)): ?>
    <label class="label" for="field-<?= htmlspecialchars($name) ?>">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control has-icons-left">
        <input 
            class="input <?= $hasError ? 'is-danger' : '' ?>" 
            type="date"
            name="<?= htmlspecialchars($name) ?>"
            id="field-<?= htmlspecialchars($name) ?>"
            value="<?= htmlspecialchars($value ?? '') ?>"
            <?= ($required ?? false) ? 'required' : '' ?>
            <?= ($disabled ?? false) ? 'disabled' : '' ?>
            <?= !empty($min) ? 'min="' . htmlspecialchars($min) . '"' : '' ?>
            <?= !empty($max) ? 'max="' . htmlspecialchars($max) . '"' : '' ?>
            <?php foreach ($attrs ?? [] as $attr => $val): ?>
                <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
            <?php endforeach; ?>
        >
        <span class="icon is-small is-left">
            <i class="fas fa-calendar-alt"></i>
        </span>
    </div>
    <?php if ($hasError): ?>
    <p class="help is-danger"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
