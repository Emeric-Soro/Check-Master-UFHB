<?php
$title = (string) ($title ?? '');
$icon = (string) ($icon ?? 'fa-pen-to-square');
$content = (string) ($content ?? '');
$actions = (string) ($actions ?? '');
$id = (string) ($id ?? '');
$class = (string) ($class ?? '');
$compact = isset($compact) ? !empty($compact) : true;
$sectionClass = 'cm-pole-superieur';
if ($compact && strpos($class, 'is-compact') === false) {
    $sectionClass .= ' is-compact';
}
if ($class !== '') {
    $sectionClass .= ' ' . $class;
}
?>
<section class="<?= htmlspecialchars(trim($sectionClass), ENT_QUOTES, 'UTF-8') ?>"<?= $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <?php if ($title !== ''): ?>
    <header class="cm-pole-superieur__header">
        <h2>
            <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h2>
    </header>
    <?php endif; ?>

    <div class="cm-pole-superieur__content">
        <?= $content ?>
    </div>

    <?php if ($actions !== ''): ?>
    <footer class="cm-pole-superieur__actions">
        <?= $actions ?>
    </footer>
    <?php endif; ?>
</section>
