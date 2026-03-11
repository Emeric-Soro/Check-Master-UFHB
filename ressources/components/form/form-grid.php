<?php
/**
 * Grille de formulaire flexible.
 *
 * Props:
 * - fields: array des champs
 * - cols: nombre de colonnes (1-6)
 * - mode: auto|compact|relaxed
 * - defaults: valeurs par défaut fusionnées sur chaque champ
 */

$sizeMap = [
    'xs' => 'cm-field--xs',
    'sm' => 'cm-field--sm',
    'md' => 'cm-field--md',
    'lg' => 'cm-field--lg',
    'xl' => 'cm-field--xl',
    'date' => 'cm-field--date',
    'year' => 'cm-field--year',
    'full' => '',
];

$supportedInputTypes = ['text', 'date', 'email', 'number', 'password'];

$cols = isset($cols) ? (int) $cols : 3;
if ($cols < 1) {
    $cols = 1;
} elseif ($cols > 6) {
    $cols = 6;
}

$mode = strtolower(trim((string) ($mode ?? 'auto')));
if (!in_array($mode, ['auto', 'compact', 'relaxed'], true)) {
    $mode = 'auto';
}

$defaults = is_array($defaults ?? null) ? $defaults : [];
$fields = is_array($fields ?? null) ? $fields : [];

$gridClass = 'cm-form-grid cm-form-grid--' . $cols;
if ($mode !== 'auto') {
    $gridClass .= ' cm-form-grid--' . $mode;
}
?>

<div class="<?= htmlspecialchars($gridClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($fields as $field): ?>
        <?php
        if (!is_array($field)) {
            continue;
        }

        $field = array_merge($defaults, $field);
        $type = strtolower(trim((string) ($field['type'] ?? 'text')));
        if ($type === '') {
            $type = 'text';
        }

        $size = isset($field['size']) ? strtolower(trim((string) $field['size'])) : '';
        if ($size === '') {
            if ($type === 'date') {
                $size = 'date';
            } elseif ($type === 'year') {
                $size = 'year';
            } else {
                $size = 'md';
            }
        }

        $sizeClass = $sizeMap[$size] ?? '';
        $controlClass = trim($sizeClass . ' ' . (string) ($field['control_class'] ?? ''));
        $field['control_class'] = $controlClass;
        $field['size'] = $size;

        if ($type === 'year') {
            $type = 'number';
            $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
            if (!isset($attrs['step'])) {
                $attrs['step'] = 1;
            }
            $field['attrs'] = $attrs;
        }

        if (isset($field['component']) && is_string($field['component']) && trim($field['component']) !== '') {
            $component = trim($field['component']);
        } elseif ($type === 'select') {
            $component = 'form/select';
        } elseif ($type === 'textarea') {
            $component = 'form/textarea';
        } elseif ($type === 'file') {
            $component = 'form/file-upload';
        } elseif (in_array($type, $supportedInputTypes, true)) {
            $component = 'form/input-' . $type;
        } else {
            $component = 'form/input-text';
        }

        cm_component($component, $field);
        ?>
    <?php endforeach; ?>
</div>
