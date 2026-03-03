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
$rowsToShow = array_slice($attributions, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=' . urlencode($currentPageSlug) . '&limit_prog=' . $perPage;
$selectedYearLabel = \AcademicYear::getSelectedLabelFromSession();
$activeYearLabel = \AcademicYear::getActiveLabelFromSession();
$writeYearLabel = \AcademicYear::getWritableLabelFromSession();
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writeAllowed = \AcademicYear::isWriteAllowedFromSession();
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
        <div class="">
            <div class="">
            </div>
            <form id="cmProgForm" autocomplete="off">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" id="cmProgEditId" value="">
                <input type="hidden" id="cmProgDirecteurId" value="">
                <input type="hidden" id="cmProgEncadreurId" value="">
                <input type="hidden" id="cmProgMaitreId" value="">

                <div class="cm-grid-4">
                    <?php
                    cm_component('form/select', [
                        'name' => 'cm_prog_etudiant',
                        'id' => 'cmProgEtudiant',
                        'label' => 'Etudiant',
                        'required' => true,
                        'options' => $studentOptions,
                        'control_class' => 'cm-field-lg',
                    ]);
                    cm_component('form/input-date', [
                        'name' => 'cm_prog_date',
                        'id' => 'cmProgDate',
                        'label' => 'Date soutenance',
                        'required' => true,
                        'value' => date('Y-m-d'),
                        'control_class' => 'cm-field-sm',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_heure',
                        'id' => 'cmProgHeure',
                        'label' => 'Heure',
                        'required' => true,
                        'value' => date('H:i'),
                        'attrs' => ['placeholder' => 'HH:MM'],
                        'control_class' => 'cm-field-sm',
                    ]);
                    cm_component('form/select', [
                        'name' => 'cm_prog_salle',
                        'id' => 'cmProgSalle',
                        'label' => 'Salle',
                        'required' => true,
                        'options' => $salleOptions,
                        'control_class' => 'cm-field-md',
                    ]);
                    ?>
                </div>
                <div class="cm-grid-2">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_theme',
                        'id' => 'cmProgTheme',
                        'label' => 'Theme',
                        'required' => true,
                        'placeholder' => 'Theme de soutenance',
                        'control_class' => 'cm-field-xl',
                    ]);
                    ?>
                </div>
                <div class="">
                </div>
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/select', [
                        'name' => 'cm_prog_president',
                        'id' => 'cmProgPresident',
                        'label' => 'President',
                        'required' => true,
                        'options' => $presidentOptions,
                        'control_class' => 'cm-field-lg',
                    ]);
                    cm_component('form/select', [
                        'name' => 'cm_prog_examinateur',
                        'id' => 'cmProgExaminateur',
                        'label' => 'Examinateur',
                        'required' => true,
                        'options' => $enseignantOptions,
                        'control_class' => 'cm-field-lg',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_directeur',
                        'id' => 'cmProgDirecteur',
                        'label' => 'Dir. mémoire',
                        'readonly' => true,
                        'control_class' => 'cm-field-lg',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_encadreur',
                        'id' => 'cmProgEncadreur',
                        'label' => 'Encadreur P.',
                        'readonly' => true,
                        'control_class' => 'cm-field-lg',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_maitre',
                        'id' => 'cmProgMaitreStage',
                        'label' => 'Maître stage',
                        'readonly' => true,
                        'control_class' => 'cm-field-lg',
                    ]);
                    ?>
                </div>

                <div class="cm-form-buttons">
                    <button class="cm-btn is-light" type="button" id="cmProgResetBtn">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                        Réinitialiser
                    </button>
                    <?php if ((function_exists('canCreate') && canCreate()) || (function_exists('canEdit') && canEdit())): ?>
                        <button class="cm-btn is-success" type="submit" id="cmProgSubmitBtn">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            Programmer
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="cm-barre-intermediaire">
            <?php cm_toolbar([
                'screen' => 'programmation_soutenance',
                'id_prefix' => 'prog_sout',
                'search_value' => $_GET['search'] ?? '',
                'limit' => $perPage,
                'can_delete' => canDelete(),
                'can_view' => canView(),
            ]); ?>
        </div>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmProgTable">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th cm-data-table__th--check">
                            <input type="checkbox" id="cmProgCheckAll" aria-label="Tout sélectionner">
                        </th>
                        <th class="cm-data-table__th">N</th>
                        <th class="cm-data-table__th">Etudiant</th>
                        <th class="cm-data-table__th">Promotion</th>
                        <th class="cm-data-table__th">Date S.</th>
                        <th class="cm-data-table__th">Heure</th>
                        <th class="cm-data-table__th">Salle</th>
                        <th class="cm-data-table__th">Thème</th>
                        <th class="cm-data-table__th">Président</th>
                        <th class="cm-data-table__th">Directeur</th>
                        <th class="cm-data-table__th">Examinateur</th>
                        <th class="cm-data-table__th">Encadreur</th>
                        <th class="cm-data-table__th">Maître stage</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="cmProgTableBody">
                    <?php if (empty($rowsToShow)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 14,
                            'title' => '',
                            'message' => 'Aucune soutenance programmee pour le moment.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($rowsToShow as $index => $row): ?>
                            <?php
                            $idAttribution = (int) ($row['id_attribution'] ?? 0);
                            $idEtudiant = (string) ($row['id_etudiant'] ?? '');
                            $nomEtudiant = (string) ($row['nom_etudiant'] ?? '');
                            $matricule = (string) ($row['matricule_etudiant'] ?? '');
                            $theme = (string) ($row['theme_soutenance'] ?? '');
                            $dateRaw = (string) ($row['date_soutenance'] ?? '');
                            $heureRaw = (string) ($row['heure_soutenance'] ?? '');
                            $dateDisplay = $dateRaw !== '' ? date('d/m/Y', strtotime($dateRaw)) : '-';
                            $heureDisplay = $heureRaw !== '' ? date('H:i', strtotime($heureRaw)) : '-';
                            $salleId = (string) ($row['id_salle'] ?? '');
                            $salleNom = trim((string) ($row['nom_salle'] ?? ''));
                            $promotion = trim((string) ($row['promotion_etu'] ?? ''));
                            $searchText = strtolower(
                                $nomEtudiant . ' ' . $matricule . ' ' . $promotion . ' ' . $theme . ' ' . $dateDisplay . ' ' . $heureDisplay . ' ' . $salleNom
                            );
                            ?>
                            <tr class="cm-data-table__row"
                                data-id="<?php echo $idAttribution; ?>"
                                data-id-etudiant="<?php echo htmlspecialchars($idEtudiant, ENT_QUOTES, 'UTF-8'); ?>"
                                data-theme="<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>"
                                data-date="<?php echo htmlspecialchars($dateRaw, ENT_QUOTES, 'UTF-8'); ?>"
                                data-heure="<?php echo htmlspecialchars($heureRaw, ENT_QUOTES, 'UTF-8'); ?>"
                                data-salle-id="<?php echo htmlspecialchars($salleId, ENT_QUOTES, 'UTF-8'); ?>"
                                data-president-id="<?php echo htmlspecialchars((string) ($row['president_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-examinateur-id="<?php echo htmlspecialchars((string) ($row['examinateur_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="cm-data-table__td cm-data-table__td--check">
                                    <input type="checkbox" class="cm-prog-check-row" value="<?php echo $idAttribution; ?>" aria-label="Sélectionner ligne <?php echo $idAttribution; ?>">
                                </td>
                                <td class="cm-data-table__td"><?php echo (int) ($pagination['offset'] ?? 0) + $index + 1; ?></td>
                                <td class="cm-data-table__td">
                                    <?php echo htmlspecialchars($nomEtudiant, ENT_QUOTES, 'UTF-8'); ?><br>
                                    <small><?php echo htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($promotion !== '' ? $promotion : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($heureDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($salleNom !== '' ? $salleNom : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($theme !== '' ? $theme : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['president_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['directeur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['examinateur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['encadreur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['maitre_stage_nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions">
                                        <?php if (function_exists('canEdit') ? canEdit() : true): ?>
                                            <button type="button"
                                                    class="cm-btn-action is-edit cm-prog-edit"
                                                    data-id="<?php echo $idAttribution; ?>"
                                                    title="Modifier">
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (function_exists('canDelete') ? canDelete() : true): ?>
                                            <button type="button"
                                                    class="cm-btn-action is-delete cm-prog-delete"
                                                    data-id="<?php echo $idAttribution; ?>"
                                                    title="Supprimer">
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
    function setAlert(type, message) {
        if (!alertBox) {
            return;
        }
        const cssType = type === 'success' ? 'success' : 'danger';
        alertBox.innerHTML = '<div class="cm-alert is-' + cssType + '"><div class="cm-alert__content"><span class="cm-alert__message">' +
            String(message || '').replace(/[<>&]/g, '') +
            '</span></div></div>';
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
    function updateBulkState() {
        const checked = getCheckedRows();
        if (deleteBtn) {
            deleteBtn.disabled = checked.length === 0;
            deleteBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i> Supprimer (' + checked.length + ')';
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
    function updateStudentDerivedFields() {
        const student = getStudentById(etudiantSelect ? etudiantSelect.value : '');
        if (!student) {
            if (themeInput && !editIdInput.value) themeInput.value = '';
            if (directeurInput) directeurInput.value = '';
            if (encadreurInput) encadreurInput.value = '';
            if (maitreInput) maitreInput.value = '';
            if (directeurIdInput) directeurIdInput.value = '';
            if (encadreurIdInput) encadreurIdInput.value = '';
            if (maitreIdInput) maitreIdInput.value = '';
            return;
        }
        if (themeInput && !editIdInput.value && !themeInput.value) {
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
            return response.json();
        });
    }
    function refreshPage() {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
            return;
        }
        window.location.reload();
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
            .catch(function () {
                setAlert('error', 'Erreur reseau.');
            });
    }
    function deleteAttribution(id) {
        if (!id) {
            return Promise.resolve();
        }
        return apiCall('deleteAttribution', { id: id });
    }
    function exportVisibleRows() {
        const headers = ['N', 'Etudiant', 'Promotion', 'Date soutenance', 'Heure', 'Salle', 'Thème', 'President', 'Dir.M', 'Exam.', 'Enc.', 'MS'];
        const rows = [headers.join(';')];
        getVisibleRows().forEach(function (row) {
            const cells = row.querySelectorAll('.cm-data-table__td');
            if (cells.length < 13) {
                return;
            }
            const line = [
                cells[1].innerText.trim(),
                cells[2].innerText.trim().replace(/\s+/g, ' '),
                cells[3].innerText.trim(),
                cells[4].innerText.trim(),
                cells[5].innerText.trim(),
                cells[6].innerText.trim(),
                cells[7].innerText.trim(),
                cells[8].innerText.trim(),
                cells[9].innerText.trim(),
                cells[10].innerText.trim(),
                cells[11].innerText.trim(),
                cells[12].innerText.trim()
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
            if (editIdInput) editIdInput.value = row.getAttribute('data-id') || '';
            if (etudiantSelect) etudiantSelect.value = row.getAttribute('data-id-etudiant') || '';
            if (themeInput) themeInput.value = row.getAttribute('data-theme') || '';
            if (dateInput) dateInput.value = row.getAttribute('data-date') || '';
            if (heureInput) heureInput.value = (row.getAttribute('data-heure') || '').slice(0, 5);
            if (salleSelect) salleSelect.value = row.getAttribute('data-salle-id') || '';
            if (presidentSelect) presidentSelect.value = row.getAttribute('data-president-id') || '';
            if (examinateurSelect) examinateurSelect.value = row.getAttribute('data-examinateur-id') || '';
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Modifier';
            }
            updateStudentDerivedFields();
            updateJuryConstraints();
            setAlert('success', 'Mode modification active.');
        });
    });
    document.querySelectorAll('.cm-prog-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            const id = button.getAttribute('data-id') || '';
            if (!id) {
                return;
            }
            if (!window.confirm('Supprimer cette programmation ?')) {
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
                .catch(function () {
                    setAlert('error', 'Erreur reseau.');
                });
        });
    });
    if (searchInput) {
        searchInput.addEventListener('input', applySearch);
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
        deleteBtn.addEventListener('click', function () {
            const ids = getCheckedRows().map(function (row) {
                const cb = row.querySelector('.cm-prog-check-row');
                return cb ? cb.value : '';
            }).filter(Boolean);
            if (ids.length === 0) {
                return;
            }
            if (!window.confirm('Supprimer ' + ids.length + ' programmation(s) ?')) {
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
                .catch(function () {
                    setAlert('error', 'Erreur reseau.');
                });
        });
    }
    setDefaultDateTime();
    updateStudentDerivedFields();
    updateJuryConstraints();
    applySearch();
})();
</script>
