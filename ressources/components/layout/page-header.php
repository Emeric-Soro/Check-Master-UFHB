<?php
$title = (string) ($title ?? '');
$subtitle = (string) ($subtitle ?? '');
$annee = (string) ($annee ?? '');
if ($annee === '' && session_status() === PHP_SESSION_ACTIVE) {
    $annee = trim((string) ($_SESSION['global_annee_selected'] ?? ''));
}
$breadcrumbs = is_array($breadcrumbs ?? null) ? $breadcrumbs : [];
$icon = (string) ($icon ?? '');
$show_title = !isset($show_title_group) || (bool) $show_title_group;

$has_title = $show_title && ($title !== '' || $subtitle !== '' || $icon !== '');
$has_year = $annee !== '';
$has_breadcrumbs = !empty($breadcrumbs);

if (!$has_title && !$has_year && !$has_breadcrumbs) {
    return;
}
?>
<section class="cm-page-header">
    <?php if ($has_breadcrumbs): ?>
    <nav class="cm-breadcrumb" aria-label="Fil d Ariane">
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
</section>
