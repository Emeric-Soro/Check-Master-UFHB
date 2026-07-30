<?php
$templates = isset($pdfTemplates) && is_array($pdfTemplates) ? $pdfTemplates : [];
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Regroupement des modèles par famille pour l'affichage
$iconByType = [
    'rapport'              => 'fa-file-lines',
    'fiche_inscription'    => 'fa-id-card',
    'recu'                 => 'fa-receipt',
    'memoire'              => 'fa-book-open',
    'pv_commission'        => 'fa-file-signature',
    'pv_final'             => 'fa-file-circle-check',
    'planning'             => 'fa-calendar-days',
    'bulletin'             => 'fa-chart-bar',
    'compte_rendu'         => 'fa-clipboard-list',
    'releve_notes'         => 'fa-list-ol',
    'dossier_candidature'  => 'fa-folder-open',
    'archives_soutenances' => 'fa-box-archive',
    'piste_audit'          => 'fa-shield-halved',
];
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <div class="cm-crud-wrapper">

        <?php
        /* ── En-tête descriptif ─────────────────────────────────────────── */
        ob_start();
        ?>
        <div class="cm-pdf-intro-inner">
            <p class="cm-pdf-intro-text">
                Retrouvez ici tous les formats PDF pris en charge par le système.
                Chaque fiche indique à quelle étape le document est produit. Le bouton
                Visualiser ouvre un rendu fictif complet, sans utiliser ni conserver de données réelles.
            </p>
            <span class="cm-badge is-danger cm-pdf-count-badge">
                <?= count($templates) ?> modèle<?= count($templates) !== 1 ? 's' : '' ?> référencé<?= count($templates) !== 1 ? 's' : '' ?>
            </span>
        </div>
        <?php
        cm_component('crud/form-pole', [
            'title'   => 'Modèles de documents PDF',
            'icon'    => 'fa-file-pdf',
            'content' => (string) ob_get_clean(),
            'compact' => false,
        ]);
        ?>

        <?php /* ── Grille des fiches ──────────────────────────────────────── */ ?>
        <div class="cm-pole-inferieur">
            <?php if (empty($templates)): ?>
                <?php cm_component('ui/empty-state', [
                    'title'   => 'Aucun modèle PDF',
                    'message' => 'Aucun modèle de document PDF n\'est disponible pour le moment.',
                    'icon'    => 'fa-file-pdf',
                ]); ?>
            <?php else: ?>
            <div class="cm-pdf-catalogue-grid">
                <?php foreach ($templates as $template):
                    $type      = (string) ($template['type']      ?? '');
                    $title     = (string) ($template['title']     ?? '');
                    $desc      = (string) ($template['description'] ?? '');
                    $stage     = (string) ($template['stage']     ?? '');
                    $generator = (string) ($template['generator'] ?? '');
                    $source    = (string) ($template['source']    ?? '');
                    $previewUrl  = (string) ($template['preview_url']  ?? '');
                    $downloadUrl = (string) ($template['download_url'] ?? '');
                    $hasExample  = $previewUrl !== '';
                    $icon = $iconByType[$type] ?? 'fa-file-pdf';
                ?>
                <article class="cm-pdf-fiche">
                    <header class="cm-pdf-fiche__head">
                        <span class="cm-pdf-fiche__icon" aria-hidden="true">
                            <i class="fas <?= $h($icon) ?>"></i>
                        </span>
                        <div class="cm-pdf-fiche__heading">
                            <strong class="cm-pdf-fiche__title"><?= $h($title) ?></strong>
                            <span class="cm-pdf-fiche__desc"><?= $h($desc) ?></span>
                        </div>
                    </header>

                    <dl class="cm-pdf-fiche__meta">
                        <div class="cm-pdf-fiche__meta-row">
                            <dt>Étape</dt>
                            <dd><?= $h($stage) ?></dd>
                        </div>
                        <div class="cm-pdf-fiche__meta-row">
                            <dt>Générateur</dt>
                            <dd><?= $h($generator) ?></dd>
                        </div>
                        <div class="cm-pdf-fiche__meta-row">
                            <dt>Source</dt>
                            <dd class="cm-pdf-fiche__source"><?= $h($source) ?></dd>
                        </div>
                    </dl>

                    <footer class="cm-pdf-fiche__actions">
                        <button type="button"
                                class="cm-btn is-primary is-sm js-cm-pdf-preview"
                                data-url="<?= $h($previewUrl) ?>"
                                data-title="<?= $h($title) ?>">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            Visualiser le modèle
                        </button>
                        <?php if ($downloadUrl !== ''): ?>
                            <a class="cm-btn is-light is-sm" href="<?= $h($downloadUrl) ?>">
                                <i class="fas fa-download" aria-hidden="true"></i>
                                Télécharger un exemplaire
                            </a>
                        <?php else: ?>
                            <span class="cm-badge is-warning" style="font-size:.75rem;padding:5px 10px;" title="Le téléchargement concerne uniquement les exemplaires réels">
                                Aperçu fictif uniquement
                            </span>
                        <?php endif; ?>
                    </footer>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php /* ── Styles locaux alignés sur le design system CM ─────────────────── */ ?>
