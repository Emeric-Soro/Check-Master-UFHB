<?php
/**
 * Onglet Inscriptions
 */
if (empty($inscriptions)) {
    cm_student_record_empty('Aucune inscription', 'Aucune inscription enregistrée pour cet étudiant.', 'fa-file-signature');
    return;
}

$inscriptionsRows = [];
$totalFrais = 0.0;
$totalVerse = 0.0;
$totalSolde = 0.0;

foreach ($inscriptions as $ins) {
    $anneeLabel = '';
    if (!empty($ins->date_deb) && !empty($ins->date_fin)) {
        $anneeLabel = date('Y', strtotime((string) $ins->date_deb)) . '-' . date('Y', strtotime((string) $ins->date_fin));
    }

    $fraisInscription = (float) ($ins->frais_inscription ?? 0);
    $montantVerser = (float) ($ins->montant_verser ?? 0);
    $solde = (float) ($ins->solde ?? 0);
    $statutInscription = $solde <= 0 ? 'Soldé' : ($montantVerser > 0 ? 'Partiel' : 'Impayé');

    $totalFrais += $fraisInscription;
    $totalVerse += $montantVerser;
    $totalSolde += $solde;

    $inscriptionsRows[] = [
        'annee' => $anneeLabel ?: cm_student_record_value($ins->id_annee_acad ?? null),
        'date' => cm_student_record_date($ins->date_inscription ?? null),
        'niveau' => cm_student_record_value($ins->lib_niv_etude ?? null),
        'frais' => ['label' => cm_student_record_money($fraisInscription), 'type' => 'info'],
        'montant_verser' => ['label' => cm_student_record_money($montantVerser), 'type' => $montantVerser > 0 ? 'success' : 'info'],
        'solde' => ['label' => cm_student_record_money($solde), 'type' => $solde <= 0 ? 'success' : 'warning'],
        'statut' => ['label' => $statutInscription, 'type' => cm_student_record_status_type($statutInscription)],
        'methode' => cm_student_record_value($ins->methode_paiement ?? null),
        'versement' => (string) (int) ($ins->num_versement ?? 1),
    ];
}

$encaissement = cm_student_record_percent($totalVerse, max($totalFrais, 1));
$latestInscription = $inscriptions[0] ?? null;

$inscriptionsCols = [
    ['key' => 'annee', 'label' => 'Année', 'align' => 'left'],
    ['key' => 'date', 'label' => 'Date', 'align' => 'left'],
    ['key' => 'niveau', 'label' => 'Niveau', 'align' => 'left'],
    ['key' => 'frais', 'label' => 'Frais', 'align' => 'right', 'type' => 'badge'],
    ['key' => 'montant_verser', 'label' => 'Versé', 'align' => 'right', 'type' => 'badge'],
    ['key' => 'solde', 'label' => 'Solde', 'align' => 'right', 'type' => 'badge'],
    ['key' => 'statut', 'label' => 'Statut', 'align' => 'center', 'type' => 'badge'],
    ['key' => 'methode', 'label' => 'Paiement', 'align' => 'left'],
    ['key' => 'versement', 'label' => 'Versement', 'align' => 'right'],
];
?>

<section class="cm-student-record__section">
    <div class="cm-student-record__metrics">
        <?php cm_student_record_metric('Inscriptions', (string) count($inscriptions), 'fa-file-signature', 'primary'); ?>
        <?php cm_student_record_metric('Total attendu', cm_student_record_money($totalFrais), 'fa-calculator', 'info'); ?>
        <?php cm_student_record_metric('Total versé', cm_student_record_money($totalVerse), 'fa-wallet', 'success'); ?>
        <?php cm_student_record_metric('Solde', cm_student_record_money($totalSolde), 'fa-scale-balanced', $totalSolde <= 0 ? 'success' : 'warning'); ?>
    </div>

    <div class="cm-student-record__grid cm-student-record__grid--two">
        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-wave-square" aria-hidden="true"></i>
                    <span>Encaissement</span>
                </h3>
                <span class="cm-student-record__panel-note"><?= htmlspecialchars((string) round($encaissement), ENT_QUOTES, 'UTF-8') ?>%</span>
            </div>
            <div class="cm-student-record__panel-body">
                <div class="cm-student-record__timeline-block">
                    <div class="cm-student-record__split">
                        <span>Montant encaissé</span>
                        <strong><?= htmlspecialchars(cm_student_record_money($totalVerse), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="cm-student-record__progress">
                        <span class="cm-student-record__progress-bar <?= $totalSolde <= 0 ? 'is-success' : 'is-primary' ?>" style="width: <?= $encaissement ?>%"></span>
                    </div>
                    <div class="cm-student-record__split">
                        <span>Reste à solder</span>
                        <strong><?= htmlspecialchars(cm_student_record_money($totalSolde), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="cm-card cm-student-record__panel">
            <div class="cm-student-record__panel-head">
                <h3 class="cm-student-record__panel-title">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                    <span>Dernière inscription</span>
                </h3>
            </div>
            <div class="cm-student-record__panel-body">
                <?php cm_student_record_field_list([
                    ['label' => 'Date', 'value' => cm_student_record_date($latestInscription->date_inscription ?? null)],
                    ['label' => 'Niveau', 'value' => $latestInscription->lib_niv_etude ?? null],
                    ['label' => 'Paiement', 'value' => $latestInscription->methode_paiement ?? null],
                    ['label' => 'Solde', 'value' => cm_student_record_money($latestInscription->solde ?? null)],
                ]); ?>
            </div>
        </article>
    </div>

    <article class="cm-card cm-student-record__panel">
        <div class="cm-student-record__panel-head">
            <h3 class="cm-student-record__panel-title">
                <i class="fas fa-table-list" aria-hidden="true"></i>
                <span>Historique des inscriptions</span>
            </h3>
        </div>
        <div class="cm-student-record__panel-body">
            <?php cm_component('crud/data-table', [
                'id' => 'tableInscriptions',
                'columns' => $inscriptionsCols,
                'rows' => $inscriptionsRows,
                'row_key' => 'versement',
                'wrapper_class' => 'cm-table-wrapper cm-student-record__table-wrap',
                'table_class' => 'cm-data-table cm-student-record__table',
                'empty_title' => 'Aucune inscription',
                'empty_message' => 'Cet étudiant n’a aucune inscription enregistrée.',
            ]); ?>
        </div>
    </article>
</section>
