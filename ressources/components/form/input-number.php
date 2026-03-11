<?php
require_once __DIR__ . '/_helpers.php';

$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$value = cm_form_old_value((string) $name, $value ?? '');
$placeholder = $placeholder ?? '';
$required = !empty($required);
$readonly = !empty($readonly);
$disabled = !empty($disabled);
$min = isset($min) ? (string) $min : null;
$max = isset($max) ? (string) $max : null;
$step = isset($step) ? (string) $step : null;
$size = trim((string) ($size ?? 'sm'));
if ($size === '') {
    $size = 'sm';
}
$dense = isset($dense) ? !empty($dense) : true;
$group_class = cm_form_class_names('cm-field--number', (string) ($group_class ?? ''));
$label_class = (string) ($label_class ?? '');
$control_class = (string) ($control_class ?? '');
$hint = $hint ?? '';
$error = cm_form_field_error((string) $name, $error ?? '');
$attrs = is_array($attrs ?? null) ? $attrs : [];

$groupClass = cm_form_group_class($required, $error, [
    'readonly' => $readonly,
    'disabled' => $disabled,
    'dense' => $dense,
    'size' => $size,
    'group_class' => $group_class,
]);
$labelClass = cm_form_label_class([
    'readonly' => $readonly,
    'dense' => $dense,
    'size' => $size,
    'label_class' => $label_class,
]);
$controlClass = cm_form_control_class('cm-form-control', [
    'readonly' => $readonly,
    'disabled' => $disabled,
    'dense' => $dense,
    'size' => $size,
    'control_class' => $control_class,
]);
?>
<div class="<?= htmlspecialchars($groupClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($label !== ''): ?>
    <label for="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($labelClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?><?= $required ? ' <span class="cm-required-star">*</span>' : '' ?>
    </label>
    <?php endif; ?>

    <input type="number"
           class="<?= htmlspecialchars($controlClass, ENT_QUOTES, 'UTF-8') ?>"
           name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
           id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
           value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"
           placeholder="<?= htmlspecialchars((string) $placeholder, ENT_QUOTES, 'UTF-8') ?>"
           <?= $required ? 'required' : '' ?>
           <?= $readonly ? 'readonly' : '' ?>
           <?= $disabled ? 'disabled' : '' ?>
           <?= $min !== null ? 'min="' . htmlspecialchars($min, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
           <?= $max !== null ? 'max="' . htmlspecialchars($max, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
           <?= $step !== null ? 'step="' . htmlspecialchars($step, ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= cm_form_attr_string($attrs) ?>>

    <?php if ($error !== ''): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    <?php elseif ($hint !== ''): ?>
    <span class="cm-form-hint"><?= htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>