<style>
/* cm-pdf-intro-inner -------------------------------------------------------- */
.cm-pdf-intro-inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.cm-pdf-intro-text {
    margin: 0;
    flex: 1 1 0;
    min-width: 0;
}
.cm-pdf-count-badge {
    flex-shrink: 0;
    white-space: nowrap;
    font-size: .8rem;
    padding: 4px 10px;
    border-radius: 999px;
}

/* cm-pdf-catalogue-grid ----------------------------------------------------- */
.cm-pdf-catalogue-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 16px 0 8px;
}
@media (max-width: 900px) {
    .cm-pdf-catalogue-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 580px) {
    .cm-pdf-catalogue-grid { grid-template-columns: 1fr; }
}

/* cm-pdf-fiche (utilise les tokens du design system CM) ------------------- */
.cm-pdf-fiche {
    display: flex;
    flex-direction: column;
    gap: 0;
    background: var(--cm-surface, #fff);
    border: 1px solid var(--cm-border, #e2e8f0);
    border-radius: var(--cm-radius-lg, 10px);
    overflow: hidden;
    box-shadow: var(--cm-shadow-sm, 0 1px 4px rgba(15,23,42,.06));
    transition: box-shadow .18s ease;
}
.cm-pdf-fiche:hover {
    box-shadow: var(--cm-shadow-md, 0 4px 16px rgba(15,23,42,.1));
}
.cm-pdf-fiche__head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--cm-border, #e2e8f0);
}
.cm-pdf-fiche__icon {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: var(--cm-radius, 8px);
    background: var(--cm-danger-light, #fee2e2);
    color: var(--cm-danger, #b91c1c);
    font-size: 17px;
}
.cm-pdf-fiche__heading {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}
.cm-pdf-fiche__title {
    font-size: .95rem;
    font-weight: 600;
    color: var(--cm-text, #0f172a);
    line-height: 1.3;
}
.cm-pdf-fiche__desc {
    font-size: .8rem;
    color: var(--cm-text-muted, #64748b);
    line-height: 1.4;
}

/* méta --------------------------------------------------------------------- */
.cm-pdf-fiche__meta {
    flex: 1;
    display: grid;
    gap: 0;
    margin: 0;
    padding: 10px 16px;
    font-size: .8rem;
}
.cm-pdf-fiche__meta-row {
    display: flex;
    gap: 6px;
    padding: 5px 0;
    border-bottom: 1px solid var(--cm-border-light, #f1f5f9);
    align-items: baseline;
}
.cm-pdf-fiche__meta-row:last-child { border-bottom: none; }
.cm-pdf-fiche__meta dt {
    flex-shrink: 0;
    font-weight: 600;
    color: var(--cm-text-secondary, #334155);
    min-width: 72px;
}
.cm-pdf-fiche__meta dt::after { content: ' :'; }
.cm-pdf-fiche__meta dd {
    margin: 0;
    color: var(--cm-text-muted, #64748b);
    overflow-wrap: anywhere;
}
.cm-pdf-fiche__source {
    font-size: .75rem;
    font-family: var(--cm-font-mono, ui-monospace, monospace);
    color: var(--cm-text-muted, #64748b) !important;
}

/* actions ------------------------------------------------------------------ */
.cm-pdf-fiche__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px 16px 14px;
    border-top: 1px solid var(--cm-border, #e2e8f0);
    background: var(--cm-surface-alt, #f8fafc);
}
</style>

<?php /* ── Modal aperçu PDF ──────────────────────────────────────────────── */ ?>
<div class="cm-pdf-modal" id="cmPdfPreviewModal" role="dialog" aria-modal="true" aria-labelledby="cmPdfPreviewTitle" hidden>
    <div class="cm-pdf-modal__dialog">
        <div class="cm-pdf-modal__header">
            <h2 id="cmPdfPreviewTitle" class="cm-pdf-modal__label">Aperçu PDF</h2>
            <button type="button" class="cm-pdf-modal__close" id="cmPdfPreviewClose" aria-label="Fermer">&times;</button>
        </div>
        <iframe id="cmPdfPreviewFrame" title="Aperçu du document PDF"></iframe>
    </div>
</div>
<style>
.cm-pdf-modal {
    position: fixed; inset: 0; z-index: 1100;
    display: none; align-items: center; justify-content: center;
    padding: 20px;
    background: rgba(15,23,42,.72);
}
.cm-pdf-modal.is-open { display: flex; }
.cm-pdf-modal__dialog {
    display: flex; flex-direction: column;
    width: min(1100px, 100%); height: min(90vh, 860px);
    overflow: hidden;
    border-radius: var(--cm-radius-lg, 10px);
    background: var(--cm-surface, #fff);
    box-shadow: 0 24px 64px rgba(0,0,0,.35);
}
.cm-pdf-modal__header {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--cm-border, #e2e8f0);
}
.cm-pdf-modal__label { margin: 0; font-size: 1rem; font-weight: 600; }
.cm-pdf-modal__close {
    border: 0; background: transparent; font-size: 22px;
    cursor: pointer; color: var(--cm-text-muted, #64748b); line-height: 1;
    padding: 2px 6px; border-radius: 4px;
    transition: background .15s;
}
.cm-pdf-modal__close:hover { background: var(--cm-border, #e2e8f0); }
.cm-pdf-modal iframe { flex: 1; width: 100%; border: 0; background: #f8fafc; }
@media (max-width: 600px) { .cm-pdf-modal { padding: 8px; } }
</style>
<script>
(() => {
    const modal = document.getElementById('cmPdfPreviewModal');
    const frame = document.getElementById('cmPdfPreviewFrame');
    const title = document.getElementById('cmPdfPreviewTitle');
    const close = document.getElementById('cmPdfPreviewClose');
    if (!modal || !frame || !close) return;

    const hide = () => {
        modal.classList.remove('is-open');
        modal.hidden = true;
        frame.src = 'about:blank';
        document.body.classList.remove('modal-open');
    };

    document.querySelectorAll('.js-cm-pdf-preview').forEach(btn => {
        btn.addEventListener('click', () => {
            frame.src = btn.dataset.url || 'about:blank';
            title.textContent = 'Aperçu — ' + (btn.dataset.title || 'Document PDF');
            modal.hidden = false;
            modal.classList.add('is-open');
            document.body.classList.add('modal-open');
            close.focus();
        });
    });

    close.addEventListener('click', hide);
    modal.addEventListener('click', e => { if (e.target === modal) hide(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) hide(); });
})();
</script>
