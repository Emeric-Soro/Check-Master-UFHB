<?php $hasError = !empty($error); ?>
<div class="field cm-field <?= $hasError ? 'has-error' : '' ?>">
    <?php if (!empty($label)): ?>
    <label class="label" for="field-<?= htmlspecialchars($name) ?>">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control">
        <div class="select <?= $hasError ? 'is-danger' : '' ?> <?= ($attrs['multiple'] ?? false) ? 'is-multiple' : '' ?>">
            <select 
                name="<?= htmlspecialchars($name) ?>"
                id="field-<?= htmlspecialchars($name) ?>"
                <?= ($required ?? false) ? 'required' : '' ?>
                <?= ($disabled ?? false) ? 'disabled' : '' ?>
                <?php foreach ($attrs ?? [] as $attr => $val): ?>
                    <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
                <?php endforeach; ?>
            >
                <?php if (!empty($placeholder)): ?>
                <option value=""><?= htmlspecialchars($placeholder) ?></option>
                <?php endif; ?>
                <?php foreach ($options ?? [] as $optValue => $optLabel): ?>
                <option value="<?= htmlspecialchars($optValue) ?>" <?= ((string)$optValue === (string)($value ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($optLabel) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php if ($hasError): ?>
    <p class="help is-danger"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
