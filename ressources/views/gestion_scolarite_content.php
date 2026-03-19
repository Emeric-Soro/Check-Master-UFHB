<?php
$etudiantsNonInscrits = is_array($GLOBALS['etudiantsNonInscrits'] ?? null) ? $GLOBALS['etudiantsNonInscrits'] : [];
$etudiantsInscrits = is_array($GLOBALS['etudiantsInscrits'] ?? null) ? $GLOBALS['etudiantsInscrits'] : [];
$listeAllEtudiant = is_array($GLOBALS['listeAllEtudiant'] ?? null) ? $GLOBALS['listeAllEtudiant'] : [];
$niveaux = is_array($GLOBALS['niveaux'] ?? null) ? $GLOBALS['niveaux'] : [];
$listeAnnees = is_array($GLOBALS['listeAnnees'] ?? null) ? $GLOBALS['listeAnnees'] : [];
$listeVersement = is_array($GLOBALS['listeVersement'] ?? null) ? $GLOBALS['listeVersement'] : [];
$anneeActiveId = \AcademicYear::getActiveIdFromSession();
$anneeActiveLabel = \AcademicYear::getActiveLabelFromSession();
$anneeSelectionneeId = \AcademicYear::getSelectedIdFromSession();
$anneeSelectionneeLabel = \AcademicYear::getSelectedLabelFromSession();
$anneeSelectionToutes = \AcademicYear::isAllSelectedFromSession();
$anneeEcritureId = \AcademicYear::getWritableIdFromSession();
$anneeEcritureLabel = \AcademicYear::getWritableLabelFromSession();
$ecritureAutorisee = \AcademicYear::isWriteAllowedFromSession();
$today = date('Y-m-d');
if ($anneeActiveId === null || $anneeActiveLabel === '') {
    $anneeActiveLabel = date('Y') . '-' . (date('Y') + 1);
    foreach ($listeAnnees as $annee) {
        $debut = (string) ($annee->date_deb ?? '');
        $fin = (string) ($annee->date_fin ?? '');
        if ($debut !== '' && $fin !== '' && $today >= $debut && $today <= $fin) {
            $anneeActiveId = (int) ($annee->id_annee_acad ?? 0);
            $anneeActiveLabel = date('Y', strtotime($debut)) . '-' . date('Y', strtotime($fin));
            break;
        }
    }
}
if ($anneeSelectionneeLabel === '') {
    $anneeSelectionneeLabel = $anneeSelectionToutes
        ? \AcademicYear::getAllLabel()
        : ($anneeEcritureLabel !== '' ? $anneeEcritureLabel : $anneeActiveLabel);
}

