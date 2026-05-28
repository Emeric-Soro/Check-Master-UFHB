<?php
/**
 * Onglet Soutenance
 */
$soutenances = $soutenance['soutenances'] ?? [];
$jury = $soutenance['jury'] ?? [];
$evaluation = $soutenance['evaluation'] ?? [];

if (empty($soutenances)) {
    cm_student_record_empty('Aucune soutenance', 'Aucune soutenance programmée pour cet étudiant.', 'fa-microphone-lines');
    return;
}

$reference = $soutenances[0];
$totalNotes = 0.0;
$totalCoeff = 0.0;

foreach ($evaluation as $eval) {
    $note = (float) ($eval['note'] ?? 0);
    $coeff = (float) ($eval['coefficient'] ?? 1);
    $totalNotes += $note * $coeff;
    $totalCoeff += $coeff;
}

$moyenneEvaluation = $totalCoeff > 0 ? ($totalNotes / $totalCoeff) : null;
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Soutenances', (string) count($soutenances), 'fa-microphone-lines', 'primary'); ?>
        <?php cm_student_record_metric('Jury', (string) count($jury), 'fa-users', count($jury) > 0 ? 'info' : 'warning'); ?>
        <?php cm_student_record_metric('Moyenne', cm_student_record_decimal($moyenneEvaluation, 2, ' /20'), 'fa-star', cm_student_record_score_tone($moyenneEvaluation)); ?>
        <?php cm_student_record_metric('Session', cm_student_record_value($reference['lib_session'] ?? null), 'fa-calendar-check', 'success'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-microphone-lines" aria-hidden="true"></i>
                    <span>Soutenance de référence</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Thème', 'value' => $reference['theme_soutenance'] ?? null],
                    ['label' => 'Date', 'value' => cm_student_record_date($reference['date_soutenance'] ?? null)],
                    ['label' => 'Heure', 'value' => cm_student_record_time($reference['heure_soutenance'] ?? null)],
                    ['label' => 'Salle', 'value' => $reference['lib_salle'] ?? null],
                    ['label' => 'Domaine', 'value' => $reference['lib_domaine'] ?? null],
                    ['label' => 'Session', 'value' => $reference['lib_session'] ?? null],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <span>Composition du jury</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php if (empty($jury)): ?>
                    <?php cm_student_record_empty('Jury indisponible', 'Aucun membre de jury associé.', 'fa-users'); ?>
                <?php else: ?>
                    <div class="cm-student-record__people">
                        <?php foreach ($jury as $membre): ?>
                            <article class="cm-student-record__person">
                                <p class="cm-student-record__person-role"><?= htmlspecialchars((string) ($membre['lib_role'] ?? 'Rôle'), ENT_QUOTES, 'UTF-8') ?></p>
                                <h4 class="cm-student-record__person-name">
                                    <?= htmlspecialchars(trim((string) (($membre['nom_enseignant'] ?? '') . ' ' . ($membre['prenom_enseignant'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>
                                </h4>
                                <?php if (!empty($membre['lib_grade'])): ?>
                                    <p class="cm-student-record__person-meta"><?= htmlspecialchars((string) $membre['lib_grade'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <article class="cm-card cm-student-record__panel">
        <div class="cm-student-record__panel-head">
            <h3 class="cm-student-record__panel-title">
                <i class="fas fa-list-check" aria-hidden="true"></i>
                <span>Évaluation</span>
            </h3>
            <?php if ($moyenneEvaluation !== null): ?>
                <span class="cm-student-record__panel-note"><?= htmlspecialchars(cm_student_record_decimal($moyenneEvaluation, 2, ' /20'), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
        <div class="cm-student-record__panel-body">
            <?php if (empty($evaluation)): ?>
                <?php cm_student_record_empty('Aucune évaluation', 'Aucun détail d’évaluation disponible.', 'fa-clipboard-list'); ?>
            <?php else: ?>
                <div class="cm-table-wrapper cm-student-record__table-wrap">
                    <table class="cm-table cm-student-record__table">
                        <thead>
                            <tr>
                                <th>Critère</th>
                                <th class="cm-text-right">Note</th>
                                <th class="cm-text-right">Coef.</th>
                                <th class="cm-text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluation as $eval): ?>
                                <?php
                                $note = (float) ($eval['note'] ?? 0);
                                $coeff = (float) ($eval['coefficient'] ?? 1);
                                $points = ($note * $coeff) / 20;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($eval['lib_critere'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-text-right"><?= htmlspecialchars(cm_student_record_decimal($note, 2), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-text-right"><?= htmlspecialchars(cm_student_record_decimal($coeff, 2), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-text-right"><?= htmlspecialchars(cm_student_record_decimal($points, 2), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th colspan="3" class="cm-text-right"><?= htmlspecialchars(cm_student_record_decimal($moyenneEvaluation, 2, ' /20'), ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </article>

    <?php if (count($soutenances) > 1): ?>
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                    <span>Historique</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <div class="cm-student-record__timeline">
                    <?php foreach (array_slice($soutenances, 1) as $history): ?>
                        <article class="cm-student-record__timeline-item">
                            <div class="cm-student-record__timeline-icon is-info" aria-hidden="true">
                                <i class="fas fa-microphone-lines"></i>
                            </div>
                            <div class="cm-student-record__timeline-main">
                                <h3 class="cm-student-record__timeline-title"><?= htmlspecialchars((string) ($history['theme_soutenance'] ?? 'Soutenance'), ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="cm-student-record__timeline-meta">
                                    <span><i class="far fa-calendar" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($history['date_soutenance'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><i class="fas fa-clock" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_time($history['heure_soutenance'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><i class="fas fa-door-open" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_value($history['lib_salle'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    <?php endif; ?>
</section>
