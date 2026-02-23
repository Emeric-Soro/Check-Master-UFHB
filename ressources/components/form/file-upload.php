<?php
require_once __DIR__ . '/_helpers.php';

$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$required = !empty($required);
$disabled = !empty($disabled);
$accept = $accept ?? '';
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

$metaId = (string) $id . '_meta';
$previewId = (string) $id . '_preview';
?>
<div class="<?= htmlspecialchars($groupClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($label !== ''): ?>
    <label for="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>" class="cm-form-label">
        <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?><?= $required ? ' <span class="cm-required-star">*</span>' : '' ?>
    </label>
    <?php endif; ?>

    <div class="cm-file-upload">
        <input type="file"
               class="cm-form-control"
               name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
               id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
               <?= $required ? 'required' : '' ?>
               <?= $disabled ? 'disabled' : '' ?>
               <?= $accept !== '' ? 'accept="' . htmlspecialchars((string) $accept, ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= cm_form_attr_string($attrs) ?>>
        <div class="cm-file-upload__meta" id="<?= htmlspecialchars($metaId, ENT_QUOTES, 'UTF-8') ?>">Aucun fichier selectionne.</div>
        <div class="cm-file-upload__preview" id="<?= htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>

    <?php if ($error !== ''): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    <?php elseif ($hint !== ''): ?>
    <span class="cm-form-hint"><?= htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>

<script>
(function () {
    const input = document.getElementById(<?= json_encode((string) $id) ?>);
    const meta = document.getElementById(<?= json_encode($metaId) ?>);
    const preview = document.getElementById(<?= json_encode($previewId) ?>);
    if (!input || !meta || !preview) {
        return;
    }
    input.addEventListener('change', function () {
        preview.innerHTML = '';
        if (!input.files || input.files.length === 0) {
            meta.textContent = 'Aucun fichier selectionne.';
            return;
        }
        const file = input.files[0];
        meta.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';

        if (!file.type.startsWith('image/')) {
            return;
        }

        const img = document.createElement('img');
        img.alt = 'Apercu fichier';
        img.src = URL.createObjectURL(file);
        preview.appendChild(img);
    });
})();
</script>
