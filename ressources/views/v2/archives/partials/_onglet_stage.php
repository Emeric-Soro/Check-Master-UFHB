<?php
/**
 * Onglet Stage
 */
if (!$stage) {
    cm_student_record_empty('Aucun stage', 'Aucune information de stage disponible.', 'fa-briefcase');
    return;
}

$duree = cm_student_record_days_between($stage['date_debut_stage'] ?? null, $stage['date_fin_stage'] ?? null);
$period = cm_student_record_period($stage['date_debut_stage'] ?? null, $stage['date_fin_stage'] ?? null);
$companyName = $stage['lib_long_entreprise'] ?? $stage['nom_entreprise'] ?? null;
$mentorName = trim((string) (($stage['maitre_nom'] ?? '') . ' ' . ($stage['maitre_prenom'] ?? '')));
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Entreprise', cm_student_record_value($companyName), 'fa-building', 'primary'); ?>
        <?php cm_student_record_metric('Période', $period, 'fa-calendar-days', 'info'); ?>
        <?php cm_student_record_metric('Durée', $duree !== null ? $duree . ' jours' : '—', 'fa-hourglass-half', 'warning'); ?>
        <?php cm_student_record_metric('Suivi', cm_student_record_value($mentorName), 'fa-user-tie', 'success'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-briefcase" aria-hidden="true"></i>
                    <span>Mission</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Sujet', 'value' => $stage['sujet_stage'] ?? null],
                    ['label' => 'Début', 'value' => cm_student_record_date($stage['date_debut_stage'] ?? null)],
                    ['label' => 'Fin', 'value' => cm_student_record_date($stage['date_fin_stage'] ?? null)],
                    ['label' => 'Période', 'value' => $period],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-building" aria-hidden="true"></i>
                    <span>Entreprise</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Nom', 'value' => $companyName],
                    ['label' => 'Sigle', 'value' => $stage['lib_court_en'] ?? null],
                    ['label' => 'Email', 'value' => $stage['entreprise_email'] ?? null],
                    ['label' => 'Téléphone', 'value' => $stage['entreprise_telephone'] ?? null],
                ]); ?>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel cm-student-record__panel--full">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                    <span>Maître de stage</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Nom', 'value' => $mentorName],
                    ['label' => 'Fonction', 'value' => $stage['lib_fonction'] ?? null],
                    ['label' => 'Email', 'value' => $stage['maitre_email'] ?? null],
                    ['label' => 'Téléphone', 'value' => $stage['maitre_tel'] ?? null],
                ]); ?>
            </div>
        </article>
    </div>
</section>
