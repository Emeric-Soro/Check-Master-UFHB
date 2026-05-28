<?php
/**
 * Onglet Compte rendu
 */
if (!$cr) {
    cm_student_record_empty('Aucun compte rendu', 'Aucun compte rendu n’a été trouvé.', 'fa-file-contract');
    return;
}

$content = trim((string) ($cr['contenu_CR'] ?? ''));
$contentHtml = cm_student_record_sanitize_html($content);
$contentLength = $content !== '' ? mb_strlen($content) : 0;
$crId = $cr['id_CR'] ?? '';
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Date', cm_student_record_date($cr['date_CR'] ?? null), 'fa-calendar-day', 'primary'); ?>
        <?php cm_student_record_metric('PDF', !empty($cr['chemin_fichier_pdf']) ? 'Disponible' : 'Indisponible', 'fa-file-pdf', !empty($cr['chemin_fichier_pdf']) ? 'success' : 'warning'); ?>
        <?php cm_student_record_metric('Contenu', $contentLength > 0 ? $contentLength . ' caractères' : 'Aucun texte', 'fa-align-left', $contentLength > 0 ? 'info' : 'warning'); ?>
        <?php cm_student_record_metric('Titre', cm_student_record_value($cr['nom_CR'] ?? $cr['titre'] ?? null), 'fa-heading', 'success'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-file-contract" aria-hidden="true"></i>
                    <span>Fichier</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Titre', 'value' => $cr['nom_CR'] ?? $cr['titre'] ?? null],
                    ['label' => 'Date', 'value' => cm_student_record_date($cr['date_CR'] ?? null)],
                    [
                        'label' => 'Actions',
                        'html' => !empty($cr['chemin_fichier_pdf']) && $crId !== ''
                            ? cm_student_record_capture(static function () use ($crId, $cr): void {
                                cm_student_record_document_actions('compte_rendu', $crId, (string) ($cr['nom_CR'] ?? $cr['titre'] ?? 'Compte rendu'));
                            })
                            : '<span class="cm-text-muted">Aucun fichier</span>',
                    ],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-align-left" aria-hidden="true"></i>
                    <span>Contenu</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php if ($contentHtml !== ''): ?>
                    <div class="cm-student-record__rich-text"><?= $contentHtml ?></div>
                <?php else: ?>
                    <?php cm_student_record_empty('Contenu vide', '', 'fa-file-lines'); ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>
