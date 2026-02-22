<?php
$id = (string) ($id ?? '');
$active = !empty($active);
$content = (string) ($content ?? '');
if ($id === '') {
    return;
}
?>
<section class="cm-tab-panel <?= $active ? 'is-active' : '' ?>" data-tab-panel="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" <?= $active ? '' : 'hidden' ?>>
    <?= $content ?>
</section>
