<?php
require_once __DIR__ . '/../../app/models/Etudiant.php';

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

function formatCrDate(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '—';
    }
    return date('d/m/Y à H:i', $timestamp);
}

$numEtu = isset($_SESSION['num_etu']) && $_SESSION['num_etu'] !== '' ? (string) $_SESSION['num_etu'] : null;
$compte_rendu = null;
$error = null;

if ($numEtu === null) {
    $error = 'Aucun étudiant connecté.';
} else {
    try {
        $etudiantModel = new Etudiant(Database::getConnection());
        $compte_rendu = $etudiantModel->getCompteRendu($numEtu);
    } catch (\Exception $e) {
        $error = 'Une erreur est survenue lors de la récupération du compte rendu.';
        error_log('Erreur consultation_cr_etud: ' . $e->getMessage());
    }
}
?>

<style>
.cm-etu-document-viewer::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.cm-etu-document-viewer::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.cm-etu-document-viewer::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.cm-etu-document-viewer::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<div class="cm-etu-screen">
    <div class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                <h1 class="cm-etu-panel__title">
                    <i class="fas fa-file-pen" aria-hidden="true"></i>
                    Mon compte rendu
                </h1>
                <p class="cm-etu-panel__subtitle">Document officiel publié par la commission de soutenance.</p>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="cm-etu-empty">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <a href="?page=candidature_soutenance" class="cm-btn is-primary is-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Retour à ma candidature
                </a>
            </div>

        <?php elseif (!$compte_rendu): ?>
            <div class="cm-etu-empty">
                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                <p>Aucun compte rendu n'est disponible pour le moment.</p>
                <p class="cm-etu-help">Le compte rendu sera publié ici après votre soutenance, une fois évalué par la commission.</p>
                <a href="?page=candidature_soutenance" class="cm-btn is-primary is-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Retour à ma candidature
                </a>
            </div>

        <?php else:
            $nomCr = (string) ($compte_rendu['nom_CR'] ?? 'Compte rendu');
            $dateCr = (string) ($compte_rendu['date_CR'] ?? '');
            $contenuCr = (string) ($compte_rendu['contenu_CR'] ?? '');
            $contenuCrHtml = cm_cr_sanitize_html($contenuCr);
            $idCr = isset($compte_rendu['id_CR']) ? (int) $compte_rendu['id_CR'] : 0;
            $pdfDownloadUrl = $idCr > 0
                ? '?page=docviewer&type=compte_rendu&id=' . urlencode((string) $idCr) . '&action=download'
                : '';
            $crNumEtu = (string) ($compte_rendu['num_etu'] ?? $numEtu ?? '');
            $hasPdf = $pdfDownloadUrl !== '';
            ?>

            <div class="cm-etu-validation-box">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <div>
                    <strong>Compte rendu disponible</strong>
                    <span>Publié le <?= formatCrDate($dateCr) ?></span>
                </div>
            </div>

            <article class="cm-etu-doc-card">
                <header class="cm-etu-doc-card__header">
                    <div>
                        <h3><?= htmlspecialchars($nomCr, ENT_QUOTES, 'UTF-8') ?></h3>
                        <p>Publié le <?= formatCrDate($dateCr) ?></p>
                    </div>
                    <div style="display:flex;gap:0.45rem;flex-wrap:wrap;">
                        <?php if ($hasPdf): ?>
                            <a href="<?= htmlspecialchars($pdfDownloadUrl, ENT_QUOTES, 'UTF-8') ?>"
                               class="cm-btn is-info is-sm">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                <span>Télécharger le PDF</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="cm-etu-doc-card__body" style="padding: 1.5rem;">
                    <?php if ($contenuCrHtml !== ''): ?>
                        <div style="background: #f1f5f9; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--cm-border-color, #e2e8f0);">
                            <div class="cm-etu-document-viewer" style="
                                background: #ffffff;
                                padding: 3rem;
                                max-height: 65vh;
                                min-height: 400px;
                                overflow-y: auto;
                                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                                border-radius: 4px;
                            ">
                                <div class="cm-etu-cr-rendered" style="max-width: 850px; margin: 0 auto; color: #334155; line-height: 1.7; font-size: 1.05rem;">
                                    <?= $contenuCrHtml ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cm-etu-empty" style="box-shadow:none;border:0;background:transparent;padding:1rem 0;">
                            <i class="fas fa-file-lines" aria-hidden="true"></i>
                            <p>Le document texte n'est pas encore complet ou disponible.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <footer class="cm-etu-doc-card__footer">
                    <span>
                        <i class="fas fa-id-card" aria-hidden="true"></i>
                        Numéro étudiant : <strong><?= htmlspecialchars($crNumEtu, ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                    <a href="?page=candidature_soutenance" class="cm-btn is-light is-sm">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        Retour à ma candidature
                    </a>
                </footer>
            </article>

            <div class="cm-etu-note-box">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <p>Ce compte rendu est un document officiel faisant foi de l'évaluation de votre soutenance.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
