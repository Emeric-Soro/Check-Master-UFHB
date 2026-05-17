<?php
$compte_rendu = $GLOBALS['compte_rendu'] ?? null;

if (!function_exists('cm_cr_sanitize_html')) {
    function cm_cr_sanitize_html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (!class_exists(\DOMDocument::class)) {
            $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html) ?? '';
            $html = preg_replace('~<(iframe|object|embed)\b[^>]*>.*?</\1>~is', '', $html) ?? '';
            $html = preg_replace('~\son[a-z]+\s*=\s*([\"\']).*?\1~is', '', $html) ?? '';
            $html = preg_replace('~\s(href|src)\s*=\s*([\"\'])\s*javascript:.*?\2~is', '', $html) ?? '';
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrappedHtml = '<div id="cm-cr-root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script|//iframe|//object|//embed') ?: [] as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        foreach ($xpath->query('//*') ?: [] as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttributes()) {
                continue;
            }

            $attrsToRemove = [];
            foreach ($element->attributes as $attribute) {
                $attrName = strtolower((string) $attribute->name);
                $attrValue = trim((string) $attribute->value);

                if (str_starts_with($attrName, 'on')) {
                    $attrsToRemove[] = $attribute->name;
                    continue;
                }

                if (in_array($attrName, ['href', 'src'], true) && preg_match('~^\s*javascript:~i', $attrValue)) {
                    $attrsToRemove[] = $attribute->name;
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $element->removeAttribute($attrName);
            }
        }

        $root = $dom->getElementById('cm-cr-root');
        if (!$root instanceof \DOMElement) {
            return $html;
        }

        $output = '';
        foreach ($root->childNodes as $childNode) {
            $output .= $dom->saveHTML($childNode);
        }

        return $output;
    }
}

$statut = (string) ($compte_rendu['statut'] ?? 'en_attente');
$statutLower = strtolower($statut);
$badgeType = 'light';
$badgeText = ucfirst($statut);
if ($statutLower === 'accepté' || $statutLower === 'accepte' || $statutLower === 'valide' || $statutLower === 'validé') {
    $badgeType = 'success';
    $badgeText = 'Accepté';
} elseif ($statutLower === 'refusé' || $statutLower === 'refuse' || $statutLower === 'rejeté' || $statutLower === 'rejete') {
    $badgeType = 'danger';
    $badgeText = 'Refusé';
} elseif ($statutLower === 'en_attente' || $statutLower === 'en attente') {
    $badgeType = 'warning';
    $badgeText = 'En attente';
}

$dateSoutenance = $compte_rendu['date_soutenance'] ?? null;
$noteTechnique = (int) ($compte_rendu['note_technique'] ?? 0);
$notePresentation = (int) ($compte_rendu['note_presentation'] ?? 0);
$commentaires = (string) ($compte_rendu['commentaires'] ?? 'Aucun commentaire disponible.');
$nomCr = (string) ($compte_rendu['nom_CR'] ?? 'Compte Rendu de Soutenance');
$dateCr = (string) ($compte_rendu['date_CR'] ?? '');
$idCr = isset($compte_rendu['id_CR']) ? (int) $compte_rendu['id_CR'] : 0;
$pdfDownloadUrl = $idCr > 0
    ? '?page=docviewer&type=compte_rendu&id=' . urlencode((string) $idCr) . '&action=download'
    : '';
$contenuCr = (string) ($compte_rendu['contenu_CR'] ?? '');
$contenuCrHtml = cm_cr_sanitize_html($contenuCr);
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                
                <p class="cm-etu-panel__subtitle">Document officiel publié par la commission après évaluation de votre dossier.</p>
            </div>
                    <?php if (function_exists('canView') ? canView() : true): ?>
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
                    <?php endif; ?>

        <?php if (!$compte_rendu): ?>
            <div class="cm-etu-empty">
                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                <p>Aucun compte rendu disponible pour cette soutenance.</p>
                <a href="?page=candidature_soutenance" class="cm-btn is-primary is-sm">Retour à ma candidature</a>
            </div>
        <?php else: ?>
            <article class="cm-etu-doc-card">
                <header class="cm-etu-doc-card__header">
                    <div>

                        <?php if ($dateCr !== ''): ?>
                            <p>Publié le <?= date('d/m/Y à H:i', strtotime($dateCr)) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($pdfDownloadUrl !== '' && (function_exists('canView') ? canView() : true)): ?>
                        <a href="<?= htmlspecialchars($pdfDownloadUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-info is-sm">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            <span>Télécharger</span>
                        </a>
                    <?php endif; ?>
                </header>

                <div class="cm-etu-doc-card__body">
                    <!-- Statut de la candidature -->
                    <div class="cm-etu-cr-section">

                        <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                    </div>

                    <!-- Date de soutenance -->
                    <div class="cm-etu-cr-section">

                        <p><?= $dateSoutenance ? date('d/m/Y', strtotime($dateSoutenance)) : 'Non définie' ?></p>
                    </div>

                    <!-- Évaluation technique -->
                    <div class="cm-etu-cr-section">

                        <div class="cm-etu-cr-score">
                            <span>Qualité du travail</span>
                            <strong><?= $noteTechnique ?>/20</strong>
                        </div>
                        <div class="cm-etu-cr-bar">
                            <div class="cm-etu-cr-bar__fill" style="width: <?= ($noteTechnique / 20) * 100 ?>%"></div>
                        </div>
                    </div>

                    <!-- Évaluation de la présentation -->
                    <div class="cm-etu-cr-section">

                        <div class="cm-etu-cr-score">
                            <span>Qualité de la présentation</span>
                            <strong><?= $notePresentation ?>/20</strong>
                        </div>
                        <div class="cm-etu-cr-bar">
                            <div class="cm-etu-cr-bar__fill" style="width: <?= ($notePresentation / 20) * 100 ?>%"></div>
                        </div>
                    </div>

                    <!-- Contenu texte du CR -->
                    <?php if ($contenuCrHtml !== ''): ?>
                        <div class="cm-etu-cr-section">
                            <div class="cm-etu-cr-rendered"><?= $contenuCrHtml ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Commentaires -->
                    <div class="cm-etu-cr-section">

                        <div class="cm-etu-cr-comments">
                            <p><?= nl2br(htmlspecialchars($commentaires, ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                    </div>
                </div>

                <footer class="cm-etu-doc-card__footer">
                    <span>Numéro étudiant : <strong><?= htmlspecialchars((string) ($compte_rendu['num_etu'] ?? $_SESSION['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <a href="?page=candidature_soutenance" class="cm-btn is-light is-sm">Retour à ma candidature</a>
                </footer>
            </article>

            <div class="cm-etu-note-box">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <p>Note importante : ce compte rendu est un document officiel.</p>
            </div>
        <?php endif; ?>
    </section>
</div>
