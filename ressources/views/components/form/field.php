<?php
$sizeClass = '';
switch ($size ?? 'standard') {
    case 'compresse': $sizeClass = 'cm-field-compresse'; break;
    case 'etendue': $sizeClass = 'cm-field-etendue'; break;
    case 'full': $sizeClass = 'is-fullwidth'; break;
    default: $sizeClass = 'cm-field-standard';
}
$hasError = !empty($error);
?>
<div class="field cm-field <?= $sizeClass ?> <?= $hasError ? 'has-error' : '' ?>">
    <?php if (!empty($label)): ?>
    <label class="label" for="field-<?= htmlspecialchars($name) ?>">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control <?= !empty($icon) ? 'has-icons-left' : '' ?> <?= $hasError ? 'has-icons-right' : '' ?>">
        <input 
            class="input <?= $hasError ? 'is-danger' : '' ?>" 
            type="<?= $type ?? 'text' ?>" 
            name="<?= htmlspecialchars($name) ?>"
            id="field-<?= htmlspecialchars($name) ?>"
            value="<?= htmlspecialchars($value ?? '') ?>"
            placeholder="<?= htmlspecialchars($placeholder ?? '') ?>"
            <?= ($required ?? false) ? 'required' : '' ?>
            <?= ($disabled ?? false) ? 'disabled' : '' ?>
            <?= ($readonly ?? false) ? 'readonly' : '' ?>
            <?php foreach ($attrs ?? [] as $attr => $val): ?>
                <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
            <?php endforeach; ?>
        >
        <?php if (!empty($icon)): ?>
        <span class="icon is-small is-left">
            <i class="fas <?= $icon ?>"></i>
        </span>
        <?php endif; ?>
        <?php if ($hasError): ?>
        <span class="icon is-small is-right">
            <i class="fas fa-exclamation-triangle"></i>
        </span>
        <?php endif; ?>
    </div>
    <?php if ($hasError): ?>
    <p class="help is-danger"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
