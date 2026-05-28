<?php
/**
 * Onglet Notes
 */
$notesUnified = $notes['notes_unifies'] ?? [];
$noteStats = $notes['note_stats'] ?? [];

if (empty($notesUnified)) {
    cm_student_record_empty('Aucune note', 'Aucune note enregistrée pour cet étudiant.', 'fa-chart-column');
    return;
}

$moyenne = $noteStats['moyenne'] ?? null;
$creditsTotal = (int) ($noteStats['total_unites'] ?? 0);
$creditsValides = (int) ($noteStats['unites_validees'] ?? 0);
$resultsAvailable = !empty($noteStats['resultats_disponibles']);
$creditRate = cm_student_record_percent($creditsValides, max($creditsTotal, 1));

$notesRows = [];
foreach ($notesUnified as $noteItem) {
    $noteValue = $noteItem['moyenne'] ?? null;
    $notesRows[] = [
        'semestre' => cm_student_record_value($noteItem['lib_semestre'] ?? null),
        'ue' => cm_student_record_value($noteItem['lib_ue'] ?? null),
        'code' => cm_student_record_value($noteItem['code_ue'] ?? null),
        'credit' => (string) (int) ($noteItem['credit'] ?? 0),
        'moyenne' => [
            'label' => cm_student_record_decimal($noteValue, 2, ' /20'),
            'type' => cm_student_record_score_tone($noteValue),
        ],
        'niveau' => cm_student_record_value($noteItem['lib_niv_etude'] ?? null),
    ];
}

$notesCols = [
    ['key' => 'semestre', 'label' => 'Semestre', 'align' => 'left'],
    ['key' => 'ue', 'label' => 'UE / Matière', 'align' => 'left'],
    ['key' => 'code', 'label' => 'Code', 'align' => 'left'],
    ['key' => 'credit', 'label' => 'Crédit', 'align' => 'center'],
    ['key' => 'moyenne', 'label' => 'Note', 'align' => 'center', 'type' => 'badge'],
    ['key' => 'niveau', 'label' => 'Niveau', 'align' => 'left'],
];
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Moyenne', cm_student_record_decimal($moyenne, 2, ' /20'), 'fa-chart-line', cm_student_record_score_tone($moyenne)); ?>
        <?php cm_student_record_metric('UE notées', (string) count($notesUnified), 'fa-book-open', 'info'); ?>
        <?php cm_student_record_metric('Crédits', $creditsValides . ' / ' . $creditsTotal, 'fa-award', $creditsValides >= $creditsTotal && $creditsTotal > 0 ? 'success' : 'warning'); ?>
        <?php cm_student_record_metric('Résultats', $resultsAvailable ? 'Disponibles' : 'En attente', 'fa-signal', $resultsAvailable ? 'success' : 'warning'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-bullseye" aria-hidden="true"></i>
                    <span>Lecture rapide</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Moyenne générale', 'value' => cm_student_record_decimal($moyenne, 2, ' /20')],
                    ['label' => 'UE notées', 'value' => (string) count($notesUnified)],
                    ['label' => 'Crédits totaux', 'value' => (string) $creditsTotal],
                    ['label' => 'Crédits validés', 'value' => (string) $creditsValides],
                    ['label' => 'Résultats', 'html' => cm_student_record_badge_html($resultsAvailable ? 'Disponibles' : 'En attente', $resultsAvailable ? 'success' : 'warning')],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-chart-pie" aria-hidden="true"></i>
                    <span>Validation</span>
                </h3>
                <span class="cm-student-record__panel-note"><?= htmlspecialchars((string) round($creditRate), ENT_QUOTES, 'UTF-8') ?>%</span>
            </div>
            <div class="cm-student-record__panel-body">
                <div class="cm-student-record__timeline-block">
                    <div class="cm-student-record__split">
                        <span>Crédits validés</span>
                        <strong><?= htmlspecialchars((string) $creditsValides, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="cm-student-record__progress">
                        <span class="cm-student-record__progress-bar <?= $creditRate >= 100 ? 'is-success' : 'is-primary' ?>" style="width: <?= $creditRate ?>%"></span>
                    </div>
                    <div class="cm-student-record__split">
                        <span>Crédits restants</span>
                        <strong><?= htmlspecialchars((string) max(0, $creditsTotal - $creditsValides), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>
        </article>
    </div>

    <article class="cm-card cm-student-record__panel">
        <div class="cm-student-record__panel-head">
            <h3 class="cm-student-record__panel-title">
                <i class="fas fa-table-list" aria-hidden="true"></i>
                <span>Détail des notes</span>
            </h3>
        </div>
        <div class="cm-student-record__panel-body">
            <?php cm_component('crud/data-table', [
                'id' => 'tableNotes',
                'columns' => $notesCols,
                'rows' => $notesRows,
                'row_key' => 'code',
                'wrapper_class' => 'cm-table-wrapper cm-student-record__table-wrap',
                'table_class' => 'cm-data-table cm-student-record__table',
                'empty_title' => 'Aucune note',
                'empty_message' => 'Aucune note enregistrée pour cet étudiant.',
            ]); ?>
        </div>
    </article>
</section>
