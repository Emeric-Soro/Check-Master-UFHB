<?php
/**
 * Onglet Réclamations
 */
if (empty($reclamations)) {
    cm_student_record_empty('Aucune réclamation', 'Aucune réclamation enregistrée pour cet étudiant.', 'fa-life-ring');
    return;
}

$resolvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;

foreach ($reclamations as $reclamation) {
    $status = (string) ($reclamation->statut_reclamation ?? $reclamation->libelle_statut_reclamation ?? 'En attente');
    $type = cm_student_record_status_type($status);
    if ($type === 'success') {
        $resolvedCount++;
    } elseif ($type === 'danger') {
        $rejectedCount++;
    } else {
        $pendingCount++;
    }
}
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Total', (string) count($reclamations), 'fa-life-ring', 'warning'); ?>
        <?php cm_student_record_metric('Résolues', (string) $resolvedCount, 'fa-circle-check', $resolvedCount > 0 ? 'success' : 'info'); ?>
        <?php cm_student_record_metric('En attente', (string) $pendingCount, 'fa-hourglass-half', $pendingCount > 0 ? 'warning' : 'info'); ?>
        <?php cm_student_record_metric('Rejetées', (string) $rejectedCount, 'fa-circle-xmark', $rejectedCount > 0 ? 'danger' : 'info'); ?>
    </div>

    <div class="cm-student-record__cards">
        <?php foreach ($reclamations as $reclamation): ?>
            <?php
            $status = (string) ($reclamation->statut_reclamation ?? $reclamation->libelle_statut_reclamation ?? 'En attente');
            $statusType = cm_student_record_status_type($status);
            $description = $reclamation->description_reclamation ?? $reclamation->description ?? null;
            ?>
            <article class="cm-card cm-student-record__panel">
                <div class="cm-student-record__panel-head">
                    <h3 class="cm-student-record__panel-title">
                        <i class="fas fa-life-ring" aria-hidden="true"></i>
                        <span><?= htmlspecialchars((string) ($reclamation->objet_reclamation ?? $reclamation->titre_reclamation ?? 'Réclamation'), ENT_QUOTES, 'UTF-8') ?></span>
                    </h3>
                    <?php cm_student_record_badge($status, $statusType); ?>
                </div>
                <div class="cm-student-record__panel-body">
                    <div class="cm-student-record__timeline-meta">
                        <span><i class="far fa-calendar" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($reclamation->date_creation ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($reclamation->date_mise_a_jour)): ?>
                            <span><i class="fas fa-rotate" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($reclamation->date_mise_a_jour), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="cm-student-record__timeline-text"><?= htmlspecialchars(cm_student_record_value($description), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
