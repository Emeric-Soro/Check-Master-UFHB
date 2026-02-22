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

    <div class="cm-input-password-wrapper">
        <input type="password"
               class="cm-form-control"
               name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
               id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
               value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= htmlspecialchars((string) $placeholder, ENT_QUOTES, 'UTF-8') ?>"
               <?= $required ? 'required' : '' ?>
               <?= $readonly ? 'readonly' : '' ?>
               <?= $disabled ? 'disabled' : '' ?>
               <?= $maxlength ? 'maxlength="' . (int) $maxlength . '"' : '' ?><?= cm_form_attr_string($attrs) ?>>
        <button type="button"
                class="cm-input-password-toggle"
                aria-label="Afficher/Masquer le mot de passe"
                data-target="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>">
            <i class="fas fa-eye" aria-hidden="true"></i>
        </button>
    </div>

    <?php if ($error !== ''): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    <?php elseif ($hint !== ''): ?>
    <span class="cm-form-hint"><?= htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>

<script>
(function () {
    const toggle = document.querySelector('.cm-input-password-toggle[data-target="<?= addslashes((string) $id) ?>"]');
    if (!toggle) {
        return;
    }
    toggle.addEventListener('click', function () {
        const targetId = toggle.getAttribute('data-target');
        const input = targetId ? document.getElementById(targetId) : null;
        if (!input) {
            return;
        }
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        const icon = toggle.querySelector('i');
        if (icon) {
            icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        }
    });
})();
</script>
