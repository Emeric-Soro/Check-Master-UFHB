<?php
$actions = is_array($actions ?? null) ? $actions : [];
$cancel_action = is_array($cancel_action ?? null) ? $cancel_action : null;
$class = trim((string) ($class ?? ''));
$align = trim((string) ($align ?? ''));
$dense = !empty($dense);
$wrapperClass = 'cm-form-buttons';
if ($cancel_action !== null) {
    $wrapperClass .= ' has-cancel';
}
if ($dense) {
    $wrapperClass .= ' is-dense';
}
if ($align !== '') {
    $wrapperClass .= ' is-' . $align;
}
if ($class !== '') {
    $wrapperClass .= ' ' . $class;
}

$renderBtn = function (array $action) {
    $tag = strtolower((string) ($action['tag'] ?? 'button'));
    $actionClass = (string) ($action['class'] ?? 'cm-btn is-info is-sm');
    $label = (string) ($action['label'] ?? 'Action');
    $icon = (string) ($action['icon'] ?? '');
    $type = (string) ($action['type'] ?? 'button');
    $href = (string) ($action['href'] ?? '#');
    $attrs = is_array($action['attrs'] ?? null) ? $action['attrs'] : [];
    if (!empty($action['name']) && !isset($attrs['name'])) {
        $attrs['name'] = (string) $action['name'];
    }
    if (array_key_exists('value', $action) && !isset($attrs['value'])) {
        $attrs['value'] = (string) $action['value'];
    }
    $attrsString = function_exists('cm_form_attr_string') ? cm_form_attr_string($attrs) : '';
    if ($tag === 'a'): ?>
        <a class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>"
            href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" <?= $attrsString ?>>
            <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"
                    aria-hidden="true"></i><?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php else: ?>
        <button class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>"
            type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= $attrsString ?>>
            <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"
                    aria-hidden="true"></i><?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </button>
    <?php endif;
};
?>
<div class="<?= htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($cancel_action !== null): ?>
        <div class="cm-form-buttons__cancel">
            <?php $cancel_action['class'] = (string) ($cancel_action['class'] ?? 'cm-btn is-light is-sm'); ?>
            <?php $renderBtn($cancel_action); ?>
        </div>
    <?php endif; ?>
    <div class="cm-form-buttons__right">
        <?php foreach ($actions as $action): ?>
            <?php $renderBtn($action); ?>
        <?php endforeach; ?>
    </div>
</div>