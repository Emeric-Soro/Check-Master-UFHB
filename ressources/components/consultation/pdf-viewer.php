<?php
$title = (string) ($title ?? 'Document');
$src = (string) ($src ?? '');
$height = strtolower((string) ($height ?? 'lg'));
$download_url = (string) ($download_url ?? $src);

$height_map = [
    'sm' => 'is-sm',
    'md' => 'is-md',
    'lg' => 'is-lg',
    'xl' => 'is-xl',
];
$height_class = $height_map[$height] ?? $height_map['lg'];
?>
<section class="cm-pdf-viewer">
    <header class="cm-pdf-viewer__header">
        <h3 class="cm-pdf-viewer__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
        <?php if ($download_url !== ''): ?>
        <a class="cm-btn is-info is-sm" href="<?= htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
            <i class="fas fa-download" aria-hidden="true"></i>
            Telecharger
        </a>
        <?php endif; ?>
    </header>

    <?php if ($src !== ''): ?>
    <iframe class="cm-pdf-viewer__frame <?= htmlspecialchars($height_class, ENT_QUOTES, 'UTF-8') ?>"
            src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"
            title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"></iframe>
    <?php else: ?>
    <div class="cm-pdf-viewer__fallback">Aucun document disponible.</div>
    <?php endif; ?>
</section>
