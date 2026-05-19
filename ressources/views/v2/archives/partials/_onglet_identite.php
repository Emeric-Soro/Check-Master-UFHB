<?php
/**
 * Onglet Identité
 */
$etu = $identite['etudiant'] ?? null;
$dossier = $identite['dossier'] ?? [];
$niveau = $identite['niveau'] ?? null;

if (!$etu) {
    cm_student_record_empty('Identité indisponible', 'Aucune information trouvée pour cet étudiant.', 'fa-id-card');
    return;
}

$moyenneM1 = $dossier['moyenne_M1'] ?? null;
$moyenneM2 = $dossier['moyenne_M2'] ?? null;
$creditsTotal = (int) ($dossier['credits_total'] ?? 0);
$creditsValides = (int) ($dossier['credits_valides'] ?? 0);
$creditRate = cm_student_record_percent($creditsValides, max($creditsTotal, 1));
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Niveau', cm_student_record_value($niveau->lib_niv_etude ?? null), 'fa-layer-group', 'primary'); ?>
        <?php cm_student_record_metric('Moyenne M1', cm_student_record_decimal($moyenneM1, 2, ' /20'), 'fa-chart-line', cm_student_record_score_tone($moyenneM1)); ?>
        <?php cm_student_record_metric('Moyenne M2', cm_student_record_decimal($moyenneM2, 2, ' /20'), 'fa-chart-line', cm_student_record_score_tone($moyenneM2)); ?>
        <?php cm_student_record_metric('Crédits validés', $creditsValides . ' / ' . $creditsTotal, 'fa-award', $creditsValides >= $creditsTotal && $creditsTotal > 0 ? 'success' : 'warning'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <span>Coordonnées</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Matricule', 'value' => $etu->num_carte_etud ?? null],
                    ['label' => 'Identifiant MESRS', 'value' => $etu->num_ident_etud ?? null],
                    ['label' => 'Nom', 'value' => $etu->nom_etu ?? null],
                    ['label' => 'Prénom', 'value' => $etu->prenom_etu ?? null],
                    ['label' => 'Email', 'value' => $etu->email_etu ?? null],
                    ['label' => 'Téléphone', 'value' => $etu->tel_etu ?? null],
                    ['label' => 'Date de naissance', 'value' => cm_student_record_date($etu->date_naiss_etu ?? null)],
                    ['label' => 'Genre', 'value' => $etu->libelle_genre ?? null],
                    ['label' => 'Adresse', 'value' => $etu->adresse_etu ?? null],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    <span>Dossier académique</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Promotion', 'value' => FormattingUtils::formatPromotion($etu->promotion_etu ?? '')],
                    ['label' => 'Niveau', 'value' => $niveau->lib_niv_etude ?? null],
                    ['label' => 'Moyenne M1', 'value' => cm_student_record_decimal($moyenneM1, 2, ' /20')],
                    ['label' => 'Moyenne M2', 'value' => cm_student_record_decimal($moyenneM2, 2, ' /20')],
                    ['label' => 'Crédits totaux', 'value' => (string) $creditsTotal],
                    ['label' => 'Crédits validés', 'value' => (string) $creditsValides],
                ]); ?>

                <div class="cm-student-record__timeline-block cm-mt-md">
                    <div class="cm-student-record__split">
                        <span>Validation des crédits</span>
                        <strong><?= htmlspecialchars((string) round($creditRate), ENT_QUOTES, 'UTF-8') ?>%</strong>
                    </div>
                    <div class="cm-student-record__progress">
                        <span class="cm-student-record__progress-bar <?= $creditRate >= 100 ? 'is-success' : 'is-primary' ?>" style="width: <?= $creditRate ?>%"></span>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>
