<?php
$reclamationsEnCours = is_array($GLOBALS['reclamationsEnCours'] ?? null) ? $GLOBALS['reclamationsEnCours'] : [];
$reclamationsTraitees = is_array($GLOBALS['reclamationsTraitees'] ?? null) ? $GLOBALS['reclamationsTraitees'] : [];
$allReclamations = array_merge($reclamationsEnCours, $reclamationsTraitees);
usort($allReclamations, static function ($a, $b) {
    $dateA = strtotime((string) ($a->date_creation ?? '1970-01-01'));
    $dateB = strtotime((string) ($b->date_creation ?? '1970-01-01'));
    return $dateB <=> $dateA;
});
$stats = [
    'en_attente' => 0,
    'en_cours' => 0,
    'resolue' => 0,
    'rejetee' => 0,
];
foreach ($allReclamations as $rec) {
    $status = strtolower(trim((string) ($rec->statut_reclamation ?? '')));
    if ($status === 'en attente') {
        $stats['en_attente']++;
    } elseif ($status === 'en cours') {
        $stats['en_cours']++;
    } elseif ($status === 'résolue' || $status === 'resolue' || $status === 'traitée' || $status === 'traitee') {
        $stats['resolue']++;
    } elseif ($status === 'rejetée' || $status === 'rejetee' || $status === 'rejeté' || $status === 'rejete') {
        $stats['rejetee']++;
    }
}
?>
<?php
$allowedLimits = [2, 5, 10, 25, 50, 100];
$reclamationsPerPage = max(2, (int) ($_GET['limit_reclamations'] ?? 10));
if (!in_array($reclamationsPerPage, $allowedLimits, true)) {
    $reclamationsPerPage = 10;
}
$reclamationsPage = max(1, (int) ($_GET['page_reclamations'] ?? 1));
$reclamationPagination = function_exists('cm_paginate')
    ? cm_paginate(count($allReclamations), $reclamationsPerPage, $reclamationsPage)
    : [
        'total' => count($allReclamations),
        'per_page' => $reclamationsPerPage,
        'current' => $reclamationsPage,
        'last' => max(1, (int) ceil(max(1, count($allReclamations)) / $reclamationsPerPage)),
        'offset' => max(0, ($reclamationsPage - 1) * $reclamationsPerPage),
        'has_prev' => $reclamationsPage > 1,
        'has_next' => $reclamationsPage < max(1, (int) ceil(max(1, count($allReclamations)) / $reclamationsPerPage)),
        'pages' => [$reclamationsPage],
    ];
