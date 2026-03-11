<?php
require_once __DIR__ . '/_helpers.php';

$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$required = !empty($required);
$disabled = !empty($disabled);
$readonly = !empty($readonly);
$hint = $hint ?? '';
$error = cm_form_field_error((string) $name, $error ?? '');
$attrs = is_array($attrs ?? null) ? $attrs : [];
$options = is_array($options ?? null) ? $options : [];
$placeholder = $placeholder ?? '-- Selectionner --';
$size = trim((string) ($size ?? 'sm'));
if ($size === '') {
    $size = 'sm';
}
$dense = isset($dense) ? !empty($dense) : true;
$group_class = cm_form_class_names('cm-field--select', (string) ($group_class ?? ''));
$label_class = (string) ($label_class ?? '');
$control_class = (string) ($control_class ?? '');

$selected = (string) ($selected ?? cm_form_old_value((string) $name, ''));
$normalized_options = cm_form_normalize_options($options);

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
$controlClass = cm_form_control_class('cm-form-control cm-form-select', [
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

    <select name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
            id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
            class="<?= htmlspecialchars($controlClass, ENT_QUOTES, 'UTF-8') ?>"
            <?= $required ? 'required' : '' ?>
            <?= $disabled ? 'disabled' : '' ?>
            <?= $readonly ? 'readonly' : '' ?><?= cm_form_attr_string($attrs) ?>>
        <?php if ($placeholder !== ''): ?>
        <option value=""><?= htmlspecialchars((string) $placeholder, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endif; ?>
        <?php foreach ($normalized_options as $option): ?>
            <?php
            $valueOption = (string) ($option['value'] ?? '');
            $labelOption = (string) ($option['label'] ?? $valueOption);
            $optionDisabled = !empty($option['disabled']);
            $isSelected = ($valueOption === $selected);
            ?>
        <option value="<?= htmlspecialchars($valueOption, ENT_QUOTES, 'UTF-8') ?>"
                <?= $isSelected ? 'selected' : '' ?>
                <?= $optionDisabled ? 'disabled' : '' ?>>
            <?= htmlspecialchars($labelOption, ENT_QUOTES, 'UTF-8') ?>
        </option>
        <?php endforeach; ?>
    </select>

    <?php if ($error !== ''): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    <?php elseif ($hint !== ''): ?>
    <span class="cm-form-hint"><?= htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>
