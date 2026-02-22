<?php
$title = (string) ($title ?? 'Document');
$src = (string) ($src ?? '');
$height = (string) ($height ?? '700px');
$download_url = (string) ($download_url ?? $src);
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
    <iframe class="cm-pdf-viewer__frame" src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" style="height: <?= htmlspecialchars($height, ENT_QUOTES, 'UTF-8') ?>;"></iframe>
    <?php else: ?>
    <div class="cm-pdf-viewer__fallback">Aucun document disponible.</div>
    <?php endif; ?>
</section>
