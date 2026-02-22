<?php
require_once __DIR__ . '/_helpers.php';

$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$value = cm_form_old_value((string) $name, $value ?? '');
$placeholder = $placeholder ?? 'exemple@domaine.ci';
$required = !empty($required);
$readonly = !empty($readonly);
$disabled = !empty($disabled);
$maxlength = isset($maxlength) ? (int) $maxlength : null;
$hint = $hint ?? '';
$error = cm_form_field_error((string) $name, $error ?? '');
$attrs = is_array($attrs ?? null) ? $attrs : [];

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

    <input type="email"
           class="cm-form-control"
           name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
           id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
           value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"
           placeholder="<?= htmlspecialchars((string) $placeholder, ENT_QUOTES, 'UTF-8') ?>"
           <?= $required ? 'required' : '' ?>
           <?= $readonly ? 'readonly' : '' ?>
           <?= $disabled ? 'disabled' : '' ?>
           <?= $maxlength ? 'maxlength="' . (int) $maxlength . '"' : '' ?><?= cm_form_attr_string($attrs) ?>>

    <?php if ($error !== ''): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    <?php elseif ($hint !== ''): ?>
    <span class="cm-form-hint"><?= htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>