$niveauxOptions = [];
$niveauxMontants = [];
foreach ($niveaux as $niveau) {
    $idNiveau = (string) ($niveau['id_niv_etude'] ?? '');
    $niveauxOptions[$idNiveau] = (string) ($niveau['lib_niv_etude'] ?? 'Niveau');
    $niveauxMontants[$idNiveau] = (float) ($niveau['montant_scolarite'] ?? 0);
}
$anneesOptions = [];
foreach ($listeAnnees as $annee) {
    $id = (int) ($annee->id_annee_acad ?? 0);
    $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
    $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
    $anneesOptions[$id] = trim($debut . '-' . $fin, '-');
}
$catalog = [];
foreach ($listeAllEtudiant as $etu) {
    $numIdent = (string) ($etu['num_ident_etud'] ?? '');
    $numCarte = (string) ($etu['num_carte_etud'] ?? '');
    if ($numIdent === '') {
        continue;
    }
    $catalog[$numIdent] = [
        'num_ident' => $numIdent,
        'num_carte' => $numCarte,
        'nom' => (string) ($etu['nom_etu'] ?? ''),
        'prenom' => (string) ($etu['prenom_etu'] ?? ''),
        'inscrit' => false,
        'id_niv_etude' => !empty($etu['id_niv_etude']) ? (string) $etu['id_niv_etude'] : null,
        'nom_niveau' => '',
        'id_annee' => $anneeEcritureId,
        'montant_total' => 0.0,
        'montant_paye' => 0.0,
        'reste_a_payer' => 0.0,
        'solde' => 0.0,
        'num_versement' => 1,
    ];
}
foreach ($etudiantsNonInscrits as $etu) {
    $numIdent = (string) ($etu['num_ident_etud'] ?? $etu['num_etu'] ?? '');
    $numCarte = (string) ($etu['num_carte_etud'] ?? '');
    if ($numIdent === '') {
        continue;
    }
    if (!isset($catalog[$numIdent])) {
        $catalog[$numIdent] = [
            'num_ident' => $numIdent,
            'num_carte' => $numCarte,
            'nom' => (string) ($etu['nom_etu'] ?? ''),
            'prenom' => (string) ($etu['prenom_etu'] ?? ''),
            'inscrit' => false,
            'id_niv_etude' => null,
            'nom_niveau' => '',
            'id_annee' => $anneeEcritureId,
            'montant_total' => 0.0,
            'montant_paye' => 0.0,
            'reste_a_payer' => 0.0,
            'solde' => 0.0,
            'num_versement' => 1,
        ];
    }
}
foreach ($etudiantsInscrits as $etu) {
    $numIdent = (string) ($etu['num_ident_etud'] ?? '');
    $numCarte = (string) ($etu['num_carte_etud'] ?? '');
    if ($numIdent === '') {
        continue;
    }
    if (!isset($catalog[$numIdent])) {
        $catalog[$numIdent] = [
            'num_ident' => $numIdent,
            'num_carte' => $numCarte,
            'nom' => (string) ($etu['nom'] ?? ''),
            'prenom' => (string) ($etu['prenom'] ?? ''),
            'inscrit' => true,
            'id_niv_etude' => !empty($etu['id_niv_etude']) ? (string) $etu['id_niv_etude'] : null,
            'nom_niveau' => (string) ($etu['nom_niveau'] ?? ''),
            'id_annee' => !empty($etu['id_annee_acad']) ? (int) $etu['id_annee_acad'] : $anneeActiveId,
            'montant_total' => (float) ($etu['montant_scolarite'] ?? 0),
            'montant_paye' => (float) ($etu['montant_paye'] ?? 0),
            'reste_a_payer' => (float) ($etu['reste_a_payer'] ?? 0),
            'solde' => (float) ($etu['solde'] ?? $etu['reste_a_payer'] ?? 0),
            'num_versement' => ((int) ($etu['nombre_versements'] ?? 0)) + 1,
        ];
    } else {
        $catalog[$numIdent]['inscrit'] = true;
        $catalog[$numIdent]['num_carte'] = $numCarte;
        $catalog[$numIdent]['id_niv_etude'] = !empty($etu['id_niv_etude']) ? (string) $etu['id_niv_etude'] : $catalog[$numIdent]['id_niv_etude'];
        $catalog[$numIdent]['nom_niveau'] = (string) ($etu['nom_niveau'] ?? $catalog[$numIdent]['nom_niveau']);
        $catalog[$numIdent]['id_annee'] = !empty($etu['id_annee_acad']) ? (int) $etu['id_annee_acad'] : $catalog[$numIdent]['id_annee'];
        $catalog[$numIdent]['montant_total'] = (float) ($etu['montant_scolarite'] ?? 0);
        $catalog[$numIdent]['montant_paye'] = (float) ($etu['montant_paye'] ?? 0);
        $catalog[$numIdent]['reste_a_payer'] = (float) ($etu['reste_a_payer'] ?? 0);
        $catalog[$numIdent]['solde'] = (float) ($etu['solde'] ?? $etu['reste_a_payer'] ?? 0);
        $catalog[$numIdent]['num_versement'] = ((int) ($etu['nombre_versements'] ?? 0)) + 1;
    }
}
$studentOptions = [];
foreach ($catalog as $numIdent => $item) {
    $status = !empty($item['inscrit']) ? 'Inscrit' : 'Non inscrit';
    $numCarte = $item['num_carte'] ?? $numIdent;
    $studentOptions[$numIdent] = trim($item['nom'] . ' ' . $item['prenom']) . ' (' . $numCarte . ') - ' . $status;
}
ksort($studentOptions);
$allowedLimits = [2, 5, 10, 25, 50, 100];
$versementsParPage = max(2, (int) ($_GET['limit_versements'] ?? 10));
if (!in_array($versementsParPage, $allowedLimits, true)) {
    $versementsParPage = 10;
}
$versementsPage = max(1, (int) ($_GET['page_versements'] ?? 1));
$totalVersements = count($listeVersement);
$versementPagination = function_exists('cm_paginate')
    ? cm_paginate($totalVersements, $versementsParPage, $versementsPage)
    : [
        'total' => $totalVersements,
        'per_page' => $versementsParPage,
        'current' => $versementsPage,
        'last' => max(1, (int) ceil($totalVersements / $versementsParPage)),
        'offset' => max(0, ($versementsPage - 1) * $versementsParPage),
        'has_prev' => $versementsPage > 1,
        'has_next' => $versementsPage < max(1, (int) ceil($totalVersements / $versementsParPage)),
        'pages' => [$versementsPage],
    ];
