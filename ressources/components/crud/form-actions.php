<?php
$actions = is_array($actions ?? null) ? $actions : [];
$class = trim((string) ($class ?? ''));
$align = trim((string) ($align ?? ''));
$dense = !empty($dense);
$wrapperClass = 'cm-form-buttons';
if ($dense) {
    $wrapperClass .= ' is-dense';
}
if ($align !== '') {
    $wrapperClass .= ' is-' . $align;
}
if ($class !== '') {
    $wrapperClass .= ' ' . $class;
}
?>
<div class="<?= htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($actions as $action): ?>
        <?php
        $tag = strtolower((string) ($action['tag'] ?? 'button'));
        $actionClass = (string) ($action['class'] ?? 'cm-btn is-info is-sm');
        $label = (string) ($action['label'] ?? 'Action');
        $icon = (string) ($action['icon'] ?? '');
        $type = (string) ($action['type'] ?? 'button');
        $href = (string) ($action['href'] ?? '#');
        $attrs = is_array($action['attrs'] ?? null) ? $action['attrs'] : [];
        $attrsString = function_exists('cm_form_attr_string') ? cm_form_attr_string($attrs) : '';
        ?>
        <?php if ($tag === 'a'): ?>
        <a class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"<?= $attrsString ?>>
            <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php else: ?>
        <button class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"<?= $attrsString ?>>
            <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
