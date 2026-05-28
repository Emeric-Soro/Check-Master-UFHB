<?php
/**
 * Onglet Rapport
 */
$rapportData = $rapport['rapport'] ?? null;
$encadrement = $rapport['encadrement'] ?? [];
$validation = $rapport['validation'] ?? null;

if (!$rapportData) {
    cm_student_record_empty('Aucun rapport', 'Aucun rapport enregistré pour cet étudiant.', 'fa-file-lines');
    return;
}

$statutRapport = (string) ($rapportData['statut_rapport'] ?? 'En attente');
$decisionValidation = (string) ($validation['decision_validation'] ?? 'En attente');
$rapportId = $rapportData['id_rapport'] ?? '';
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Statut', $statutRapport, 'fa-file-shield', cm_student_record_status_type($statutRapport)); ?>
        <?php cm_student_record_metric('Version', (string) (int) ($rapportData['version'] ?? 1), 'fa-code-branch', 'info'); ?>
        <?php cm_student_record_metric('Encadrement', (string) count($encadrement), 'fa-users', count($encadrement) > 0 ? 'primary' : 'warning'); ?>
        <?php cm_student_record_metric('Validation', $validation ? $decisionValidation : 'En attente', 'fa-stamp', $validation ? cm_student_record_status_type($decisionValidation) : 'warning'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-file-lines" aria-hidden="true"></i>
                    <span>Fichier et dépôt</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Thème', 'value' => $rapportData['theme_rapport'] ?? null],
                    ['label' => 'Version', 'value' => (string) (int) ($rapportData['version'] ?? 1)],
                    ['label' => 'Date dépôt', 'value' => cm_student_record_date($rapportData['date_redaction_rapport'] ?? $rapportData['date_rapport'] ?? null)],
                    ['label' => 'Statut', 'html' => cm_student_record_badge_html($statutRapport)],
                    [
                        'label' => 'Fichier',
                        'html' => !empty($rapportData['chemin_fichier']) && $rapportId !== ''
                            ? cm_student_record_capture(static function () use ($rapportId, $rapportData): void {
                                cm_student_record_document_actions('rapport', $rapportId, (string) ($rapportData['theme_rapport'] ?? 'Rapport'));
                            })
                            : '<span class="cm-text-muted">Aucun fichier</span>',
                    ],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <span>Validation commission</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php if ($validation): ?>
                    <?php cm_student_record_field_list([
                        ['label' => 'Décision', 'html' => cm_student_record_badge_html($decisionValidation)],
                        ['label' => 'Date', 'value' => cm_student_record_date($validation['date_validation'] ?? null)],
                        ['label' => 'Traité par', 'value' => $validation['valide_par'] ?? null],
                    ]); ?>
                <?php else: ?>
                    <?php cm_student_record_empty('Validation en attente', '', 'fa-hourglass-half'); ?>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <article class="cm-card cm-student-record__panel">
        <div class="cm-student-record__panel-head">
            <h3 class="cm-student-record__panel-title">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span>Encadrement</span>
            </h3>
        </div>
        <div class="cm-student-record__panel-body">
            <?php if (empty($encadrement)): ?>
                <?php cm_student_record_empty('Aucun encadrement', 'Aucun encadrant n’est rattaché à ce rapport.', 'fa-users'); ?>
            <?php else: ?>
                <div class="cm-student-record__people">
                    <?php foreach ($encadrement as $enc): ?>
                        <article class="cm-student-record__person">
                            <p class="cm-student-record__person-role"><?= htmlspecialchars((string) ($enc['role'] ?? 'Rôle'), ENT_QUOTES, 'UTF-8') ?></p>
                            <h4 class="cm-student-record__person-name">
                                <?= htmlspecialchars(trim((string) (($enc['nom_enseignant'] ?? '') . ' ' . ($enc['prenom_enseignant'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>
                            </h4>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</section>