$versementsToShow = array_slice($listeVersement, (int) ($versementPagination['offset'] ?? 0), $versementsParPage);
$paginationBaseUrl = '?page=gestion_scolarite&limit_versements=' . $versementsParPage;
?>
<style>
/* Ajustements demandés: écran Gestion scolarité uniquement */
#cmPaiementForm #cmNiveau {
    width: 10ch !important; /* "Master 2" */
    min-width: 13ch !important;
    max-width: 2ch !important;
}

#cmPaiementForm #cmNumVersement {
    width: 3.5ch !important; /* 1 caractère utile */
    min-width: 3.5ch !important;
    max-width: 3.5ch !important;
}

#cmPaiementForm #cmNumVersement {
    text-align: center;
    padding-left: 0.2rem !important;
    padding-right: 0.2rem !important;
}

#cmPaiementForm .cm-form-group:has(#cmNiveau),
#cmPaiementForm .cm-form-group:has(#cmNumVersement) {
    justify-self: start;
    width: auto !important;
    min-width: 0 !important;
    max-width: none !important;
}
</style>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => '',
        'subtitle' => 'Gestion unifiee des inscriptions et versements.',
        'annee' => $anneeSelectionneeLabel,
        'icon' => 'fa-credit-card',
    ]);
    ?>
    <?php if (!empty($GLOBALS['messageSuccess'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $GLOBALS['messageSuccess']]); ?>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $GLOBALS['messageErreur']]); ?>
    <?php endif; ?>
    <?php if ($anneeSelectionToutes): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'info',
            'message' => "Affichage multi-années actif. Les nouvelles inscriptions et les nouveaux versements seront enregistrés sur l'année académique active {$anneeEcritureLabel}.",
        ]); ?>
    <?php elseif (!$ecritureAutorisee): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'warning',
            'message' => "Consultation historique active. Les inscriptions et versements sont réservés à l'année académique active {$anneeActiveLabel}.",
        ]); ?>
    <?php endif; ?>
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur is-compact">
            <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmPaiementForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form id="cmPaiementForm" method="POST" action="?page=gestion_scolarite&action=enregistrer_paiement">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" id="cmIsNewInscription" name="is_new_inscription" value="">
                <!-- Ligne 1: Niveau, Année A., Frais Scolarité -->
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/select', [
                        'name' => 'niveau',
                        'id' => 'cmNiveau',
                        'label' => 'Niveau',
                        'options' => $niveauxOptions,
                        'selected' => 'M2',
                        'required' => true,
                        'control_class' => 'cm-field-md',
                    ]);
                    ?>
                    <input type="hidden" name="annee_academique" id="cmAnneeAcademique"
                        value="<?= htmlspecialchars((string) $anneeEcritureId, ENT_QUOTES, 'UTF-8') ?>">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'frais_scolarite_display',
                        'id' => 'cmFraisScolarite',
                        'label' => 'Frais',
                        'readonly' => true,
                        'value' => '',
                        'control_class' => 'cm-field-md',
                    ]);
                    ?>
                </div>
                <!-- Ligne 2: Nom Prénom, Identifiant, N° Carte -->
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/select-search', [
                        'name' => 'etudiant',
                        'id' => 'cmEtudiantSelect',
                        'label' => 'Nom Prénom',
                        'options' => $studentOptions,
                        'required' => true,
                        'placeholder' => '-- Sélectionner --',
                        'dense' => true,
                        'size' => 'sm',
                        'show_selected_label' => false,
                        'control_class' => 'cm-field-lg',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'identifiant_display',
                        'id' => 'cmIdentifiantDisplay',
                        'label' => 'Identifiant',
                        'readonly' => true,
                        'control_class' => 'cm-field-md',
                        'maxlength' => 15,
                        'attrs' => ['size' => '15'],
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'num_carte_display',
                        'id' => 'cmNumCarteDisplay',
                        'label' => 'N° Carte',
                        'readonly' => true,
                        'control_class' => 'cm-field-md',
                        'maxlength' => 15,
                        'attrs' => ['size' => '15'],
                    ]);
                    ?>
                </div>
                <!-- Ligne 3: Versement + Paiement (compact) -->
                <div class="cm-grid-5">
                    <?php
                    cm_component('form/input-number', [
                        'name' => 'num_versement_display',
                        'id' => 'cmNumVersement',
                        'label' => 'N° Vers.',
                        'readonly' => true,
                        'control_class' => 'cm-field-sm',
                        'attrs' => ['size' => '2', 'maxlength' => '2'],
                    ]);
                    cm_component('form/input-date', [
                        'name' => 'date_versement_display',
                        'id' => 'cmDateVersement',
                        'label' => 'Date',
                        'value' => date('Y-m-d'),
                        'required' => true,
                        'control_class' => 'cm-field-sm',
                        'attrs' => ['size' => '10', 'maxlength' => '10'],
                    ]);
                    cm_component('form/input-number', [
                        'name' => 'montant_versement',
                        'id' => 'cmMontantVersement',
                        'label' => 'Montant',
                        'required' => true,
                        'min' => 1,
                        'control_class' => 'cm-field-md',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'reste_a_payer_display',
                        'id' => 'cmResteAPayer',
                        'label' => 'Reste',
                        'readonly' => true,
                        'value' => '',
                        'control_class' => 'cm-field-md',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'solde_display',
                        'id' => 'cmSolde',
                        'label' => 'Solde',
                        'readonly' => true,
                        'value' => '',
                        'control_class' => 'cm-field-md',
                    ]);
                    cm_component('form/select', [
                        'name' => 'methode_paiement',
                        'id' => 'cmModePaiement',
                        'label' => 'Mode',
                        'required' => true,
                        'options' => [
                            'Espece' => 'Espece',
                            'Cheque' => 'Cheque',
                            'Virement' => 'Virement',
                            'Mobile Money' => 'Mobile Money',
                            'Wave' => 'Wave',
                        ],
                        'control_class' => 'cm-field-md',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'num_piece',
                        'id' => 'cmNumPiece',
                        'label' => 'N° M.P',
                        'maxlength' => 30,
                        'control_class' => 'cm-field-md',
                    ]);
                    ?>
                </div>
                <!-- Hidden field -->
                <input type="hidden" id="cmInfoEtudiant" name="cmInfoEtudiant" value="">
                <?php
                $paiementFormActions = [
                    ['label' => 'Réinitialiser', 'type' => 'reset', 'class' => 'cm-btn is-secondary is-sm', 'attrs' => ['id' => 'cmResetPaiement']],
                ];
                if (canCreate() || canEdit()) {
                    $paiementFormActions[] = ['label' => 'Valider', 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm'];
                }
                cm_component('crud/form-actions', [
                    'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']],
                    'actions' => $paiementFormActions,
                    'dense' => true,
                ]);
                ?>
            </form>
        </div>
        <?php
        // Préparer les options de filtres dynamiques
        $niveauxFilterOptions = ['' => 'Tous'];
        foreach ($niveauxOptions as $idNiveau => $libelle) {
            $niveauxFilterOptions[$idNiveau] = $libelle;
        }

        cm_toolbar([
            'screen' => 'gestion_scolarite',
            'id_prefix' => 'cmScolarite',
            'search_name' => 'search',
            'search_value' => $_GET['search'] ?? '',
            'search_placeholder' => 'Rechercher (étudiant, numero, mode)...',
            'limit' => $versementsParPage,
            'limit_options' => $allowedLimits,
            'limit_name' => 'limit_versements',
            'can_delete' => canDelete(),
            'can_view' => canView(),
            'filters' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => $niveauxFilterOptions],
                ['type' => 'select', 'name' => 'statut_paiement', 'label' => 'Statut paiement', 'options' => ['' => 'Tous', 'solde' => 'Soldé', 'partiel' => 'Partiel', 'non_solde' => 'Non soldé']],
                ['type' => 'select', 'name' => 'mode_paiement', 'label' => 'Mode paiement', 'options' => ['' => 'Tous', 'especes' => 'Espèces', 'cheque' => 'Chèque', 'virement' => 'Virement', 'mobile' => 'Mobile Money']],
                ['type' => 'date_range', 'name' => 'date_versement', 'label' => 'Date de versement'],
            ],
        ]);
        ?>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table cm-data-table--compact" id="cmVersementsTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" id="cmCheckAllVersements" class="cm-checkbox"
                                    aria-label="Sélectionner toutes les lignes">
                            </th>
                            <th class="cm-data-table__th">ID MESRS</th>
                            <th class="cm-data-table__th">Nom &amp; Prénom</th>
                            <th class="cm-data-table__th">N° Versement</th>
                            <th class="cm-data-table__th">Date Versement</th>
                            <th class="cm-data-table__th">Année Acad.</th>
                            <th class="cm-data-table__th">Montant versé</th>
                            <th class="cm-data-table__th">Reste</th>
                            <th class="cm-data-table__th">Solde</th>
                            <th class="cm-data-table__th">Mode paiement</th>
                            <th class="cm-data-table__th">N° Moyen Paiement</th>
                            <th class="cm-data-table__th">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmVersementsTableBody">
                        <?php if (empty($versementsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 12,
                                'title' => '',
                                'message' => 'Aucune inscription / aucun versement trouve.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($versementsToShow as $versement): ?>
                                <?php
                                $numEtu = (string) ($versement['num_carte_etud'] ?? '');
                                $nomPrenom = trim((string) ($versement['nom_etu'] ?? '') . ' ' . (string) ($versement['prenom_etu'] ?? ''));
                                $numVersement = (int) ($versement['num_versement'] ?? 0);
                                $dateVersement = !empty($versement['date_versement']) ? date('d/m/Y', strtotime((string) $versement['date_versement'])) : '';
                                $anneeVersement = $anneesOptions[(int) ($versement['id_annee_acad'] ?? 0)] ?? '';
                                $montant = (float) ($versement['montant_verser'] ?? 0);
                                $resteVersement = (float) ($versement['reste_a_payer'] ?? 0);
                                $soldeVersement = (float) ($versement['solde'] ?? $resteVersement);
                                $mode = (string) ($versement['methode_paiement'] ?? '');
                                $numPiece = (string) ($versement['num_piece_mp'] ?? '');
                                $niveauLib = strtolower((string) ($versement['lib_niv_etude'] ?? ''));
                                $niveauId = (string) ($versement['id_niv_etude'] ?? '');
                                // Créer un ID composite pour le reçu
                                $idInscriptionComposite = $numEtu . '-' . ($versement['id_annee_acad'] ?? '') . '-' . $numVersement;
                                $statutPaiement = $soldeVersement <= 0 ? 'solde' : 'partiel';
                                $modeNormalized = strtolower(trim($mode));
                                $modeFilterKey = '';
                                if (strpos($modeNormalized, 'espe') !== false) {
                                    $modeFilterKey = 'especes';
                                } elseif (strpos($modeNormalized, 'cheq') !== false) {
                                    $modeFilterKey = 'cheque';
                                } elseif (strpos($modeNormalized, 'vir') !== false) {
                                    $modeFilterKey = 'virement';
                                } elseif (strpos($modeNormalized, 'mobile') !== false || strpos($modeNormalized, 'wave') !== false) {
                                    $modeFilterKey = 'mobile';
                                }
                                $dateVersementRaw = !empty($versement['date_versement']) ? date('Y-m-d', strtotime((string) $versement['date_versement'])) : '';
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?php echo htmlspecialchars(strtolower($numEtu . ' ' . $nomPrenom . ' ' . $mode . ' ' . $numPiece), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-niveau="<?php echo htmlspecialchars($niveauLib, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-niveau-id="<?php echo htmlspecialchars($niveauId, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-statut="<?php echo htmlspecialchars($statutPaiement, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-mode="<?php echo htmlspecialchars($modeFilterKey, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-date="<?php echo htmlspecialchars($dateVersementRaw, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="cm-data-table__td cm-data-table__td--check">
                                        <input type="checkbox" class="cm-checkbox cm-row-checkbox">
                                    </td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($nomPrenom, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) $numVersement, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($dateVersement, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($anneeVersement !== '' ? $anneeVersement : '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars(number_format($montant, 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars(number_format($resteVersement, 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars(number_format($soldeVersement, 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($numPiece, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center is-actions">
                                        <div class="cm-table-actions">
                                            <button type="button" class="cm-btn-action is-edit cmPrefillPaiement"
                                                data-student="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Pre-remplir le formulaire">
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                            </button>
                                            <a class="cm-btn-action is-edit"
                                                href="?page=gestion_scolarite&action=imprimer_recu&id=<?php echo urlencode($idInscriptionComposite); ?>"
                                                target="_blank" title="Imprimer recu">
                                                <i class="fas fa-receipt" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php cm_component('crud/pagination', [
                'pagination' => $versementPagination,
                'base_url' => $paginationBaseUrl,
                'param_name' => 'page_versements',
            ]); ?>
        </div>
    </div>
</div>
<script>
    (function () {
        const catalog = <?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const niveauxMontants = <?php echo json_encode($niveauxMontants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const navigate = function (url) {
            if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                window.CM.ajax.load(url);
                return;
            }
            window.location.href = url;
        };
        const etudiantHidden = document.getElementById('cmEtudiantSelect_hidden');
        const etudiantSearchInput = document.getElementById('cmEtudiantSelect_search');
        const etudiantSelectedLabel = document.getElementById('cmEtudiantSelect_selected_label');
        const niveauField = document.getElementById('cmNiveau');
        const fraisField = document.getElementById('cmFraisScolarite');
        const identifiantField = document.getElementById('cmIdentifiantDisplay');
        const numCarteField = document.getElementById('cmNumCarteDisplay');
        const numVersementField = document.getElementById('cmNumVersement');
        const montantVerseField = document.getElementById('cmMontantVersement');
        const resteField = document.getElementById('cmResteAPayer');
        const soldeField = document.getElementById('cmSolde');
        const modePaiementField = document.getElementById('cmModePaiement');
        const anneeField = document.getElementById('cmAnneeAcademique');
        const infoField = document.getElementById('cmInfoEtudiant');
        const isNewField = document.getElementById('cmIsNewInscription');
        const form = document.getElementById('cmPaiementForm');
        const formatNumber = function (value) {
            const parsed = Number(value || 0);
            return parsed.toLocaleString('fr-FR');
        };
        const parseNumber = function (value) {
            return Number(String(value || '0').replace(/\s/g, '').replace(',', '.')) || 0;
        };
        const syncByStudent = function () {
            if (!etudiantHidden) {
                return;
            }
            const studentId = etudiantHidden.value;
            const data = catalog[studentId];
            if (!data) {
                identifiantField.value = '';
                numCarteField.value = '';
                numVersementField.value = '';
                fraisField.value = '';
                resteField.value = '';
                if (soldeField) {
                    soldeField.value = '';
                }
                infoField.value = '';
                isNewField.value = '';
                return;
            }
            const isInscrit = !!data.inscrit;
            isNewField.value = isInscrit ? 'false' : 'true';
            identifiantField.value = data.num_ident || '';
            numCarteField.value = data.num_carte || '';
            numVersementField.value = String(data.num_versement || 1);
            if (isInscrit) {
                if (data.id_niveau && niveauField) {
                    niveauField.value = String(data.id_niveau);
                }
                if (data.id_annee && anneeField) {
                    anneeField.value = String(data.id_annee);
                }
                const montantTotal = Number(data.montant_total || 0);
                const montantPaye = Number(data.montant_paye || 0);
                const reste = Number(data.reste_a_payer || 0);
                const solde = Number(data.solde !== undefined ? data.solde : reste);
                fraisField.value = formatNumber(montantTotal);
                resteField.value = formatNumber(reste);
                if (soldeField) {
                    soldeField.value = formatNumber(solde);
                }
                infoField.value = 'Deja inscrit - montant paye: ' + formatNumber(montantPaye) + ' FCFA';
            } else {
                infoField.value = 'Nouvelle inscription';
                if (niveauField && niveauField.value && niveauxMontants[niveauField.value] !== undefined) {
                    fraisField.value = formatNumber(niveauxMontants[niveauField.value]);
                } else {
                    fraisField.value = '';
                }
                updateReste();
            }
        };
        const setSelectSearchValue = function (studentId) {
            const options = document.querySelectorAll('#cmEtudiantSelect_wrapper .cm-select-search__option');
            let selectedLabel = '';
            options.forEach(function (option) {
                const isSelected = option.getAttribute('data-value') === studentId;
                option.classList.toggle('is-selected', isSelected);
                option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                if (isSelected) {
                    selectedLabel = option.getAttribute('data-label') || '';
                }
            });
            if (etudiantSearchInput) {
                etudiantSearchInput.value = selectedLabel;
            }
            if (etudiantSelectedLabel) {
                etudiantSelectedLabel.textContent = selectedLabel || '-- Sélectionner un étudiant --';
            }
        };
        const updateFraisFromNiveau = function () {
            if (!niveauField) {
                return;
            }
            const niveauId = niveauField.value;
            const montant = niveauxMontants[niveauId] !== undefined ? Number(niveauxMontants[niveauId]) : 0;
            fraisField.value = montant > 0 ? formatNumber(montant) : '';
            updateReste();
        };
        const updateReste = function () {
            const studentId = etudiantHidden ? etudiantHidden.value : '';
            const data = catalog[studentId];
            const montantScolarite = parseNumber(fraisField.value);
            const montantVerse = parseNumber(montantVerseField.value);
            if (data && data.inscrit) {
                const reste = Number(data.reste_a_payer || 0);
                const solde = Math.max(0, reste - montantVerse);
                resteField.value = formatNumber(solde);
                if (soldeField) {
                    soldeField.value = formatNumber(solde);
                }
            } else {
                const reste = Math.max(0, montantScolarite - montantVerse);
                resteField.value = montantScolarite > 0 ? formatNumber(reste) : '';
                if (soldeField) {
                    soldeField.value = montantScolarite > 0 ? formatNumber(reste) : '';
                }
            }
        };
        if (etudiantHidden) {
            etudiantHidden.addEventListener('change', syncByStudent);
            document.querySelectorAll('#cmEtudiantSelect_wrapper .cm-select-search__option').forEach(function (option) {
                option.addEventListener('click', function () {
                    setTimeout(syncByStudent, 0);
                });
            });
        }
        document.querySelectorAll('.cmPrefillPaiement').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!etudiantHidden) {
                    return;
                }
                const studentId = button.getAttribute('data-student') || '';
                if (!studentId) {
                    return;
                }
                etudiantHidden.value = studentId;
                setSelectSearchValue(studentId);
                syncByStudent();
            });
        });
        if (niveauField) {
            niveauField.addEventListener('change', updateFraisFromNiveau);
        }
        if (montantVerseField) {
            montantVerseField.addEventListener('input', updateReste);
        }
        const resetPaiementBtn = document.getElementById('cmResetPaiement');
        if (resetPaiementBtn) {
            resetPaiementBtn.addEventListener('click', function () {
                setTimeout(function () {
                    if (isNewField) {
                        isNewField.value = '';
                    }
                    if (infoField) {
                        infoField.value = '';
                    }
                    if (fraisField) {
                        fraisField.value = '';
                    }
                    if (resteField) {
                        resteField.value = '';
                    }
                    if (soldeField) {
                        soldeField.value = '';
                    }
                    if (numVersementField) {
                        numVersementField.value = '';
                    }
                }, 0);
            });
        }
        if (form) {
            form.addEventListener('submit', function (event) {
                if (!etudiantHidden || !etudiantHidden.value) {
                    event.preventDefault();
                    window.alert('Veuillez sélectionner un étudiant.');
                    return;
                }
                const currentData = catalog[etudiantHidden.value];
                const montantVerse = parseNumber(montantVerseField.value);
                if (montantVerse <= 0) {
                    event.preventDefault();
                    window.alert('Le montant verse doit etre strictement positif.');
                    return;
                }
                if (currentData && currentData.inscrit) {
                    const reste = Number(currentData.reste_a_payer || 0);
                    if (montantVerse > reste) {
                        event.preventDefault();
                        window.alert('Le montant verse ne peut pas depasser le reste a payer (' + formatNumber(reste) + ' FCFA).');
                        return;
                    }
                } else {
                    const montantScolarite = parseNumber(fraisField.value);
                    if (montantScolarite > 0 && montantVerse > montantScolarite) {
                        event.preventDefault();
                        window.alert('Le montant verse ne peut pas depasser les frais de scolarite.');
                        return;
                    }
                    if (!niveauField.value || !anneeField.value) {
                        event.preventDefault();
                        window.alert('Niveau et année académique sont obligatoires pour une nouvelle inscription.');
                        return;
                    }
                }
                if (!modePaiementField.value) {
                    event.preventDefault();
                    window.alert('Veuillez sélectionner un mode de paiement.');
                }
            });
        }
        var toolbarId = 'cmScolarite_toolbar';
        var rows = function () { return Array.from(document.querySelectorAll('#cmVersementsTableBody tr')); };
        var rowCheckboxes = function () {
            return Array.from(document.querySelectorAll('#cmVersementsTableBody .cm-row-checkbox'));
        };
        var checkAllRows = document.getElementById('cmCheckAllVersements');
        var updateSelectionState = function () {
            if (!checkAllRows) return;
            var checkboxes = rowCheckboxes();
            checkAllRows.checked = checkboxes.length > 0 && checkboxes.every(function (cb) { return cb.checked; });
        };

        if (checkAllRows) {
            checkAllRows.addEventListener('change', function () {
                rowCheckboxes().forEach(function (cb) { cb.checked = checkAllRows.checked; });
                updateSelectionState();
            });
        }
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('cm-row-checkbox')) {
                updateSelectionState();
            }
        });

        var searchInput = document.getElementById('cmScolarite_search');
        if (searchInput) {
            searchInput.addEventListener('input', updateSelectionState);
            searchInput.addEventListener('keyup', updateSelectionState);
        }

        document.addEventListener('cm:toolbar:delete', function (event) {
            if (!event.detail || !event.detail.toolbar) return;
            if (event.detail.toolbar.id !== toolbarId) return;
            window.alert('Suppression multiple indisponible sur cet ecran.');
        });

        document.addEventListener('cm:toolbar:limit:change', function (event) {
            if (!event.detail || !event.detail.toolbar) return;
            if (event.detail.toolbar.id !== toolbarId) return;
            event.preventDefault();
            var limit = event.detail.limit || '10';
            var url = new URL(window.location.href);
            url.searchParams.set('limit_versements', limit);
            url.searchParams.set('page_versements', '1');
            navigate(url.toString());
        });
        syncByStudent();
        updateSelectionState();
        // Initialiser les frais pour le niveau par défaut (M2)
        updateFraisFromNiveau();
    })();
</script>

