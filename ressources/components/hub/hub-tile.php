<?php
$title = (string) ($title ?? 'Tuile');
$desc = (string) ($desc ?? '');
$href = (string) ($href ?? '#');
$icon = (string) ($icon ?? 'fa-cube');
$color = strtolower((string) ($color ?? 'primary'));
$count = isset($count) ? (string) $count : '';
$disabled = !empty($disabled);
?>
<a class="cm-hub-tile is-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?> <?= $disabled ? 'is-disabled' : '' ?>"
   href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
    <span class="cm-hub-tile__icon">
        <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
    </span>
    <span class="cm-hub-tile__text">
        <strong class="cm-hub-tile__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if ($desc !== ''): ?><span class="cm-hub-tile__desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </span>
    <?php if ($count !== ''): ?><span class="cm-hub-tile__count"><?= htmlspecialchars($count, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    <i class="fas fa-chevron-right cm-hub-tile__arrow" aria-hidden="true"></i>
</a>
