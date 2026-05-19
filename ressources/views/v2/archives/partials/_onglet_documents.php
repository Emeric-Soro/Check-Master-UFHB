<?php
/**
 * Onglet Documents
 */
if (empty($documents)) {
    cm_student_record_empty('Aucun document', 'Aucun document associé à cet étudiant.', 'fa-folder-open');
    return;
}

$latestDocument = $documents[0] ?? null;
$types = [];
foreach ($documents as $document) {
    $types[(string) ($document['type_doc'] ?? $document['type'] ?? 'document')] = true;
}
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Total', (string) count($documents), 'fa-folder-open', 'success'); ?>
        <?php cm_student_record_metric('Dernier dépôt', cm_student_record_date($latestDocument['date_document'] ?? $latestDocument['date'] ?? null), 'fa-calendar-day', 'primary'); ?>
        <?php cm_student_record_metric('Types', (string) count($types), 'fa-tags', 'info'); ?>
        <?php cm_student_record_metric('Consultables', (string) count($documents), 'fa-eye', 'warning'); ?>
    </div>

    <div class="cm-student-record__cards">
        <?php foreach ($documents as $document): ?>
            <?php
            $type = (string) ($document['type_doc'] ?? $document['type'] ?? 'document');
            $title = (string) ($document['titre'] ?? 'Document');
            $documentId = $document['id_doc'] ?? $document['id'] ?? '';
            ?>
            <article class="cm-student-record__document">
                <div class="cm-student-record__document-main">
                    <span class="cm-student-record__document-icon is-<?= htmlspecialchars(cm_student_record_document_tone($type), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                        <i class="fas <?= htmlspecialchars(cm_student_record_document_icon($type), ENT_QUOTES, 'UTF-8') ?>"></i>
                    </span>
                    <div class="cm-student-record__document-copy">
                        <h3 class="cm-student-record__document-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="cm-student-record__document-meta">
                            <span><?= cm_student_record_badge_html(cm_student_record_document_label($type), cm_student_record_document_tone($type)) ?></span>
                            <span><i class="far fa-calendar" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($document['date_document'] ?? $document['date'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i class="fas fa-weight-hanging" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_format_size($document['taille_fichier'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
                <?php cm_student_record_document_actions($type, $documentId, $title); ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
