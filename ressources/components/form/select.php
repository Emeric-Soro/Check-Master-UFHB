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

$selected = (string) ($selected ?? cm_form_old_value((string) $name, ''));
$normalized_options = cm_form_normalize_options($options);

$groupClass = 'cm-form-group';
if ($required) {
    $groupClass .= ' is-required';
}
if ($error !== '') {
    $groupClass .= ' is-invalid';
}
?>
<div class="<?= htmlspecialchars($groupClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($label !== ''): ?>
    <label for="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>" class="cm-form-label">
        <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?><?= $required ? ' <span class="cm-required-star">*</span>' : '' ?>
    </label>
    <?php endif; ?>

    <select name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
            id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
            class="cm-form-control cm-form-select"
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
