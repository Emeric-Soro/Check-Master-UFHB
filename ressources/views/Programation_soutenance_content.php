<?php
require_once __DIR__ . '/../../app/controllers/ProgrammationSoutenanceController.php';
$controller = new ProgrammationSoutenanceController();
$currentPageSlug = (string) ($_GET['page'] ?? 'programmation_soutenance');
$action = (string) ($_GET['action'] ?? '');
if ($action !== '') {
    switch ($action) {
        case 'getEtudiants':
            $controller->getEtudiants();
            exit;
        case 'getEnseignants':
            $controller->getEnseignants();
            exit;
        case 'getProfesseursTitulaires':
            $controller->getProfesseursTitulaires();
            exit;
        case 'getSalles':
            $controller->getSalles();
            exit;
        case 'getAttributions':
            $controller->getAttributions();
            exit;
        case 'createAttribution':
            $controller->createAttribution();
            exit;
        case 'updateAttribution':
            $controller->updateAttribution();
            exit;
        case 'deleteAttribution':
            $controller->deleteAttribution();
            exit;
        case 'getPlanningPreview':
            $controller->getPlanningPreview();
            exit;
        case 'getDayDetails':
            $controller->getDayDetails();
            exit;
        case 'generatePlanningPdf':
            $controller->generatePlanningPdf();
            exit;
        case 'downloadPlanningPdf':
            $controller->downloadPlanningPdf();
            exit;
    }
}
$etudiants = $controller->getEtudiantsForView();
$enseignants = $controller->getEnseignantsForView();
$professeursTitulaires = $controller->getProfesseursTitulairesForView();
$salles = $controller->getSallesForView();
$attributions = $controller->getAttributionsForView();
$studentMap = [];
$studentOptions = [];
foreach ($etudiants as $etu) {
    $id = (string) ($etu['id_etudiant'] ?? '');
    if ($id === '') {
        continue;
    }
    $label = trim((string) ($etu['nom_complet'] ?? 'Etudiant'));
    $matricule = trim((string) ($etu['matricule_etudiant'] ?? $id));
    $themeRapport = trim((string) ($etu['theme_rapport'] ?? ''));
    $studentMap[$id] = [
        'id_etudiant' => $id,
        'nom_complet' => $label,
        'matricule_etudiant' => $matricule,
        'promotion_etu' => (string) ($etu['promotion_etu'] ?? ''),
        'theme_rapport' => $themeRapport,
        'directeur_nom' => trim((string) ($etu['directeur_nom'] ?? '')),
        'directeur_id' => (string) ($etu['directeur_id'] ?? ''),
        'encadreur_nom' => trim((string) ($etu['encadreur_nom'] ?? '')),
        'encadreur_id' => (string) ($etu['encadreur_id'] ?? ''),
        'maitre_stage_nom' => trim((string) ($etu['maitre_stage_nom'] ?? '')),
        'maitre_stage_id' => (string) ($etu['id_maitre_stage'] ?? ''),
    ];
    $studentOptions[$id] = $label . ' (' . $matricule . ')'
        . (\AcademicYear::isAllSelectedFromSession() && !empty($etu['promotion_etu']) ? ' - ' . (string) $etu['promotion_etu'] : '');
}
$enseignantOptions = [];
foreach ($enseignants as $ens) {
    $id = (string) ($ens['id_enseignant'] ?? '');
    if ($id === '') {
        continue;
    }
    $enseignantOptions[$id] = trim((string) ($ens['nom_complet'] ?? ('Enseignant #' . $id)));
}
$presidentOptions = [];
foreach ($professeursTitulaires as $ens) {
    $id = (string) ($ens['id_enseignant'] ?? '');
    if ($id === '') {
        continue;
    }
    $presidentOptions[$id] = trim((string) ($ens['nom_complet'] ?? ('Professeur #' . $id)));
}
if (empty($presidentOptions)) {
    $presidentOptions = $enseignantOptions;
}
$salleOptions = [];
foreach ($salles as $salle) {
    $id = (string) ($salle['id_salle'] ?? '');
    if ($id === '') {
        continue;
    }
    $salleOptions[$id] = trim((string) ($salle['lib_salle'] ?? ('Salle #' . $id)));
}
$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_prog'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_prog'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($attributions), $perPage, $currentPage)
    : [
        'total' => count($attributions),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];

foreach ($attributions as $row) {
    $studentId = trim((string) ($row['id_etudiant'] ?? ''));
    if ($studentId === '' || isset($studentMap[$studentId])) {
        continue;
    }

    $studentName = trim((string) ($row['nom_etudiant'] ?? 'Etudiant'));
    $studentMatricule = trim((string) ($row['matricule_etudiant'] ?? $studentId));
    $promotion = trim((string) ($row['promotion_etu'] ?? ''));

    $studentMap[$studentId] = [
        'id_etudiant' => $studentId,
        'nom_complet' => $studentName,
        'matricule_etudiant' => $studentMatricule,
        'promotion_etu' => $promotion,
        'theme_rapport' => trim((string) ($row['theme_soutenance'] ?? '')),
        'directeur_nom' => trim((string) ($row['directeur_nom'] ?? '')),
        'directeur_id' => (string) ($row['directeur_id'] ?? ''),
        'encadreur_nom' => trim((string) ($row['encadreur_nom'] ?? '')),
        'encadreur_id' => (string) ($row['encadreur_id'] ?? ''),
        'maitre_stage_nom' => trim((string) ($row['maitre_stage_nom'] ?? '')),
        'maitre_stage_id' => (string) ($row['maitre_stage_ref'] ?? $row['maitre_stage_id'] ?? ''),
    ];

    $studentOptions[$studentId] = $studentName . ' (' . $studentMatricule . ')'
        . (\AcademicYear::isAllSelectedFromSession() && $promotion !== '' ? ' - ' . $promotion : '');
}

