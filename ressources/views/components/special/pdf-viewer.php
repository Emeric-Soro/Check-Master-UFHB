<?php
$viewerId = 'pdf-viewer-' . uniqid();
$viewerHeight = $height ?? 600;
$isFullWidth = ($width ?? 'full') === 'full';
$initialPage = $page ?? 1;
$pdfUrl = $src ?? '';
?>
<div class="cm-pdf-viewer card <?= $isFullWidth ? 'is-fullwidth' : '' ?>">
    <?php if (!empty($title) || ($downloadable ?? false) || ($printable ?? false) || ($fullscreen ?? false)): ?>
    <header class="card-header">
        <p class="card-header-title">
            <span class="icon mr-2"><i class="fas fa-file-pdf has-text-danger"></i></span>
            <?= htmlspecialchars($title ?? 'Document PDF') ?>
        </p>
        <div class="card-header-icon">
            <?php if ($printable ?? false): ?>
            <button class="button is-small is-light" data-action="print" title="Imprimer">
                <span class="icon"><i class="fas fa-print"></i></span>
            </button>
            <?php endif; ?>
            <?php if ($downloadable ?? false): ?>
            <a href="<?= htmlspecialchars($pdfUrl) ?>" class="button is-small is-light" download title="Télécharger">
                <span class="icon"><i class="fas fa-download"></i></span>
            </a>
            <?php endif; ?>
            <?php if ($fullscreen ?? false): ?>
            <button class="button is-small is-light" data-action="fullscreen" title="Plein écran">
                <span class="icon"><i class="fas fa-expand"></i></span>
            </button>
            <?php endif; ?>
        </div>
    </header>
    <?php endif; ?>
    
    <div class="card-content p-0">
        <?php if (!empty($pdfUrl)): ?>
        <div class="cm-pdf-wrapper" style="height: <?= $viewerHeight ?>px;">
            <iframe 
                id="<?= $viewerId ?>" 
                src="<?= htmlspecialchars($pdfUrl) ?>#page=<?= $initialPage ?>" 
                class="cm-pdf-iframe"
                style="width: 100%; height: 100%; border: none;"
                allowfullscreen
            ></iframe>
        </div>
        <?php else: ?>
        <div class="cm-pdf-empty has-text-centered py-6">
            <span class="icon is-large has-text-grey-light">
                <i class="fas fa-file-pdf fa-3x"></i>
            </span>
            <p class="has-text-grey mt-3">Aucun document PDF à afficher</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($pdfUrl)): ?>
<script>
(function() {
    const viewer = document.getElementById('<?= $viewerId ?>');
    const card = viewer?.closest('.cm-pdf-viewer');
    
    // Print action
    card?.querySelector('[data-action="print"]')?.addEventListener('click', () => {
        viewer.contentWindow?.print();
    });
    
    // Fullscreen action
    card?.querySelector('[data-action="fullscreen"]')?.addEventListener('click', () => {
        if (viewer.requestFullscreen) {
            viewer.requestFullscreen();
        }
    });
})();
</script>
<?php endif; ?>
