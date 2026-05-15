<?php
/**
 * Composant PDF Viewer unifie.
 *
 * Variables attendues avant inclusion :
 *   $title        (string)  Titre du document
 *   $src          (string)  URL iframe pour le PDF (preview)
 *   $height       (string)  sm|md|lg|xl
 *   $download_url (string)  URL de telechargement
 *   $modal        (bool)    Si true, affiche un bouton qui ouvre la preview en overlay via CM.openDocViewer()
 *   $doc_type     (string)  Type de document (pour mode modal : rapport, recu, etc.)
 *   $doc_id       (string)  ID du document (pour mode modal)
 */
$title = (string) ($title ?? 'Document');
$src = (string) ($src ?? '');
$height = strtolower((string) ($height ?? 'lg'));
$download_url = (string) ($download_url ?? $src);
$modal = (bool) ($modal ?? false);
$doc_type = (string) ($doc_type ?? '');
$doc_id = (string) ($doc_id ?? '');

$height_map = [
    'sm' => 'is-sm',
    'md' => 'is-md',
    'lg' => 'is-lg',
    'xl' => 'is-xl',
];
$height_class = $height_map[$height] ?? $height_map['lg'];

// Pour le mode modal, extraire type et id depuis l'URL src si non fournis
if ($modal && $doc_type === '' && $src !== '') {
    if (preg_match('/[?&]type=([^&]+)/', $src, $m)) { $doc_type = $m[1]; }
    if (preg_match('/[?&]id=([^&]+)/', $src, $m)) { $doc_id = $m[1]; }
}

$escTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
?>

<?php if ($modal && $doc_type !== '' && $doc_id !== ''): ?>
<!-- Mode modal : bouton qui ouvre l'overlay -->
<button type="button"
        class="cm-btn is-info is-sm"
        onclick="CM.openDocViewer('<?= htmlspecialchars($doc_type, ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($doc_id, ENT_QUOTES, 'UTF-8') ?>', {title: '<?= $escTitle ?>'})">
    <i class="fas fa-eye" aria-hidden="true"></i>
    Voir le document
</button>
<?php if ($download_url !== ''): ?>
<a class="cm-btn is-ghost is-sm" href="<?= htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
    <i class="fas fa-download" aria-hidden="true"></i>
    Telecharger
</a>
<?php endif; ?>

<?php else: ?>
<!-- Mode inline : iframe dans la page -->
<section class="cm-pdf-viewer">
    <header class="cm-pdf-viewer__header">
        <h3 class="cm-pdf-viewer__title"><?= $escTitle ?></h3>
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
            title="<?= $escTitle ?>"></iframe>
    <?php else: ?>
    <div class="cm-pdf-viewer__fallback">Aucun document disponible.</div>
    <?php endif; ?>
</section>
<?php endif; ?>
