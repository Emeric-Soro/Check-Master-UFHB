<?php
/**
 * Onglet Candidature
 */
if (empty($candidatures)) {
    cm_student_record_empty('Aucune candidature', 'Aucune candidature à la soutenance n’a été trouvée.', 'fa-envelope-open-text');
    return;
}

$validatedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;

foreach ($candidatures as $cand) {
    $status = (string) ($cand['statut_candidature'] ?? 'En attente');
    $type = cm_student_record_status_type($status);
    if ($type === 'success') {
        $validatedCount++;
    } elseif ($type === 'danger') {
        $rejectedCount++;
    } else {
        $pendingCount++;
    }
}

$latest = $candidatures[0] ?? [];
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Dossiers', (string) count($candidatures), 'fa-envelope-open-text', 'primary'); ?>
        <?php cm_student_record_metric('Validées', (string) $validatedCount, 'fa-circle-check', $validatedCount > 0 ? 'success' : 'info'); ?>
        <?php cm_student_record_metric('En attente', (string) $pendingCount, 'fa-hourglass-half', $pendingCount > 0 ? 'warning' : 'info'); ?>
        <?php cm_student_record_metric('Dernier statut', cm_student_record_value($latest['statut_candidature'] ?? null), 'fa-flag', cm_student_record_status_type((string) ($latest['statut_candidature'] ?? ''))); ?>
    </div>

    <div class="cm-student-record__timeline">
        <?php foreach ($candidatures as $cand): ?>
            <?php
            $status = (string) ($cand['statut_candidature'] ?? 'En attente');
            $statusType = cm_student_record_status_type($status);
            $comment = trim((string) ($cand['commentaire_admin'] ?? ''));
            ?>
            <article class="cm-student-record__timeline-item">
                <div class="cm-student-record__timeline-icon is-<?= htmlspecialchars($statusType, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="cm-student-record__timeline-main">
                    <h3 class="cm-student-record__timeline-title">
                        <span>Candidature du <?= htmlspecialchars(cm_student_record_date($cand['date_candidature'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php cm_student_record_badge($status, $statusType); ?>
                    </h3>
                    <div class="cm-student-record__timeline-meta">
                        <span><i class="far fa-calendar" aria-hidden="true"></i>Dépôt <?= htmlspecialchars(cm_student_record_date($cand['date_candidature'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><i class="fas fa-user-check" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_value($cand['traite_par'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><i class="fas fa-clock" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($cand['date_traitement'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if ($comment !== ''): ?>
                        <p class="cm-student-record__timeline-text"><?= htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
