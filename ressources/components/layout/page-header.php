<?php
$breadcrumbs = is_array($breadcrumbs ?? null) ? $breadcrumbs : [];

if (empty($breadcrumbs)) {
    return;
}
?>
<section class="cm-page-header">
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
</section>