$reclamationsPageRows = array_slice($allReclamations, (int) ($reclamationPagination['offset'] ?? 0), $reclamationsPerPage);
$paginationBaseUrl = '?page=gestion_reclamations_scolarite&limit_reclamations=' . $reclamationsPerPage;
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => '',
        'subtitle' => 'Traitement unifie des réclamations et historique filtré.',
        'annee' => trim((string) ($_SESSION['global_annee_selected'] ?? '')),
        'icon' => 'fa-circle-exclamation',
    ]);
    ?>
    <div class="cm-dashboard-grid cm-mb-md">
        <?php
        cm_component('dashboard/stat-widget', [
            'value' => (string) $stats['en_attente'],
            'label' => 'En attente',
            'icon' => 'fa-clock',
            'color' => 'warning',
        ]);
        cm_component('dashboard/stat-widget', [
            'value' => (string) $stats['en_cours'],
            'label' => 'En cours',
            'icon' => 'fa-spinner',
            'color' => 'info',
        ]);
        cm_component('dashboard/stat-widget', [
            'value' => (string) $stats['resolue'],
            'label' => 'Resolues',
            'icon' => 'fa-circle-check',
            'color' => 'success',
        ]);
        cm_component('dashboard/stat-widget', [
            'value' => (string) $stats['rejetee'],
            'label' => 'Rejetées',
            'icon' => 'fa-circle-xmark',
            'color' => 'danger',
        ]);
        ?>
    </div>
    <div class="cm-crud-wrapper">
    <div class="cm-pole-superieur">
        <div class="">
        </div>
        <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmReclamationForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form id="cmReclamationForm" method="POST">
            <?php cm_component('form/csrf-token'); ?>
            <input type="hidden" id="cmReclamationId" value="">
            <div class="cm-grid-4">
                <?php
                cm_component('form/input-text', [
                    'name' => 'reclamation_de',
                    'id' => 'cmReclamationDe',
                    'label' => 'Reclamation de',
                    'readonly' => true,
                ]);
                cm_component('form/input-text', [
                    'name' => 'reclamation_objet',
                    'id' => 'cmReclamationObjet',
                    'label' => 'Objet',
                    'readonly' => true,
                ]);
                cm_component('form/select', [
                    'name' => 'nouveau_statut',
                    'id' => 'cmNouveauStatut',
                    'label' => 'Nouveau statut',
                    'required' => true,
                    'options' => [
                        'En attente' => 'En attente',
                        'En cours' => 'En cours',
                        'Résolue' => 'Résolue',
                        'Rejetée' => 'Rejetée',
                    ],
                ]);
                cm_component('form/input-text', [
                    'name' => 'reclamation_date',
                    'id' => 'cmReclamationDate',
                    'label' => 'Date réclamation',
                    'readonly' => true,
                ]);
                ?>
            </div>
            <?php
            cm_component('form/textarea', [
                'name' => 'reponse_admin',
                'id' => 'cmReponseAdmin',
                'label' => 'Reponse',
                'required' => true,
                'rows' => 4,
            ]);
            ?>
            <div class="cm-form-buttons">
                <?php
                $reclamFormActions = [
                    ['label' => 'Réinitialiser', 'type' => 'button', 'class' => 'cm-btn is-secondary is-sm', 'attrs' => ['id' => 'cmResetReclamation']],
                ];
                if (canEdit()) {
                    $reclamFormActions[] = ['label' => 'Répondre', 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm'];
                }
                cm_component('crud/form-actions', [
                    'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']],
                    'actions' => $reclamFormActions,
                ]);
                ?>
            </div>
        </form>
    </div>
    <?php cm_toolbar([
        'screen' => 'gestion_reclamations_scolarite',
        'id_prefix' => 'cmReclamations',
        'search_value' => $_GET['search'] ?? '',
        'limit' => $reclamationsPerPage,
        'limit_options' => $allowedLimits,
        'can_delete' => canDelete(),
        'can_view' => canView(),
    ]); ?>
    <div class="cm-pole-inferieur">
        <div class="cm-table-wrapper">
            <table class="cm-data-table" id="cmReclamationsTable">
                <thead>
                <tr>
                    <th class="cm-data-table__th is-checkbox">
                        <input type="checkbox" id="cmCheckAllReclamations" class="cm-checkbox" aria-label="Sélectionner toutes les lignes">
                    </th>
                    <th class="cm-data-table__th">N° Réclamation</th>
                    <th class="cm-data-table__th">Nom &amp; Prénom Étudiant</th>
                    <th class="cm-data-table__th">Objet</th>
                    <th class="cm-data-table__th">Date Réclamation</th>
                    <th class="cm-data-table__th">Statut</th>
                    <th class="cm-data-table__th">Actions</th>
                </tr>
                </thead>
                <tbody id="cmReclamationsBody">
                <?php if (empty($reclamationsPageRows)): ?>
                    <?php cm_component('ui/empty-state', [
                        'in_table' => true,
                        'colspan' => 7,
                        'title' => '',
                        'message' => 'Aucune réclamation à traiter.',
                    ]); ?>
                <?php else: ?>
                    <?php foreach ($reclamationsPageRows as $rec): ?>
                        <?php
                        $id = (int) ($rec->id_reclamation ?? 0);
                        $nomComplet = trim((string) ($rec->nom_etu ?? '') . ' ' . (string) ($rec->prenom_etu ?? ''));
                        $objet = (string) ($rec->titre_reclamation ?? $rec->objet_reclamation ?? '');
                        $description = (string) ($rec->description_reclamation ?? '');
                        $dateRaw = (string) ($rec->date_creation ?? '');
                        $dateIso = $dateRaw !== '' ? date('Y-m-d', strtotime($dateRaw)) : '';
                        $dateDisplay = $dateRaw !== '' ? date('d/m/Y', strtotime($dateRaw)) : '';
                        $statusRaw = strtolower(trim((string) ($rec->statut_reclamation ?? '')));
                        $statusLabel = (string) ($rec->statut_reclamation ?? '');
                        $badgeType = 'info';
                        if ($statusRaw === 'en attente') {
                            $badgeType = 'warning';
                            $statusLabel = 'En attente';
                        } elseif ($statusRaw === 'en cours') {
                            $badgeType = 'info';
                            $statusLabel = 'En cours';
                        } elseif ($statusRaw === 'résolue' || $statusRaw === 'resolue' || $statusRaw === 'traitée' || $statusRaw === 'traitee') {
                            $badgeType = 'success';
                            $statusLabel = 'Résolue';
                        } elseif ($statusRaw === 'rejetée' || $statusRaw === 'rejetee' || $statusRaw === 'rejeté' || $statusRaw === 'rejete') {
                            $badgeType = 'danger';
                            $statusLabel = 'Rejetée';
                        }
                        $searchBlob = strtolower((string) ($id . ' ' . $nomComplet . ' ' . $objet . ' ' . $description));
                        ?>
                        <tr class="cm-data-table__row cm-rec-main-row"
                            data-id="<?php echo $id; ?>"
                            data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>"
                            data-statut="<?php echo htmlspecialchars(strtolower($statusLabel), ENT_QUOTES, 'UTF-8'); ?>"
                            data-date="<?php echo htmlspecialchars($dateIso, ENT_QUOTES, 'UTF-8'); ?>">
                            <td class="cm-data-table__td is-checkbox">
                                <input type="checkbox" class="cm-checkbox cm-row-checkbox">
                            </td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($objet, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td">
                                <?php cm_component('ui/badge', ['text' => $statusLabel, 'type' => $badgeType]); ?>
                            </td>
                            <td class="cm-data-table__td">
                                <div class="cm-row-actions">
                                    <button type="button" class="cm-btn-action is-edit cmToggleRecDetail" data-target="cmRecDetail_<?php echo $id; ?>" title="Consulter">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                    <?php if (canEdit()): ?>
                                        <button type="button"
                                                class="cm-btn-action is-edit cmPickReclamation"
                                                data-id="<?php echo $id; ?>"
                                                data-etudiant="<?php echo htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-objet="<?php echo htmlspecialchars($objet, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-date="<?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-statut="<?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-description="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Traiter">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr id="cmRecDetail_<?php echo $id; ?>" class="cm-hidden">
                            <td class="cm-data-table__td" colspan="7">
                                <div class="cm-grid-2">
                                    <div>
                                        <strong>Description:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')); ?>
                                    </div>
                                    <div>
                                        <strong>Statut actuel:</strong><br>
                                        <?php cm_component('ui/badge', ['text' => $statusLabel, 'type' => $badgeType]); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php cm_component('crud/pagination', [
            'pagination' => $reclamationPagination,
            'base_url' => $paginationBaseUrl,
            'param_name' => 'page_reclamations',
        ]); ?>
    </div>
</div>
</div>
<script>
(function () {
    const form = document.getElementById('cmReclamationForm');
    const idField = document.getElementById('cmReclamationId');
    const etuField = document.getElementById('cmReclamationDe');
    const objetField = document.getElementById('cmReclamationObjet');
    const dateField = document.getElementById('cmReclamationDate');
    const statutField = document.getElementById('cmNouveauStatut');
    const reponseField = document.getElementById('cmReponseAdmin');
    const navigate = function (url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    };
    const toggleButtons = document.querySelectorAll('.cmToggleRecDetail');
    for (let i = 0; i < toggleButtons.length; i++) {
        toggleButtons[i].addEventListener('click', function () {
            const targetId = toggleButtons[i].getAttribute('data-target');
            if (!targetId) {
                return;
            }
            const row = document.getElementById(targetId);
            if (row) {
                row.classList.toggle('cm-hidden');
            }
        });
    }
    const pickButtons = document.querySelectorAll('.cmPickReclamation');
    for (let i = 0; i < pickButtons.length; i++) {
        pickButtons[i].addEventListener('click', function () {
            const btn = pickButtons[i];
            idField.value = btn.getAttribute('data-id') || '';
            etuField.value = btn.getAttribute('data-etudiant') || '';
            objetField.value = btn.getAttribute('data-objet') || '';
            dateField.value = btn.getAttribute('data-date') || '';
            statutField.value = btn.getAttribute('data-statut') || '';
            reponseField.value = btn.getAttribute('data-description') || '';
        });
    }
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!idField.value) {
                event.preventDefault();
                window.alert('Selectionnez une réclamation à traiter.');
                return;
            }
            if (!statutField.value) {
                event.preventDefault();
                window.alert('Sélectionnez un statut.');
                return;
            }
            if (!reponseField.value.trim()) {
                event.preventDefault();
                window.alert('La reponse est obligatoire.');
                return;
            }
            form.action = '?page=gestion_reclamations_scolarite&action=changer_statut&id=' + encodeURIComponent(idField.value);
        });
    }
    const resetBtn = document.getElementById('cmResetReclamation');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            idField.value = '';
            etuField.value = '';
            objetField.value = '';
            dateField.value = '';
            statutField.value = '';
            reponseField.value = '';
        });
    }
    const searchInput = document.getElementById('cmSearchReclamation');
    const limitSelect = document.getElementById('cmReclamationsLimit');
    const statutFilter = document.getElementById('cmFilterStatutRec');
    const dateFilter = document.getElementById('cmFilterDateRec');
    const selectAllBtn = document.getElementById('cmSelectAllReclamationsBtn');
    const deselectAllBtn = document.getElementById('cmDeselectAllReclamationsBtn');
    const deleteBtn = document.getElementById('cmDeleteReclamationsBtn');
    const selectedCount = document.getElementById('cmSelectedReclamationsCount');
    const rowCheckboxes = function () {
        return Array.from(document.querySelectorAll('#cmReclamationsBody .cm-rec-main-row .cm-row-checkbox'));
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
        const statut = (statutFilter ? statutFilter.value : '').trim().toLowerCase();
        const date = (dateFilter ? dateFilter.value : '').trim();
        const rows = document.querySelectorAll('.cm-rec-main-row');
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const detail = document.getElementById('cmRecDetail_' + row.getAttribute('data-id'));
            const search = row.getAttribute('data-search') || '';
            const rowStatut = row.getAttribute('data-statut') || '';
            const rowDate = row.getAttribute('data-date') || '';
            const matchSearch = term === '' || search.indexOf(term) !== -1;
            const matchStatut = statut === '' || rowStatut === statut;
            const matchDate = date === '' || rowDate === date;
            const visible = matchSearch && matchStatut && matchDate;
            row.style.display = visible ? '' : 'none';
            if (detail) {
                detail.style.display = visible ? '' : 'none';
                if (!visible) {
                    detail.classList.add('cm-hidden');
                }
            }
        }
    };
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (statutFilter) {
        statutFilter.addEventListener('change', applyFilters);
    }
    if (dateFilter) {
        dateFilter.addEventListener('change', applyFilters);
    }
    if (limitSelect) {
        limitSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('limit_reclamations', String(limitSelect.value));
            url.searchParams.set('page_reclamations', '1');
            navigate(url.toString());
        });
    }
    const checkAll = document.getElementById('cmCheckAllReclamations');
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
    const exportBtn = document.getElementById('cmExportReclamation');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['N° Reclamation', 'Etudiant', 'Objet', 'Date Reclamation', 'Statut'];
            const lines = [headers.join(';')];
            document.querySelectorAll('.cm-rec-main-row').forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                const cells = Array.from(row.querySelectorAll('td')).slice(1, 6);
                const values = cells.map(function (cell) {
                    return '"' + (cell.textContent || '').trim().replace(/"/g, '""') + '"';
                });
                lines.push(values.join(';'));
            });
            const blob = new Blob(["\uFEFF" + lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'reclamations_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
    const printBtn = document.getElementById('cmPrintReclamation');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
    updateSelectionState();
})();
</script>

