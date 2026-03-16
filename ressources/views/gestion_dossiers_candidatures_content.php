<?php
require_once __DIR__ . '/../../app/models/Scolarite.php';
require_once __DIR__ . '/../../app/models/Note.php';
$rapportsVerifies = is_array($GLOBALS['rapports_verifies'] ?? null) ? $GLOBALS['rapports_verifies'] : [];
$statistiques = is_array($GLOBALS['statistiques'] ?? null) ? $GLOBALS['statistiques'] : ['total' => 0, 'approuves' => 0, 'desapprouves' => 0];
$scolariteModel = new Scolarite(Database::getConnection());
$noteModel = new Note(Database::getConnection());
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writableYearLabel = \AcademicYear::getWritableLabelFromSession();
$academicYearLabels = [];
foreach (\AcademicYear::fetchAll(Database::getConnection()) as $academicYear) {
    $academicYearLabels[(int) ($academicYear['id'] ?? 0)] = (string) ($academicYear['label'] ?? '');
}
$niveauxMap = [];
foreach ($scolariteModel->getNiveauxEtudes() as $niveau) {
    $niveauxMap[(int) ($niveau['id_niv_etude'] ?? 0)] = (string) ($niveau['lib_niv_etude'] ?? '');
}
$rows = [];
foreach ($rapportsVerifies as $rapport) {
    $numEtu = (string) ($rapport['num_etu'] ?? '');
    $rowYearId = !empty($rapport['id_annee_acad']) ? (int) $rapport['id_annee_acad'] : null;
    $promotionLabel = trim((string) ($rapport['promotion_etu'] ?? ''));
    if ($promotionLabel === '' && $rowYearId !== null) {
        $promotionLabel = $academicYearLabels[$rowYearId] ?? '';
    }
    $paiement = null;
    $niveauLabel = '-';
    $montantVerse = 0.0;
    $resteAPayer = 0.0;
    $paymentStatus = 'Impayé';
    $paymentBadge = 'danger';
    if ($numEtu !== '' && $rowYearId !== null) {
        $paiement = $scolariteModel->getInfosPaiementEtudiant($numEtu, $rowYearId);
        if (is_array($paiement)) {
            $niveauLabel = $niveauxMap[(int) ($paiement['id_niveau'] ?? 0)] ?? '-';
        }
    }
    if (is_array($paiement)) {
        $montantVerse = (float) ($paiement['montant_paye'] ?? 0);
        $resteAPayer = (float) ($paiement['reste_a_payer'] ?? 0);
        if ($resteAPayer <= 0 && $montantVerse > 0) {
            $paymentStatus = 'Soldé';
            $paymentBadge = 'success';
        } elseif ($montantVerse > 0) {
            $paymentStatus = 'Partiel';
            $paymentBadge = 'warning';
        }
    }
    $latestNote = null;
    if ($numEtu !== '' && $rowYearId !== null) {
        $latestNote = $noteModel->getByStudentAndYear($numEtu, $rowYearId);
    }
    if ($latestNote === null && $numEtu !== '') {
        $latestNote = $noteModel->getLatestNote($numEtu);
    }
    $m1 = $latestNote ? (float) ($latestNote->moyenne_M1 ?? 0) : null;
    $m2 = $latestNote ? (float) ($latestNote->moyenne_M2 ?? 0) : null;
    $decisionRaw = strtolower((string) ($rapport['statut_approbation'] ?? ''));
    $candStatus = 'En attente';
    $candBadge = 'warning';
    if ($decisionRaw === 'approuve') {
        $candStatus = 'Validée';
        $candBadge = 'success';
    } elseif ($decisionRaw === 'desapprouve' || $decisionRaw === 'rejete' || $decisionRaw === 'rejetee') {
        $candStatus = 'Rejetée';
        $candBadge = 'danger';
    }
    $rows[] = [
        'id_rapport' => (int) ($rapport['id_rapport'] ?? 0),
        'num_etu' => $numEtu,
        'nom_complet' => trim((string) ($rapport['nom_etu'] ?? '') . ' ' . (string) ($rapport['prenom_etu'] ?? '')),
        'promotion' => $promotionLabel !== '' ? $promotionLabel : '-',
        'niveau' => $niveauLabel,
        'm1' => $m1,
        'm2' => $m2,
        'montant_verse' => $montantVerse,
        'reste_a_payer' => $resteAPayer,
        'payment_status' => $paymentStatus,
        'payment_badge' => $paymentBadge,
        'date_candidature' => (string) ($rapport['date_depot'] ?? ''),
        'cand_status' => $candStatus,
        'cand_badge' => $candBadge,
        'admin' => trim((string) ($rapport['nom_pers_admin'] ?? '') . ' ' . (string) ($rapport['prenom_pers_admin'] ?? '')),
        'date_traitement' => (string) ($rapport['date_approbation'] ?? ''),
        'commentaire' => (string) ($rapport['commentaire'] ?? ''),
        'title' => (string) ($rapport['titre_rapport'] ?? ''),
    ];
}
$allowedLimits = [2, 5, 10, 25, 50, 100];
$perPage = max(2, (int) ($_GET['limit_candidatures'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($rows), $perPage, $currentPage)
    : [
        'total' => count($rows),
        'per_page' => $perPage,
        'current' => $currentPage,
        'last' => max(1, (int) ceil(max(1, count($rows)) / $perPage)),
        'offset' => max(0, ($currentPage - 1) * $perPage),
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < max(1, (int) ceil(max(1, count($rows)) / $perPage)),
        'pages' => [$currentPage],
    ];
$rowsPage = array_slice($rows, (int) ($pagination['offset'] ?? 0), $perPage);
$paginationBaseUrl = '?page=gestion_dossiers_candidatures&limit_candidatures=' . $perPage;
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => '',
        'subtitle' => 'Traitement inline des dossiers (sans modal).',
        'annee' => trim((string) ($_SESSION['global_annee_selected'] ?? '')),
        'icon' => 'fa-folder-open',
    ]);
    if ($allYearsSelected) {
        cm_component('ui/alert-box', [
            'type' => 'info',
            'message' => "Affichage global sur toutes les années. Les traitements restent limités à l'année active {$writableYearLabel}.",
        ]);
    }
    ?>
    <div class="cm-grid-3 cm-mb-md">
        <?php
        cm_component('dashboard/stat-widget', [
            'value' => (string) ((int) ($statistiques['total'] ?? 0)),
            'label' => 'Total verifies',
            'icon' => 'fa-list-check',
            'color' => 'info',
        ]);
        cm_component('dashboard/stat-widget', [
            'value' => (string) ((int) ($statistiques['approuves'] ?? 0)),
            'label' => 'Validées',
            'icon' => 'fa-circle-check',
            'color' => 'success',
        ]);
        cm_component('dashboard/stat-widget', [
            'value' => (string) ((int) ($statistiques['desapprouves'] ?? 0)),
            'label' => 'Rejetées',
            'icon' => 'fa-circle-xmark',
            'color' => 'warning',
        ]);
        ?>
    </div>
    <div class="cm-crud-wrapper">
    <div class="cm-pole-superieur">
        <div class="">
        </div>
        <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmTraitementForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form id="cmTraitementForm" onsubmit="return false;">
            <input type="hidden" id="cmSelectedRapportId" value="">
            <input type="hidden" id="cmSelectedRapportUrl" value="">
            <div class="cm-grid-4">
                <?php
                cm_component('form/input-text', [
                    'name' => 'traitement_etudiant',
                    'id' => 'cmTraitementEtudiant',
                    'label' => 'Etudiant',
                    'readonly' => true,
                ]);
                cm_component('form/input-date', [
                    'name' => 'traitement_date',
                    'id' => 'cmTraitementDate',
                    'label' => 'Date candidature',
                    'readonly' => true,
                ]);
                cm_component('form/select', [
                    'name' => 'traitement_statut',
                    'id' => 'cmTraitementStatut',
                    'label' => 'Statut',
                    'required' => true,
                    'options' => [
                        'Validée' => 'Validée',
                        'Rejetée' => 'Rejetée',
                    ],
                ]);
                cm_component('form/input-text', [
                    'name' => 'traitement_info',
                    'id' => 'cmTraitementInfo',
                    'label' => 'Info dossier',
                    'readonly' => true,
                ]);
                ?>
            </div>
            <?php
            cm_component('form/textarea', [
                'name' => 'traitement_commentaire',
                'id' => 'cmTraitementCommentaire',
                'label' => 'Commentaire admin',
                'rows' => 3,
                'maxlength' => 500,
            ]);
            ?>
            <div class="cm-form-buttons">
                <?php
                cm_component('crud/form-actions', [
                    'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']],
                    'actions' => [
                        ['label' => 'Réinitialiser', 'type' => 'button', 'class' => 'cm-btn is-secondary is-sm', 'attrs' => ['id' => 'cmResetTraitement']],
                        ['label' => 'Appliquer le traitement', 'type' => 'button', 'class' => 'cm-btn is-primary is-sm', 'attrs' => ['id' => 'cmApplyTraitement']],
                    ],
                ]);
                ?>
                <a href="#" id="cmOpenRapportFromForm" class="cm-btn is-info is-sm cm-hidden" target="_blank">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                    Consulter le rapport
                </a>
            </div>
        </form>
    </div>
    <?php cm_toolbar([
        'screen' => 'gestion_dossiers_candidatures',
        'id_prefix' => 'cmCandidatures',
        'search_value' => $_GET['search'] ?? '',
        'limit' => $perPage,
        'limit_options' => $allowedLimits,
        'can_delete' => canDelete(),
        'can_view' => canView(),
    ]); ?>
    <div class="cm-pole-inferieur">
        <div class="cm-table-wrapper">
            <table class="cm-data-table" id="cmCandidaturesTable">
                <thead>
                <tr>
                    <th class="cm-data-table__th is-checkbox">
                        <input type="checkbox" id="cmCheckAllCandidatures" class="cm-checkbox" aria-label="Sélectionner toutes les lignes">
                    </th>
                    <th class="cm-data-table__th">N° Carte</th>
                    <th class="cm-data-table__th">Nom &amp; Prénom</th>
                    <th class="cm-data-table__th">Promotion</th>
                    <th class="cm-data-table__th">Moy. M1</th>
                    <th class="cm-data-table__th">Moy. M2</th>
                    <th class="cm-data-table__th">Montant versé</th>
                    <th class="cm-data-table__th">Reste</th>
                    <th class="cm-data-table__th">Statut paiement</th>
                    <th class="cm-data-table__th">Actions</th>
                </tr>
                </thead>
                <tbody id="cmCandidaturesTableBody">
                <?php if (empty($rowsPage)): ?>
                    <?php cm_component('ui/empty-state', [
                        'in_table' => true,
                        'colspan' => 10,
                        'title' => '',
                        'message' => 'Aucune candidature verifiee disponible.',
                    ]); ?>
                <?php else: ?>
                    <?php foreach ($rowsPage as $row): ?>
                        <?php
                        $dateCandIso = '';
                        if (!empty($row['date_candidature'])) {
                            $dateCandIso = date('Y-m-d', strtotime((string) $row['date_candidature']));
                        }
                        $searchBlob = strtolower((string) ($row['id_rapport'] . ' ' . $row['num_etu'] . ' ' . $row['nom_complet'] . ' ' . $row['title']));
                        ?>
                        <tr class="cm-data-table__row cm-cand-main-row"
                            data-row-id="<?php echo (int) $row['id_rapport']; ?>"
                            data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>"
                            data-status="<?php echo htmlspecialchars(strtolower((string) $row['cand_status']), ENT_QUOTES, 'UTF-8'); ?>"
                            data-date="<?php echo htmlspecialchars($dateCandIso, ENT_QUOTES, 'UTF-8'); ?>">
                            <td class="cm-data-table__td is-checkbox">
                                <input type="checkbox" class="cm-checkbox cm-row-checkbox">
                            </td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $row['num_etu'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $row['nom_complet'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $row['promotion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo $row['m1'] !== null ? htmlspecialchars(number_format((float) $row['m1'], 2), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                            <td class="cm-data-table__td"><?php echo $row['m2'] !== null ? htmlspecialchars(number_format((float) $row['m2'], 2), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars(number_format((float) $row['montant_verse'], 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars(number_format((float) $row['reste_a_payer'], 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td">
                                <?php cm_component('ui/badge', ['text' => (string) $row['payment_status'], 'type' => (string) $row['payment_badge']]); ?>
                            </td>
                            <td class="cm-data-table__td is-center">
                                <button type="button" class="cm-btn-action is-edit cm-toggle-detail" data-target="cmCandDetail_<?php echo (int) $row['id_rapport']; ?>" title="Voir detail">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="cmCandDetail_<?php echo (int) $row['id_rapport']; ?>" class="cm-cand-detail-row cm-hidden">
                            <td class="cm-data-table__td" colspan="10">
                                <div class="cm-grid-4">
                                    <div><strong>Date Cand.</strong><br><?php echo !empty($row['date_candidature']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $row['date_candidature'])), ENT_QUOTES, 'UTF-8') : '-'; ?></div>
                                    <div><strong>Promotion</strong><br><?php echo htmlspecialchars((string) $row['promotion'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div><strong>Statut Candidature</strong><br><?php cm_component('ui/badge', ['text' => (string) $row['cand_status'], 'type' => (string) $row['cand_badge']]); ?></div>
                                    <div><strong>Admin traitant</strong><br><?php echo htmlspecialchars((string) ($row['admin'] !== '' ? $row['admin'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div><strong>Date traitement</strong><br><?php echo !empty($row['date_traitement']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $row['date_traitement'])), ENT_QUOTES, 'UTF-8') : '-'; ?></div>
                                </div>
                                <div class="cm-mt-md">
                                    <strong>Titre rapport:</strong>
                                    <?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php if (!empty($row['commentaire'])): ?>
                                    <div class="cm-mt-sm">
                                        <strong>Commentaire:</strong>
                                        <?php echo htmlspecialchars((string) $row['commentaire'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="cm-form-buttons cm-mt-md">
                                    <a class="cm-btn is-info"
                                       href="?page=gestion_dossiers_candidatures&action=consulter_rapport&id_rapport=<?php echo urlencode((string) $row['id_rapport']); ?>"
                                       target="_blank">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                        Consulter
                                    </a>
                                    <a class="cm-btn is-info"
                                       href="?page=gestion_dossiers_candidatures&action=telecharger_pdf&id_rapport=<?php echo urlencode((string) $row['id_rapport']); ?>">
                                        <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                        PDF
                                    </a>
                                    <button type="button"
                                            class="cm-btn is-primary cm-pick-traitement"
                                            data-id="<?php echo (int) $row['id_rapport']; ?>"
                                            data-etudiant="<?php echo htmlspecialchars((string) $row['nom_complet'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-date="<?php echo htmlspecialchars($dateCandIso, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-status="<?php echo htmlspecialchars((string) $row['cand_status'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-commentaire="<?php echo htmlspecialchars((string) $row['commentaire'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-url="<?php echo htmlspecialchars('?page=gestion_dossiers_candidatures&action=consulter_rapport&id_rapport=' . urlencode((string) $row['id_rapport']), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-info="<?php echo htmlspecialchars('Promotion: ' . $row['promotion'] . ' | Niveau: ' . $row['niveau'] . ' | Verse: ' . number_format((float) $row['montant_verse'], 0, ',', ' ') . ' FCFA | Reste: ' . number_format((float) $row['reste_a_payer'], 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                        Traiter
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php cm_component('crud/pagination', [
            'pagination' => $pagination,
            'base_url' => $paginationBaseUrl,
            'param_name' => 'p',
        ]); ?>
    </div>
</div>
</div>
<script>
(function () {
    const navigate = function (url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    };
    const toggleButtons = document.querySelectorAll('.cm-toggle-detail');
    for (let i = 0; i < toggleButtons.length; i++) {
        toggleButtons[i].addEventListener('click', function () {
            const targetId = toggleButtons[i].getAttribute('data-target');
            if (!targetId) {
                return;
            }
            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }
            target.classList.toggle('cm-hidden');
        });
    }
    const selectedId = document.getElementById('cmSelectedRapportId');
    const selectedUrl = document.getElementById('cmSelectedRapportUrl');
    const fieldEtudiant = document.getElementById('cmTraitementEtudiant');
    const fieldDate = document.getElementById('cmTraitementDate');
    const fieldStatut = document.getElementById('cmTraitementStatut');
    const fieldInfo = document.getElementById('cmTraitementInfo');
    const fieldCommentaire = document.getElementById('cmTraitementCommentaire');
    const openLink = document.getElementById('cmOpenRapportFromForm');
    const pickButtons = document.querySelectorAll('.cm-pick-traitement');
    for (let i = 0; i < pickButtons.length; i++) {
        pickButtons[i].addEventListener('click', function () {
            const btn = pickButtons[i];
            selectedId.value = btn.getAttribute('data-id') || '';
            selectedUrl.value = btn.getAttribute('data-url') || '';
            fieldEtudiant.value = btn.getAttribute('data-etudiant') || '';
            fieldDate.value = btn.getAttribute('data-date') || '';
            fieldStatut.value = btn.getAttribute('data-status') || '';
            fieldInfo.value = btn.getAttribute('data-info') || '';
            fieldCommentaire.value = btn.getAttribute('data-commentaire') || '';
            if (openLink && selectedUrl.value !== '') {
                openLink.href = selectedUrl.value;
                openLink.classList.remove('cm-hidden');
            }
        });
    }
    const applyBtn = document.getElementById('cmApplyTraitement');
    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            if (!selectedId.value) {
                window.alert('Sélectionnez d\'abord un dossier via le bouton Traiter.');
                return;
            }
            const statut = fieldStatut.value || '';
            const commentaire = (fieldCommentaire.value || '').trim();
            const normalized = statut.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            if (normalized === 'rejetee' && commentaire === '') {
                window.alert('Le commentaire est obligatoire pour un dossier rejeté.');
                return;
            }
            window.alert('Traitement pre-rempli. Le flux de validation final est gere par le module de commission.');
        });
    }
    const resetBtn = document.getElementById('cmResetTraitement');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            selectedId.value = '';
            selectedUrl.value = '';
            fieldEtudiant.value = '';
            fieldDate.value = '';
            fieldStatut.value = '';
            fieldInfo.value = '';
            fieldCommentaire.value = '';
            if (openLink) {
                openLink.href = '#';
                openLink.classList.add('cm-hidden');
            }
        });
    }
    const searchInput = document.getElementById('cmSearchCandidature');
    const limitSelect = document.getElementById('cmCandidaturesLimit');
    const statusFilter = document.getElementById('cmFilterCandStatus');
    const dateFilter = document.getElementById('cmFilterCandDate');
    const selectAllBtn = document.getElementById('cmSelectAllCandidatures');
    const deselectAllBtn = document.getElementById('cmDeselectAllCandidatures');
    const deleteBtn = document.getElementById('cmDeleteCandidatures');
    const selectedCount = document.getElementById('cmSelectedCandidaturesCount');
    const rowCheckboxes = function () {
        return Array.from(document.querySelectorAll('#cmCandidaturesTableBody .cm-cand-main-row .cm-row-checkbox'));
    };
    const updateSelectionState = function () {
        const all = rowCheckboxes();
        const checked = all.filter(function (cb) { return cb.checked; }).length;
        if (selectedCount) {
            selectedCount.textContent = String(checked);
        }
        if (deleteBtn) {
            deleteBtn.disabled = checked === 0;
        }
        if (checkAll) {
            checkAll.checked = all.length > 0 && all.every(function (cb) { return cb.checked; });
        }
    };
    const applyFilters = function () {
        const term = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const status = (statusFilter ? statusFilter.value : '').trim().toLowerCase();
        const date = (dateFilter ? dateFilter.value : '').trim();
        const rows = document.querySelectorAll('.cm-cand-main-row');
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const detailRow = document.getElementById('cmCandDetail_' + row.getAttribute('data-row-id'));
            const search = row.getAttribute('data-search') || '';
            const rowStatus = row.getAttribute('data-status') || '';
            const rowDate = row.getAttribute('data-date') || '';
            const matchSearch = term === '' || search.indexOf(term) !== -1;
            const matchStatus = status === '' || rowStatus === status;
            const matchDate = date === '' || rowDate === date;
            const visible = matchSearch && matchStatus && matchDate;
            row.style.display = visible ? '' : 'none';
            if (detailRow) {
                detailRow.style.display = visible ? '' : 'none';
                if (!visible) {
                    detailRow.classList.add('cm-hidden');
                }
            }
        }
    };
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    if (dateFilter) {
        dateFilter.addEventListener('change', applyFilters);
    }
    if (limitSelect) {
        limitSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('limit_candidatures', String(limitSelect.value));
            url.searchParams.set('p', '1');
            navigate(url.toString());
        });
    }
    const checkAll = document.getElementById('cmCheckAllCandidatures');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowCheckboxes().forEach(function (cb) {
                cb.checked = checkAll.checked;
            });
            updateSelectionState();
        });
    }
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = true; });
            updateSelectionState();
        });
    }
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = false; });
            updateSelectionState();
        });
    }
    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('cm-row-checkbox')) {
            updateSelectionState();
        }
    });
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            if (deleteBtn.disabled) {
                return;
            }
            window.alert('Suppression multiple indisponible sur cet ecran.');
        });
    }
    const exportBtn = document.getElementById('cmExportCandidatures');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['N°C', 'N° Etud.', 'Nom & Prenom', 'Niveau', 'M1', 'M2', 'Verse', 'Reste', 'Statut paiement'];
            const lines = [headers.join(';')];
            document.querySelectorAll('.cm-cand-main-row').forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                const cells = Array.from(row.querySelectorAll('td')).slice(1, 10);
                const values = cells.map(function (cell) {
                    return '"' + (cell.textContent || '').trim().replace(/"/g, '""') + '"';
                });
                lines.push(values.join(';'));
            });
            const blob = new Blob(["\uFEFF" + lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'candidatures_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
    const printBtn = document.getElementById('cmPrintCandidatures');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
    updateSelectionState();
})();
</script>