$rowsToShow = array_slice($attributions, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=' . urlencode($currentPageSlug) . '&limit_prog=' . $perPage;
$selectedYearLabel = \AcademicYear::getSelectedLabelFromSession();
$activeYearLabel = \AcademicYear::getActiveLabelFromSession();
$writeYearLabel = \AcademicYear::getWritableLabelFromSession();
$writeYearId = \AcademicYear::getWritableIdFromSession();
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writeAllowed = \AcademicYear::isWriteAllowedFromSession();
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div id="cmProgAlert"></div>
    <div class="cm-pole-superieur is-compact">
        <style>
            /* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
            #cmProgForm .cm-form-group:has(#FIELD_ID) {
                width: 10ch !important;
                min-width: 10ch !important;
                max-width: 10ch !important;
            }

            .cm-prog-toolbar {
                overflow-x: hidden;
                padding-bottom: 0.01rem;
                padding-top: 0.01rem;
            }

            .cm-prog-toolbar__row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.22rem 0.28rem;
                width: 100%;
                min-width: 0;
                font-size: 0.68rem;
            }

            .cm-prog-toolbar__row .cm-form-control.cm-toolbar-field-lg {
                width: 7.2rem;
                min-width: 7.2rem;
            }

            #cmProgSearch {
                flex: 1 1 9.5rem;
                min-width: 8rem;
            }

            .cm-prog-toolbar__row .cm-form-control.cm-toolbar-field-sm {
                width: 5.2rem;
                min-width: 5.2rem;
            }

            .cm-prog-toolbar__row .cm-form-control.cm-toolbar-field-xs {
                width: 3.2rem;
                min-width: 3.2rem;
            }

            .cm-prog-toolbar__row .cm-form-control {
                min-height: 18px;
                padding: 0.06rem 0.14rem;
                font-size: 0.62rem;
                line-height: 1.0;
            }

            .cm-prog-toolbar__row .cm-btn.is-sm {
                min-height: 18px;
                padding: 0.06rem 0.16rem;
                font-size: 0.62rem;
                line-height: 1.0;
            }

            #cmProgSelectedCount {
                font-size: 0.68rem;
                padding: 0.08rem 0.28rem;
            }

            .cm-prog-toolbar__row .cm-btn,
            .cm-prog-toolbar__row a.cm-btn,
            .cm-prog-toolbar__row label {
                white-space: nowrap;
            }

            #cmProgPlanning {
                margin-left: auto;
            }

            @media (max-width: 980px) {
                #cmProgPlanning {
                    margin-left: 0;
                }
            }

            .cm-prog-planning-overlay {
                position: fixed;
                inset: 0;
                z-index: 1200;
                background: rgba(0, 0, 0, 0.45);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .cm-prog-planning-modal {
                background: #fff;
                border-radius: 10px;
                width: min(860px, 96vw);
                max-height: 92vh;
                overflow-y: auto;
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
                padding: 1rem;
            }

            .cm-prog-planning-modal--narrow {
                width: min(460px, 96vw);
            }

            .cm-prog-planning-modal h3 {
                margin: 0 0 0.75rem 0;
            }

            .cm-prog-planning-day {
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 0.5rem 0.75rem;
                margin-bottom: 0.5rem;
            }

            .cm-prog-planning-day h4 {
                margin: 0 0 0.4rem 0;
                font-size: 0.95rem;
            }

            .cm-prog-planning-day ul {
                margin: 0;
                padding-left: 1rem;
            }

            .cm-prog-planning-summary-meta {
                margin-top: 0.6rem;
                font-size: 0.9rem;
            }

            .cm-prog-planning-summary-meta p {
                margin: 0.2rem 0;
            }

            .cm-prog-planning-actions {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
                margin-top: 0.9rem;
            }
        </style>
        <form id="cmProgForm" autocomplete="off">
            <?php cm_component('form/csrf-token'); ?>
            <input type="hidden" id="cmProgEditId" value="">

            <div class="cm-grid-4">
                <?php
                cm_component('form/select', [
                    'name' => 'cm_prog_etudiant',
                    'id' => 'cmProgEtudiant',
                    'label' => 'Etudiant',
                    'required' => true,
                    'options' => $studentOptions,
                    'control_class' => 'cm-field-lg cm-size-personne',
                ]);
                cm_component('form/select', [
                    'name' => 'cm_prog_salle',
                    'id' => 'cmProgSalle',
                    'label' => 'Salle',
                    'required' => true,
                    'options' => $salleOptions,
                    'control_class' => 'cm-field-md cm-size-salle',
                ]);
                cm_component('form/input-date', [
                    'name' => 'cm_prog_date',
                    'id' => 'cmProgDate',
                    'label' => 'Date soutenance',
                    'required' => true,
                    'value' => date('Y-m-d'),
                    'control_class' => 'cm-field-sm cm-size-date',
                    'attrs' => ['size' => '10'],
                ]);
                cm_component('form/input-text', [
                    'name' => 'cm_prog_heure',
                    'id' => 'cmProgHeure',
                    'label' => 'Heure',
                    'required' => true,
                    'value' => date('H:i'),
                    'attrs' => ['placeholder' => 'HH:MM', 'size' => '5', 'maxlength' => '5'],
                    'control_class' => 'cm-field-xs cm-size-heure',
                ]);
                ?>
            </div>
            <?php
            cm_component('form/textarea', [
                'name' => 'cm_prog_theme',
                'id' => 'cmProgTheme',
                'label' => 'Theme',
                'required' => true,
                'rows' => 2,
                'placeholder' => 'Theme de soutenance',
                'readonly' => true,
                'control_class' => 'cm-field-full cm-size-theme',
            ]);
            ?>
            <div class="cm-grid-3">
                <?php
                cm_component('form/select', [
                    'name' => 'cm_prog_president',
                    'id' => 'cmProgPresident',
                    'label' => 'President',
                    'required' => true,
                    'options' => $presidentOptions,
                    'control_class' => 'cm-field-lg cm-size-personne',
                ]);
                cm_component('form/select', [
                    'name' => 'cm_prog_examinateur',
                    'id' => 'cmProgExaminateur',
                    'label' => 'Examinateur',
                    'required' => true,
                    'options' => $enseignantOptions,
                    'control_class' => 'cm-field-lg cm-size-personne',
                ]);
                cm_component('form/input-text', [
                    'name' => 'cm_prog_directeur',
                    'id' => 'cmProgDirecteur',
                    'label' => 'Directeur mémoire',
                    'readonly' => true,
                    'control_class' => 'cm-field-lg cm-size-personne',
                ]);
                ?>
            </div>
            <div class="cm-grid-3">
                <?php
                cm_component('form/input-text', [
                    'name' => 'cm_prog_encadreur',
                    'id' => 'cmProgEncadreur',
                    'label' => 'Encadreur Pédagogique',
                    'readonly' => true,
                    'control_class' => 'cm-field-lg cm-size-personne',
                ]);
                cm_component('form/input-text', [
                    'name' => 'cm_prog_maitre',
                    'id' => 'cmProgMaitreStage',
                    'label' => 'Maître stage',
                    'readonly' => true,
                    'control_class' => 'cm-field-lg cm-size-personne',
                ]);
                ?>
            </div>
            <input type="hidden" id="cmProgDirecteurId" value="">
            <input type="hidden" id="cmProgEncadreurId" value="">
            <input type="hidden" id="cmProgMaitreId" value="">

            <?php
            $progFormActions = [
                ['label' => 'Réinitialiser', 'type' => 'button', 'class' => 'cm-btn is-secondary is-sm', 'icon' => 'fa-rotate-left', 'attrs' => ['id' => 'cmProgResetBtn']],
            ];
            if ((function_exists('canCreate') && canCreate()) || (function_exists('canEdit') && canEdit())) {
                $progFormActions[] = ['label' => 'Programmer', 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm', 'icon' => 'fa-check', 'attrs' => ['id' => 'cmProgSubmitBtn']];
            }
            cm_component('crud/form-actions', [
                'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']],
                'actions' => $progFormActions,
            ]);
            ?>
        </form>
    </div>
    <div class="cm-barre-intermediaire">
        <div class="cm-toolbar cm-prog-toolbar">
            <div class="cm-prog-toolbar__row">
                <label for="cmProgLimit"><strong>Afficher:</strong></label>
                <select id="cmProgLimit" class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs"
                    data-cm-ajax-param="limit_prog" data-cm-ajax-reset-param="page_prog" data-cm-ajax-reset-value="1">
                    <?php foreach ($allowedLimits as $limit): ?>
                        <option value="<?php echo $limit; ?>" <?php echo $limit === $perPage ? 'selected' : ''; ?>>
                            <?php echo $limit; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="cmProgSearch" class="cm-form-control cm-toolbar-field-lg"
                    placeholder="Rechercher...">
                <button type="button" class="cm-btn is-info is-xs" id="cmProgSelectAllBtn">Tout sélectionner</button>
                <button type="button" class="cm-btn is-light is-xs" id="cmProgDeselectBtn">Tout désélectionner</button>
                <button type="button" class="cm-btn is-light is-xs" id="cmProgDeleteBtn" disabled>Supprimer (0)</button>
                <span class="cm-badge is-info is-pill" id="cmProgSelectedCount">0 sélectionnée(s)</span>
                <label for="cmProgSortMode"><strong>Organisation:</strong></label>
                <select id="cmProgSortMode" class="cm-form-control cm-form-select is-sm cm-toolbar-field-sm">
                    <option value="chrono">Chronologique</option>
                    <option value="room">Chronologique (salle secondaire)</option>
                </select>
                <button type="button" class="cm-btn is-light is-xs" id="cmProgPrint">Imprimer</button>
                <button type="button" class="cm-btn is-info is-xs" id="cmProgExport">Exporter</button>
                <button type="button" class="cm-btn is-info is-sm" id="cmProgPlanning">Planning</button>
            </div>
        </div>
    </div>
    <div class="cm-pole-inferieur">
        <div class="cm-table-wrapper">
            <table class="cm-data-table" id="cmProgTable">
                <thead>
                    <tr>
                        <th class="cm-data-table__th cm-data-table__th--check">
                            <input type="checkbox" id="cmProgCheckAll" aria-label="Tout sélectionner">
                        </th>
                        <th class="cm-data-table__th">Nom &amp; Prénom Étudiant</th>
                        <th class="cm-data-table__th">Promotion</th>
                        <th class="cm-data-table__th">Date Soutenance</th>
                        <th class="cm-data-table__th">Heure</th>
                        <th class="cm-data-table__th">Salle</th>
                        <th class="cm-data-table__th">Président</th>
                        <th class="cm-data-table__th">Dir. mémoire</th>
                        <th class="cm-data-table__th">Examinateur</th>
                        <th class="cm-data-table__th">Encadreur Péda.</th>
                        <th class="cm-data-table__th">Maître de stage</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="cmProgTableBody">
                    <?php if (empty($rowsToShow)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 12,
                            'title' => '',
                            'message' => 'Aucune soutenance programmee pour le moment.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($rowsToShow as $index => $row): ?>
                            <?php
                            $idAttribution = trim((string) ($row['id_attribution'] ?? ''));
                            $rowYearId = is_numeric($row['id_annee_acad'] ?? null) ? (int) ($row['id_annee_acad'] ?? 0) : null;
                            $rowIsWritable = $writeYearId !== null && $rowYearId !== null && $rowYearId === $writeYearId;
                            $idEtudiant = (string) ($row['id_etudiant'] ?? '');
                            $nomEtudiant = (string) ($row['nom_etudiant'] ?? '');
                            $matricule = (string) ($row['matricule_etudiant'] ?? '');
                            $theme = (string) ($row['theme_soutenance'] ?? '');
                            $dateRaw = (string) ($row['date_soutenance'] ?? '');
                            $heureRaw = (string) ($row['heure_soutenance'] ?? '');
                            $dateDisplay = ($dateRaw !== '' && strpos($dateRaw, '0000-00-00') !== 0) ? date('d/m/Y', strtotime($dateRaw)) : '-';
                            $heureDisplay = $heureRaw !== '' ? date('H:i', strtotime($heureRaw)) : '-';
                            $salleId = (string) ($row['id_salle'] ?? '');
                            $salleNom = trim((string) ($row['nom_salle'] ?? ''));
                            $promotion = trim((string) ($row['promotion_etu'] ?? ''));
                            $editTitle = $rowIsWritable
                                ? 'Modifier'
                                : 'Modification impossible: seule l\'année active ' . $writeYearLabel . ' accepte des écritures.';
                            $deleteTitle = $rowIsWritable
                                ? 'Supprimer'
                                : 'Suppression impossible: seule l\'année active ' . $writeYearLabel . ' accepte des écritures.';
                            $searchText = strtolower(
                                $nomEtudiant . ' ' . $matricule . ' ' . $promotion . ' ' . $theme . ' ' . $dateDisplay . ' ' . $heureDisplay . ' ' . $salleNom
                            );
                            ?>
                            <tr class="cm-data-table__row"
                                data-id="<?php echo htmlspecialchars($idAttribution, ENT_QUOTES, 'UTF-8'); ?>"
                                data-is-writable="<?php echo $rowIsWritable ? '1' : '0'; ?>"
                                data-academic-year-id="<?php echo htmlspecialchars((string) ($rowYearId ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-writable-year-label="<?php echo htmlspecialchars($writeYearLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                data-id-etudiant="<?php echo htmlspecialchars($idEtudiant, ENT_QUOTES, 'UTF-8'); ?>"
                                data-etudiant-name="<?php echo htmlspecialchars($nomEtudiant, ENT_QUOTES, 'UTF-8'); ?>"
                                data-etudiant-matricule="<?php echo htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8'); ?>"
                                data-promotion="<?php echo htmlspecialchars($promotion, ENT_QUOTES, 'UTF-8'); ?>"
                                data-theme="<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>"
                                data-date="<?php echo htmlspecialchars($dateRaw, ENT_QUOTES, 'UTF-8'); ?>"
                                data-heure="<?php echo htmlspecialchars($heureRaw, ENT_QUOTES, 'UTF-8'); ?>"
                                data-salle-id="<?php echo htmlspecialchars($salleId, ENT_QUOTES, 'UTF-8'); ?>"
                                data-salle-name="<?php echo htmlspecialchars($salleNom, ENT_QUOTES, 'UTF-8'); ?>"
                                data-president-id="<?php echo htmlspecialchars((string) ($row['president_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-examinateur-id="<?php echo htmlspecialchars((string) ($row['examinateur_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-directeur-id="<?php echo htmlspecialchars((string) ($row['directeur_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-encadreur-id="<?php echo htmlspecialchars((string) ($row['encadreur_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-maitre-id="<?php echo htmlspecialchars((string) ($row['maitre_stage_ref'] ?? $row['maitre_stage_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-directeur-name="<?php echo htmlspecialchars((string) ($row['directeur_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-encadreur-name="<?php echo htmlspecialchars((string) ($row['encadreur_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-maitre-name="<?php echo htmlspecialchars((string) ($row['maitre_stage_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="cm-data-table__td cm-data-table__td--check">
                                    <input type="checkbox" class="cm-prog-check-row"
                                        value="<?php echo htmlspecialchars($idAttribution, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="Sélectionner ligne <?php echo htmlspecialchars($idAttribution, ENT_QUOTES, 'UTF-8'); ?>"
                                        title="<?php echo htmlspecialchars($rowIsWritable ? 'Sélectionner' : 'Sélection autorisée, mais suppression interdite hors année active.', ENT_QUOTES, 'UTF-8'); ?>">
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars($nomEtudiant, ENT_QUOTES, 'UTF-8'); ?><br>
                                    <small><?php echo htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars($promotion !== '' ? $promotion : '-', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars($heureDisplay, ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars($salleNom !== '' ? $salleNom : '-', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars((string) ($row['president_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars((string) ($row['directeur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars((string) ($row['examinateur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars((string) ($row['encadreur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars(trim((string) ($row['maitre_stage_nom'] ?? '')) !== '' ? (string) $row['maitre_stage_nom'] : 'non renseigné', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions">
                                        <?php if (function_exists('canEdit') ? canEdit() : true): ?>
                                            <button type="button" class="cm-btn-action is-edit cm-prog-edit"
                                                data-id="<?php echo htmlspecialchars($idAttribution, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="<?php echo htmlspecialchars($editTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (function_exists('canDelete') ? canDelete() : true): ?>
                                            <button type="button" class="cm-btn-action is-delete cm-prog-delete"
                                                data-id="<?php echo htmlspecialchars($idAttribution, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="<?php echo htmlspecialchars($deleteTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        cm_component('crud/pagination', [
            'pagination' => $pagination,
            'base_url' => $baseUrl,
            'param_name' => 'page_prog',
        ]);
        ?>
    </div>
</div>
</div>
<script>
    (function () {
        const currentPage = <?php echo json_encode($currentPageSlug); ?>;
        const studentsById = <?php echo json_encode($studentMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const form = document.getElementById('cmProgForm');
        const editIdInput = document.getElementById('cmProgEditId');
        const submitBtn = document.getElementById('cmProgSubmitBtn');
        const resetBtn = document.getElementById('cmProgResetBtn');
        const alertBox = document.getElementById('cmProgAlert');
        const etudiantSelect = document.getElementById('cmProgEtudiant');
        const dateInput = document.getElementById('cmProgDate');
        const heureInput = document.getElementById('cmProgHeure');
        const salleSelect = document.getElementById('cmProgSalle');
        const themeInput = document.getElementById('cmProgTheme');
        const presidentSelect = document.getElementById('cmProgPresident');
        const examinateurSelect = document.getElementById('cmProgExaminateur');
        const directeurInput = document.getElementById('cmProgDirecteur');
        const encadreurInput = document.getElementById('cmProgEncadreur');
        const maitreInput = document.getElementById('cmProgMaitreStage');
        const directeurIdInput = document.getElementById('cmProgDirecteurId');
        const encadreurIdInput = document.getElementById('cmProgEncadreurId');
        const maitreIdInput = document.getElementById('cmProgMaitreId');
        const searchInput = document.getElementById('cmProgSearch');
        const exportBtn = document.getElementById('cmProgExport');
        const printBtn = document.getElementById('cmProgPrint');
        const checkAll = document.getElementById('cmProgCheckAll');
        const selectAllBtn = document.getElementById('cmProgSelectAllBtn');
        const deselectBtn = document.getElementById('cmProgDeselectBtn');
        const deleteBtn = document.getElementById('cmProgDeleteBtn');
        const limitSelect = document.getElementById('cmProgLimit');
        const planningBtn = document.getElementById('cmProgPlanning');
        const sortModeSelect = document.getElementById('cmProgSortMode');
        const selectedCountEl = document.getElementById('cmProgSelectedCount');
        function setAlert(type, message) {
            if (!alertBox) {
                return;
            }
            const cssType = type === 'success' ? 'success' : 'danger';
            alertBox.innerHTML = '<div class="cm-alert is-' + cssType + '"><div class="cm-alert__content"><span class="cm-alert__message">' +
                String(message || '').replace(/[<>&]/g, '') +
                '</span></div></div>';
        }
        function escHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        function getRows() {
            return Array.from(document.querySelectorAll('#cmProgTableBody .cm-data-table__row'));
        }
        function getVisibleRows() {
            return getRows().filter(function (row) {
                return row.style.display !== 'none';
            });
        }
        function getCheckedRows() {
            return getRows().filter(function (row) {
                const cb = row.querySelector('.cm-prog-check-row');
                return cb && cb.checked;
            });
        }
        function normalizedTimeValue(raw) {
            return String(raw || '').slice(0, 5);
        }
        function normalizeDateValue(raw) {
            const value = String(raw || '').trim();
            if (value === '' || value.indexOf('0000-00-00') === 0) {
                return '';
            }
            return value.slice(0, 10);
        }
        function compareRowsByMode(left, right, mode) {
            const leftDate = left.getAttribute('data-date') || '';
            const rightDate = right.getAttribute('data-date') || '';
            if (leftDate !== rightDate) {
                return leftDate.localeCompare(rightDate);
            }
            const leftHeure = normalizedTimeValue(left.getAttribute('data-heure') || '');
            const rightHeure = normalizedTimeValue(right.getAttribute('data-heure') || '');
            if (leftHeure !== rightHeure) {
                return leftHeure.localeCompare(rightHeure);
            }
            if (mode === 'room') {
                const leftSalle = (left.getAttribute('data-salle-name') || '').toLowerCase();
                const rightSalle = (right.getAttribute('data-salle-name') || '').toLowerCase();
                if (leftSalle !== rightSalle) {
                    return leftSalle.localeCompare(rightSalle);
                }
            }
            return (left.getAttribute('data-id') || '').localeCompare(right.getAttribute('data-id') || '');
        }
        function applySortMode() {
            const tbody = document.getElementById('cmProgTableBody');
            if (!tbody) {
                return;
            }
            const mode = sortModeSelect ? (sortModeSelect.value || 'chrono') : 'chrono';
            const rows = getRows();
            rows.sort(function (left, right) {
                return compareRowsByMode(left, right, mode);
            });
            rows.forEach(function (row) {
                tbody.appendChild(row);
            });
            updateBulkState();
        }
        function updateBulkState() {
            const checked = getCheckedRows();
            if (deleteBtn) {
                deleteBtn.disabled = checked.length === 0;
                deleteBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i> Supprimer (' + checked.length + ')';
            }
            if (selectedCountEl) {
                selectedCountEl.textContent = checked.length + ' sélectionnée(s)';
            }
            if (planningBtn) {
                planningBtn.disabled = checked.length === 0;
            }
            if (checkAll) {
                const visible = getVisibleRows();
                const checkedVisible = visible.filter(function (row) {
                    const cb = row.querySelector('.cm-prog-check-row');
                    return cb && cb.checked;
                });
                checkAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
            }
        }
        function applySearch() {
            const term = searchInput ? (searchInput.value || '').trim().toLowerCase() : '';
            getRows().forEach(function (row) {
                const text = row.getAttribute('data-search') || '';
                row.style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
            });
            updateBulkState();
        }
        function setDefaultDateTime() {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const mi = String(now.getMinutes()).padStart(2, '0');
            if (dateInput && !dateInput.value) {
                dateInput.value = yyyy + '-' + mm + '-' + dd;
            }
            if (heureInput && !heureInput.value) {
                heureInput.value = hh + ':' + mi;
            }
        }
        function getStudentById(id) {
            if (!id) {
                return null;
            }
            return studentsById[String(id)] || null;
        }
        function ensureSelectOption(select, value, label) {
            if (!select || !value) {
                return;
            }
            const normalizedValue = String(value);
            const exists = Array.from(select.options).some(function (option) {
                return option.value === normalizedValue;
            });
            if (exists) {
                return;
            }
            const option = document.createElement('option');
            option.value = normalizedValue;
            option.textContent = label || normalizedValue;
            select.appendChild(option);
        }
        function updateStudentDerivedFields() {
            const student = getStudentById(etudiantSelect ? etudiantSelect.value : '');
            if (!student) {
                if (themeInput) themeInput.value = '';
                if (directeurInput) directeurInput.value = '';
                if (encadreurInput) encadreurInput.value = '';
                if (maitreInput) maitreInput.value = '';
                if (directeurIdInput) directeurIdInput.value = '';
                if (encadreurIdInput) encadreurIdInput.value = '';
                if (maitreIdInput) maitreIdInput.value = '';
                return;
            }
            if (themeInput && !editIdInput.value) {
                themeInput.value = student.theme_rapport || '';
            }
            if (directeurInput) directeurInput.value = student.directeur_nom || '';
            if (encadreurInput) encadreurInput.value = student.encadreur_nom || '';
            if (maitreInput) maitreInput.value = student.maitre_stage_nom || '';
            if (directeurIdInput) directeurIdInput.value = student.directeur_id || '';
            if (encadreurIdInput) encadreurIdInput.value = student.encadreur_id || '';
            if (maitreIdInput) maitreIdInput.value = student.maitre_stage_id || '';
        }
        function updateJuryConstraints() {
            if (!presidentSelect || !examinateurSelect) {
                return;
            }
            const president = presidentSelect.value;
            const examinateur = examinateurSelect.value;
            Array.from(presidentSelect.options).forEach(function (opt) {
                if (!opt.value) {
                    return;
                }
                opt.disabled = examinateur !== '' && opt.value === examinateur;
            });
            Array.from(examinateurSelect.options).forEach(function (opt) {
                if (!opt.value) {
                    return;
                }
                opt.disabled = president !== '' && opt.value === president;
            });
        }
        function resetForm() {
            if (editIdInput) editIdInput.value = '';
            if (etudiantSelect) etudiantSelect.value = '';
            if (themeInput) themeInput.value = '';
            if (presidentSelect) presidentSelect.value = '';
            if (examinateurSelect) examinateurSelect.value = '';
            if (directeurInput) directeurInput.value = '';
            if (encadreurInput) encadreurInput.value = '';
            if (maitreInput) maitreInput.value = '';
            if (directeurIdInput) directeurIdInput.value = '';
            if (encadreurIdInput) encadreurIdInput.value = '';
            if (maitreIdInput) maitreIdInput.value = '';
            if (salleSelect) salleSelect.value = '';
            if (dateInput) dateInput.value = '';
            if (heureInput) heureInput.value = '';
            setDefaultDateTime();
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Programmer';
            }
            updateStudentDerivedFields();
            updateJuryConstraints();
        }
        function apiCall(action, payload) {
            const formData = new FormData();
            Object.keys(payload || {}).forEach(function (key) {
                formData.append(key, payload[key]);
            });
            const tokenInput = form ? form.querySelector('input[name=\"csrf_token\"]') : null;
            if (tokenInput && tokenInput.value) {
                formData.append('csrf_token', tokenInput.value);
            }
            return fetch('?page=' + encodeURIComponent(currentPage) + '&action=' + action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            }).then(function (response) {
                return response.text().then(function (text) {
                    let result = null;
                    if (text) {
                        try {
                            result = JSON.parse(text);
                        } catch (error) {
                            result = null;
                        }
                    }
                    if (result) {
                        return result;
                    }
                    if (!response.ok) {
                        throw new Error(text || 'Le serveur a retourne une reponse invalide.');
                    }
                    return {
                        success: response.ok,
                        message: text
                    };
                });
            });
        }
        function refreshPage() {
            if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                return;
            }
            window.location.reload();
        }
        function askConfirmation(title, message) {
            if (window.CM && typeof window.CM.confirm === 'function') {
                return window.CM.confirm({
                    title: title,
                    message: message,
                    type: 'danger',
                    confirmText: 'Supprimer',
                });
            }
            return Promise.resolve(window.confirm(message));
        }
        function validatePayload(payload) {
            if (!payload.id_etudiant || !payload.date_soutenance || !payload.heure_soutenance ||
                !payload.id_salle || !payload.theme_soutenance || !payload.president_id || !payload.examinateur_id) {
                setAlert('error', 'Renseignez étudiant, date, heure, salle, theme, president et examinateur.');
                return false;
            }
            if (payload.president_id === payload.examinateur_id) {
                setAlert('error', 'Le president et l examinateur doivent etre differents.');
                return false;
            }
            return true;
        }
        function buildPayload() {
            return {
                id_etudiant: etudiantSelect ? etudiantSelect.value : '',
                date_soutenance: dateInput ? dateInput.value : '',
                heure_soutenance: heureInput ? heureInput.value : '',
                id_salle: salleSelect ? salleSelect.value : '',
                theme_soutenance: themeInput ? (themeInput.value || '').trim() : '',
                president_id: presidentSelect ? presidentSelect.value : '',
                examinateur_id: examinateurSelect ? examinateurSelect.value : '',
                directeur_id: directeurIdInput ? directeurIdInput.value : '',
                encadreur_id: encadreurIdInput ? encadreurIdInput.value : '',
                maitre_stage_id: maitreIdInput ? maitreIdInput.value : ''
            };
        }
        function submitForm() {
            const payload = buildPayload();
            if (!validatePayload(payload)) {
                return;
            }
            const editId = editIdInput ? editIdInput.value : '';
            const action = editId ? 'updateAttribution' : 'createAttribution';
            if (editId) {
                payload.id = editId;
            }
            apiCall(action, payload)
                .then(function (result) {
                    if (!result || !result.success) {
                        setAlert('error', result && result.message ? result.message : 'Enregistrement impossible.');
                        return;
                    }
                    setAlert('success', result.message || 'Programmation enregistree.');
                    refreshPage();
                })
                .catch(function (error) {
                    setAlert('error', error && error.message ? error.message : 'Erreur reseau.');
                });
        }
        function deleteAttribution(id) {
            if (!id) {
                return Promise.resolve();
            }
            return apiCall('deleteAttribution', { id: id });
        }
        function exportVisibleRows() {
            const headers = ['Etudiant', 'Promotion', 'Date soutenance', 'Heure', 'Salle', 'President', 'Dir. memoire', 'Examinateur', 'Encadreur Ped.', 'Maitre stage'];
            const rows = [headers.join(';')];
            getVisibleRows().forEach(function (row) {
                const cells = row.querySelectorAll('.cm-data-table__td');
                if (cells.length < 11) {
                    return;
                }
                const line = [
                    cells[1].innerText.trim().replace(/\s+/g, ' '),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(),
                    cells[4].innerText.trim(),
                    cells[5].innerText.trim(),
                    cells[6].innerText.trim(),
                    cells[7].innerText.trim(),
                    cells[8].innerText.trim(),
                    cells[9].innerText.trim(),
                    cells[10].innerText.trim()
                ].map(function (value) {
                    return '\"' + value.replace(/\"/g, '\"\"') + '\"';
                });
                rows.push(line.join(';'));
            });
            const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'programmation_soutenances.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }
        function formatDateLabel(dateIso) {
            if (!dateIso) {
                return '-';
            }
            const [year, month, day] = String(dateIso).split('-');
            if (!year || !month || !day) {
                return dateIso;
            }
            return day + '/' + month + '/' + year;
        }
        function formatHeureLabel(rawHeure) {
            return normalizedTimeValue(rawHeure || '');
        }
        function buildSessionLabel(dateIso) {
            const date = new Date(dateIso + 'T00:00:00');
            const month = date.toLocaleDateString('fr-FR', { month: 'long' }).toUpperCase();
            const year = date.toLocaleDateString('fr-FR', { year: 'numeric' });
            return month + ' ' + year;
        }
        function collectSelectedPlanningItems() {
            return getCheckedRows().map(function (row) {
                return {
                    id: row.getAttribute('data-id') || '',
                    date: row.getAttribute('data-date') || '',
                    heure: formatHeureLabel(row.getAttribute('data-heure') || ''),
                    salleId: row.getAttribute('data-salle-id') || '',
                    salleName: row.getAttribute('data-salle-name') || '-',
                    etudiant: row.getAttribute('data-etudiant-name') || '-',
                };
            }).filter(function (item) {
                return item.id !== '' && item.date !== '';
            });
        }
        function detectSalleConflicts(items) {
            const slots = {};
            items.forEach(function (item) {
                const key = item.date + '|' + item.heure + '|' + String(item.salleName || '').toLowerCase();
                if (!slots[key]) {
                    slots[key] = {
                        date: item.date,
                        heure: item.heure,
                        salle: item.salleName || '-',
                        count: 0
                    };
                }
                slots[key].count += 1;
            });
            return Object.values(slots).filter(function (slot) {
                return slot.count > 1;
            });
        }
        function buildPlanningSummary(items) {
            const grouped = {};
            const salles = {};
            const sessions = {};
            items.forEach(function (item) {
                if (!grouped[item.date]) {
                    grouped[item.date] = [];
                }
                grouped[item.date].push(item);
                salles[item.salleName || '-'] = true;
                sessions[buildSessionLabel(item.date)] = true;
            });
            Object.keys(grouped).forEach(function (date) {
                grouped[date].sort(function (left, right) {
                    return left.heure.localeCompare(right.heure);
                });
            });
            const orderedDates = Object.keys(grouped).sort();
            return {
                total: items.length,
                grouped: grouped,
                orderedDates: orderedDates,
                salles: Object.keys(salles),
                sessions: Object.keys(sessions),
                conflicts: detectSalleConflicts(items),
            };
        }
        function removePlanningModal() {
            document.getElementById('cmProgPlanningOverlay')?.remove();
        }
        function showPlanningProgress(message) {
            const overlay = document.createElement('div');
            overlay.id = 'cmProgPlanningProgress';
            overlay.className = 'cm-prog-planning-overlay';
            overlay.innerHTML = '<div class="cm-prog-planning-modal cm-text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>' + escHtml(message) + '</p></div>';
            document.body.appendChild(overlay);
        }
        function hidePlanningProgress() {
            document.getElementById('cmProgPlanningProgress')?.remove();
        }
        function showPlanningSuccess(reference, downloadUrl) {
            const overlay = document.createElement('div');
            overlay.id = 'cmProgPlanningSuccess';
            overlay.className = 'cm-prog-planning-overlay';
            overlay.innerHTML = '<div class="cm-prog-planning-modal cm-prog-planning-modal--narrow">'
                + '<h3><i class="fas fa-check-circle"></i> Planning généré</h3>'
                + '<p>Référence : <strong>' + escHtml(reference) + '</strong></p>'
                + '<div class="cm-prog-planning-actions">'
                + '<a class="cm-btn is-primary is-sm" href="' + escHtml(downloadUrl) + '" target="_blank" rel="noopener"><i class="fas fa-download"></i> Télécharger</a>'
                + '<button type="button" class="cm-btn is-light is-sm" id="cmProgPlanningSuccessClose">Fermer</button>'
                + '</div></div>';
            document.body.appendChild(overlay);
            document.getElementById('cmProgPlanningSuccessClose')?.addEventListener('click', function () {
                overlay.remove();
            });
        }
        async function generatePlanningFromSelection(selectedIds) {
            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                setAlert('error', 'Aucune soutenance sélectionnée.');
                return;
            }
            showPlanningProgress('Génération du planning PDF en cours…');
            try {
                const tokenInput = form ? form.querySelector('input[name="csrf_token"]') : null;
                const payload = {
                    selected_ids: selectedIds
                };
                if (tokenInput && tokenInput.value) {
                    payload.csrf_token = tokenInput.value;
                }
                const response = await fetch('?page=' + encodeURIComponent(currentPage) + '&action=generatePlanningPdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const json = await response.json();
                hidePlanningProgress();
                if (json && json.success) {
                    showPlanningSuccess(json.reference || '', json.download_url || '#');
                    setAlert('success', 'Planning PDF généré avec succès.');
                    return;
                }
                setAlert('error', (json && json.error) ? json.error : 'Erreur lors de la génération du planning.');
            } catch (error) {
                hidePlanningProgress();
                setAlert('error', 'Erreur réseau lors de la génération du planning.');
            }
        }
        function openPlanningSummaryModal() {
            const selectedItems = collectSelectedPlanningItems();
            if (selectedItems.length === 0) {
                setAlert('error', 'Sélectionnez au moins une soutenance avant de générer le planning.');
                return;
            }
            const summary = buildPlanningSummary(selectedItems);
            const overlay = document.createElement('div');
            overlay.id = 'cmProgPlanningOverlay';
            overlay.className = 'cm-prog-planning-overlay';

            let bodyHtml = '<div class="cm-prog-planning-summary-count">Soutenances sélectionnées : <strong>' + summary.total + '</strong></div>';
            summary.orderedDates.forEach(function (date) {
                const entries = summary.grouped[date] || [];
                bodyHtml += '<div class="cm-prog-planning-day">';
                bodyHtml += '<h4>' + escHtml(formatDateLabel(date)) + ' — ' + entries.length + ' soutenance(s)</h4>';
                bodyHtml += '<ul>';
                entries.forEach(function (entry) {
                    bodyHtml += '<li>' + escHtml(entry.heure) + ' — ' + escHtml(entry.etudiant) + ' (' + escHtml(entry.salleName || '-') + ')</li>';
                });
                bodyHtml += '</ul></div>';
            });

            if (summary.conflicts.length > 0) {
                bodyHtml += '<div class="cm-alert is-warning cm-mt-2"><div class="cm-alert__content"><span class="cm-alert__message"><strong><i class="fas fa-exclamation-triangle"></i> Conflits de salle détectés :</strong><br>';
                summary.conflicts.forEach(function (conflict) {
                    bodyHtml += '• ' + escHtml(formatDateLabel(conflict.date)) + ' à ' + escHtml(conflict.heure) + ' — ' + escHtml(conflict.salle) + ' (' + conflict.count + ' soutenances)<br>';
                });
                bodyHtml += '</span></div></div>';
            }

            bodyHtml += '<div class="cm-prog-planning-summary-meta">';
            bodyHtml += '<p>Le PDF contiendra <strong>' + summary.orderedDates.length + '</strong> page(s) — une par jour.</p>';
            bodyHtml += '<p>Sessions : ' + escHtml(summary.sessions.join(', ')) + '</p>';
            bodyHtml += '<p>Salles utilisées : ' + escHtml(summary.salles.join(', ')) + '</p>';
            bodyHtml += '</div>';

            overlay.innerHTML = '<div class="cm-prog-planning-modal">'
                + '<h3>Générer le Planning PDF</h3>'
                + '<div class="cm-prog-planning-body">' + bodyHtml + '</div>'
                + '<div class="cm-prog-planning-actions">'
                + '<button type="button" class="cm-btn is-light is-sm" id="cmProgPlanningCancel">Annuler</button>'
                + '<button type="button" class="cm-btn is-primary is-sm" id="cmProgPlanningGenerate">Générer le PDF</button>'
                + '</div></div>';
            document.body.appendChild(overlay);

            document.getElementById('cmProgPlanningCancel')?.addEventListener('click', removePlanningModal);
            document.getElementById('cmProgPlanningGenerate')?.addEventListener('click', function () {
                removePlanningModal();
                generatePlanningFromSelection(selectedItems.map(function (item) {
                    return item.id;
                }));
            });
        }
        if (etudiantSelect) {
            etudiantSelect.addEventListener('change', updateStudentDerivedFields);
        }
        if (presidentSelect) {
            presidentSelect.addEventListener('change', updateJuryConstraints);
        }
        if (examinateurSelect) {
            examinateurSelect.addEventListener('change', updateJuryConstraints);
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', resetForm);
        }
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                submitForm();
            });
        }
        document.querySelectorAll('.cm-prog-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const row = button.closest('.cm-data-table__row');
                if (!row) {
                    return;
                }
                if (row.getAttribute('data-is-writable') !== '1') {
                    setAlert('error', 'Modification impossible: seule l annee active ' + (row.getAttribute('data-writable-year-label') || '') + ' accepte des ecritures.');
                    return;
                }
                const etudiantId = row.getAttribute('data-id-etudiant') || '';
                const etudiantName = row.getAttribute('data-etudiant-name') || '';
                const etudiantMatricule = row.getAttribute('data-etudiant-matricule') || etudiantId;
                const promotion = row.getAttribute('data-promotion') || '';
                const optionLabel = etudiantName !== ''
                    ? etudiantName + ' (' + etudiantMatricule + ')' + (promotion !== '' ? ' - ' + promotion : '')
                    : etudiantId;
                ensureSelectOption(etudiantSelect, etudiantId, optionLabel);

                if (editIdInput) editIdInput.value = row.getAttribute('data-id') || '';
                if (etudiantSelect) etudiantSelect.value = etudiantId;
                if (themeInput) themeInput.value = row.getAttribute('data-theme') || '';
                if (dateInput) dateInput.value = normalizeDateValue(row.getAttribute('data-date') || '');
                if (heureInput) heureInput.value = (row.getAttribute('data-heure') || '').slice(0, 5);
                if (salleSelect) salleSelect.value = row.getAttribute('data-salle-id') || '';
                if (presidentSelect) presidentSelect.value = row.getAttribute('data-president-id') || '';
                if (examinateurSelect) examinateurSelect.value = row.getAttribute('data-examinateur-id') || '';
                if (directeurInput) directeurInput.value = row.getAttribute('data-directeur-id') || '';
                if (encadreurInput) encadreurInput.value = row.getAttribute('data-encadreur-id') || '';
                if (maitreInput) maitreInput.value = row.getAttribute('data-maitre-id') || '';
                if (directeurInput) directeurInput.value = row.getAttribute('data-directeur-name') || '';
                if (encadreurInput) encadreurInput.value = row.getAttribute('data-encadreur-name') || '';
                if (maitreInput) maitreInput.value = row.getAttribute('data-maitre-name') || '';
                if (directeurIdInput) directeurIdInput.value = row.getAttribute('data-directeur-id') || '';
                if (encadreurIdInput) encadreurIdInput.value = row.getAttribute('data-encadreur-id') || '';
                if (maitreIdInput) maitreIdInput.value = row.getAttribute('data-maitre-id') || '';
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-pen" aria-hidden="true"></i> Modifier';
                }
                updateJuryConstraints();
                setAlert('success', 'Mode modification active.');
            });
        });
        document.querySelectorAll('.cm-prog-delete').forEach(function (button) {
            button.addEventListener('click', async function () {
                const row = button.closest('.cm-data-table__row');
                if (row && row.getAttribute('data-is-writable') !== '1') {
                    setAlert('error', 'Suppression impossible: seule l annee active ' + (row.getAttribute('data-writable-year-label') || '') + ' accepte des ecritures.');
                    return;
                }
                const id = button.getAttribute('data-id') || '';
                if (!id) {
                    return;
                }
                const confirmed = await askConfirmation('Suppression', 'Supprimer cette programmation ?');
                if (!confirmed) {
                    return;
                }
                deleteAttribution(id)
                    .then(function (result) {
                        if (!result || !result.success) {
                            setAlert('error', result && result.message ? result.message : 'Suppression impossible.');
                            return;
                        }
                        setAlert('success', result.message || 'Programmation supprimee.');
                        refreshPage();
                    })
                    .catch(function (error) {
                        setAlert('error', error && error.message ? error.message : 'Erreur reseau.');
                    });
            });
        });
        if (searchInput) {
            searchInput.addEventListener('input', applySearch);
        }
        if (limitSelect) {
            limitSelect.addEventListener('change', function () {
                const params = new URLSearchParams(window.location.search);
                params.set('page', currentPage);
                params.set('limit_prog', limitSelect.value || '10');
                params.set('page_prog', '1');
                window.location.search = params.toString();
            });
        }
        if (sortModeSelect) {
            sortModeSelect.addEventListener('change', function () {
                applySortMode();
            });
        }
        if (planningBtn) {
            planningBtn.addEventListener('click', function () {
                openPlanningSummaryModal();
            });
        }
        if (exportBtn) {
            exportBtn.addEventListener('click', exportVisibleRows);
        }
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }
        document.addEventListener('change', function (event) {
            if (event.target && event.target.classList.contains('cm-prog-check-row')) {
                updateBulkState();
            }
        });
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-prog-check-row');
                    if (cb) {
                        cb.checked = checkAll.checked;
                    }
                });
                updateBulkState();
            });
        }
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-prog-check-row');
                    if (cb) {
                        cb.checked = true;
                    }
                });
                updateBulkState();
            });
        }
        if (deselectBtn) {
            deselectBtn.addEventListener('click', function () {
                getRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-prog-check-row');
                    if (cb) {
                        cb.checked = false;
                    }
                });
                updateBulkState();
            });
        }
        if (deleteBtn) {
            deleteBtn.addEventListener('click', async function () {
                const selectedRows = getCheckedRows();
                const ids = selectedRows.map(function (row) {
                    const cb = row.querySelector('.cm-prog-check-row');
                    return cb ? cb.value : '';
                }).filter(Boolean);
                if (ids.length === 0) {
                    return;
                }
                const restrictedRows = selectedRows.filter(function (row) {
                    return row.getAttribute('data-is-writable') !== '1';
                });
                if (restrictedRows.length > 0) {
                    const writableYearLabel = restrictedRows[0].getAttribute('data-writable-year-label') || '';
                    setAlert('error', 'Suppression multiple impossible: retirez les lignes hors annee active ' + writableYearLabel + '.');
                    return;
                }
                const confirmed = await askConfirmation('Suppression multiple', 'Supprimer ' + ids.length + ' programmation(s) ?');
                if (!confirmed) {
                    return;
                }
                Promise.all(ids.map(deleteAttribution))
                    .then(function (results) {
                        const failed = results.filter(function (item) {
                            return !item || !item.success;
                        });
                        if (failed.length > 0) {
                            setAlert('error', 'Certaines suppressions ont echoue.');
                        } else {
                            setAlert('success', 'Suppressions effectuees.');
                        }
                        refreshPage();
                    })
                    .catch(function (error) {
                        setAlert('error', error && error.message ? error.message : 'Erreur reseau.');
                    });
            });
        }
        setDefaultDateTime();
        updateStudentDerivedFields();
        updateJuryConstraints();
        applySortMode();
        applySearch();
    })();
</script>
