<?php
$title = $title ?? 'Page';
$subtitle = $subtitle ?? '';
$annee = $annee ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$icon = $icon ?? '';
$show_title_group = isset($show_title_group) ? (bool) $show_title_group : true;

$has_heading = $show_title_group && ($title !== '' || $subtitle !== '' || $icon !== '');
$has_year = $annee !== '';
$has_breadcrumbs = !empty($breadcrumbs);

if (!$has_heading && !$has_year && !$has_breadcrumbs) {
    return;
}
?>
<div class="cm-page-header">
    <div class="cm-page-header__main <?= !$has_heading ? 'is-compact' : '' ?>">
        <?php if ($has_year): ?>
        <span class="cm-badge is-info cm-page-header__year">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <?= htmlspecialchars((string) $annee, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <?php endif; ?>
    </div>

    <?php if ($has_breadcrumbs): ?>
    <nav class="cm-breadcrumb" aria-label="Fil d'Ariane">
        <ol class="cm-breadcrumb__list">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <?php
            $label = (string) ($crumb['label'] ?? '');
            $url = (string) ($crumb['url'] ?? '');
            $is_last = ($i === count($breadcrumbs) - 1);
            ?>
            <li class="cm-breadcrumb__item <?= $is_last ? 'is-active' : '' ?>">
                <?php if (!$is_last && $url !== ''): ?>
                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="cm-breadcrumb__link">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <i class="fas fa-chevron-right cm-breadcrumb__sep" aria-hidden="true"></i>
                <?php else: ?>
                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>
</div>
