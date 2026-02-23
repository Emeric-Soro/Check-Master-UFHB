<?php
$yearLabel = date('Y') . '-' . (date('Y') + 1);
try {
    if (!class_exists('Database')) {
        require_once dirname(__DIR__, 3) . '/app/config/database.php';
    }
    if (!class_exists('AnneeAcademique')) {
        require_once dirname(__DIR__, 3) . '/app/models/AnneeAcademique.php';
    }
    $anneeModel = new AnneeAcademique(Database::getConnection());
    $anneeActive = $anneeModel->getAnneeAcademiqueActive();
    if ($anneeActive && is_object($anneeActive) && !empty($anneeActive->date_deb) && !empty($anneeActive->date_fin)) {
        $yearLabel = date('Y', strtotime((string) $anneeActive->date_deb)) . '-' . date('Y', strtotime((string) $anneeActive->date_fin));
    }
} catch (Throwable $e) {
    error_log('PRD5 annee view year fallback: ' . $e->getMessage());
}

$anneeToEdit = $GLOBALS['annee_a_modifier'] ?? null;
$messageSuccess = (string) ($GLOBALS['messageSuccess'] ?? '');
$messageError = (string) ($GLOBALS['messageErreur'] ?? '');

$canCreateAction = function_exists('canCreate') ? canCreate() : true;
$canEditAction = function_exists('canEdit') ? canEdit() : true;
$canDeleteAction = function_exists('canDelete') ? canDelete() : true;

$allowedLimits = [5, 10, 25, 50, 100];
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$perPage = (int) ($_GET['limit'] ?? 10);
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$search = trim((string) ($_GET['search'] ?? ''));

$listYears = is_array($GLOBALS['listeAnnees'] ?? null) ? $GLOBALS['listeAnnees'] : [];
$today = strtotime(date('Y-m-d'));
$rawRows = [];

foreach ($listYears as $year) {
    $entry = is_array($year) ? $year : (array) $year;

    $id = (string) ($entry['id_annee_acad'] ?? '');
    $dateDeb = (string) ($entry['date_deb'] ?? '');
    $dateFin = (string) ($entry['date_fin'] ?? '');
    $label = ($dateDeb !== '' && $dateFin !== '')
        ? date('Y', strtotime($dateDeb)) . '-' . date('Y', strtotime($dateFin))
        : '-';

    if ($search !== '') {
        $haystack = strtolower($id . ' ' . $label . ' ' . $dateDeb . ' ' . $dateFin);
        if (strpos($haystack, strtolower($search)) === false) {
            continue;
        }
    }

    $startTs = $dateDeb !== '' ? strtotime($dateDeb) : false;
    $endTs = $dateFin !== '' ? strtotime($dateFin) : false;
    $isActive = ($startTs !== false && $endTs !== false && $today >= $startTs && $today <= $endTs);

    $rawRows[] = [
        'id' => $id,
        'date_deb' => $dateDeb !== '' ? date('d/m/Y', strtotime($dateDeb)) : '-',
        'date_fin' => $dateFin !== '' ? date('d/m/Y', strtotime($dateFin)) : '-',
        'label' => $label,
        'status' => [
            'label' => $isActive ? 'Active' : 'Inactive',
            'type' => $isActive ? 'success' : 'light',
        ],
    ];
}

$totalItems = count($rawRows);
$pagination = function_exists('cm_paginate')
    ? cm_paginate($totalItems, $perPage, $currentPage)
    : [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$offset = (int) ($pagination['offset'] ?? 0);
$visibleRows = array_slice($rawRows, $offset, $perPage);

$baseParams = [
    'page' => 'parametres_generaux',
    'action' => 'annees_academiques',
    'limit' => $perPage,
];
if ($search !== '') {
    $baseParams['search'] = $search;
}
$baseUrl = '?' . http_build_query($baseParams);

$columns = [
    cm_column('id', 'ID'),
    cm_column('date_deb', 'Date debut'),
    cm_column('date_fin', 'Date fin'),
    cm_column('label', 'Libelle'),
    cm_column('status', 'Statut', ['type' => 'badge']),
];

$tableActions = [];
if ($canEditAction) {
    $tableActions[] = [
        'tag' => 'button',
        'type' => 'button',
        'label' => 'Modifier',
        'icon' => 'fa-pen',
        'class' => 'cm-btn-action is-edit',
    ];
}
if ($canDeleteAction) {
    $tableActions[] = [
        'tag' => 'button',
        'type' => 'button',
        'label' => 'Supprimer',
        'icon' => 'fa-trash',
        'class' => 'cm-btn-action is-delete',
    ];
}

$formContent = '';
ob_start();
?>
<form method="POST"
      action="?page=parametres_generaux&action=annees_academiques"
      data-cm-ajax-form="true"
      class="cm-flex cm-flex-col cm-flex-gap-sm">
    <?php cm_component('form/csrf-token'); ?>
    <?php if ($anneeToEdit): ?>
    <input type="hidden" name="id_annee_acad" value="<?= htmlspecialchars((string) ($anneeToEdit->id_annee_acad ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <div class="cm-grid-2">
        <?php
        cm_component('form/input-date', [
            'name' => 'date_debut',
            'id' => 'date_debut',
            'label' => 'Date de debut',
            'required' => true,
            'value' => $anneeToEdit ? date('Y-m-d', strtotime((string) ($anneeToEdit->date_deb ?? ''))) : '',
        ]);
        cm_component('form/input-date', [
            'name' => 'date_fin',
            'id' => 'date_fin',
            'label' => 'Date de fin',
            'required' => true,
            'value' => $anneeToEdit ? date('Y-m-d', strtotime((string) ($anneeToEdit->date_fin ?? ''))) : '',
        ]);
        ?>
    </div>

    <div class="cm-form-buttons">
        <?php if ($anneeToEdit): ?>
            <a class="cm-btn is-light" href="?page=parametres_generaux&action=annees_academiques" data-cm-ajax-link="true">
                <i class="fas fa-times" aria-hidden="true"></i>
                Annuler
            </a>
            <?php if ($canEditAction): ?>
            <button type="submit" class="cm-btn is-success" name="btn_modifier_annees_academiques" value="1">
                <i class="fas fa-save" aria-hidden="true"></i>
                Modifier
            </button>
            <?php endif; ?>
        <?php else: ?>
            <button type="reset" class="cm-btn is-light">
                <i class="fas fa-rotate-left" aria-hidden="true"></i>
                Reinitialiser
            </button>
            <?php if ($canCreateAction): ?>
            <button type="submit" class="cm-btn is-success" name="btn_add_annees_academiques" value="1">
                <i class="fas fa-save" aria-hidden="true"></i>
                Enregistrer
            </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>
<?php
$formContent = (string) ob_get_clean();

$upperHtml = cm_render_component('crud/form-pole', [
    'title' => 'Ouverture / Fermeture annee academique',
    'icon' => 'fa-calendar-days',
    'content' => $formContent,
]);

$toolbarLeft = '';
ob_start();
?>
<label class="cm-toolbar__control">
    <span>Afficher:</span>
    <select class="cm-form-control cm-toolbar__select cm-toolbar-field-xs"
            data-cm-ajax-param="limit"
            data-cm-ajax-reset-param="p"
            data-cm-ajax-reset-value="1">
        <?php foreach ($allowedLimits as $limitValue): ?>
        <option value="<?= (int) $limitValue ?>" <?= $perPage === (int) $limitValue ? 'selected' : '' ?>><?= (int) $limitValue ?></option>
        <?php endforeach; ?>
    </select>
</label>
<span class="cm-toolbar-year">
    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
    <?= htmlspecialchars($yearLabel, ENT_QUOTES, 'UTF-8') ?>
</span>
<?php
$toolbarLeft = (string) ob_get_clean();

$toolbarCenter = '';
ob_start();
?>
<form method="GET" action="" data-cm-ajax-form="true" class="cm-toolbar__search">
    <input type="hidden" name="page" value="parametres_generaux">
    <input type="hidden" name="action" value="annees_academiques">
    <input type="hidden" name="limit" value="<?= (int) $perPage ?>">
    <input type="hidden" name="p" value="1">
    <input type="search"
           name="search"
           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
           placeholder="Rechercher une annee..."
           class="cm-form-control cm-toolbar-field-lg">
</form>
<?php
$toolbarCenter = (string) ob_get_clean();

$toolbarRight = '';
ob_start();
?>
<?php if ($canDeleteAction): ?>
<button type="button" class="cm-btn is-info is-sm" id="cmYearsSelectAllBtn">
    <i class="fas fa-square-check" aria-hidden="true"></i>
    Tout selectionner
</button>
<button type="button" class="cm-btn is-light is-sm" id="cmYearsClearBtn">
    <i class="fas fa-square" aria-hidden="true"></i>
    Deselectionner
</button>
<button type="button" class="cm-btn is-danger is-sm" id="cmYearsDeleteBtn" disabled>
    <i class="fas fa-trash" aria-hidden="true"></i>
    <span>Supprimer (0)</span>
</button>
<?php endif; ?>
<?php
$toolbarRight = (string) ob_get_clean();

$toolbarHtml = cm_render_component('crud/toolbar', [
    'left_html' => $toolbarLeft,
    'center_html' => $toolbarCenter,
    'right_html' => $toolbarRight,
]);

$tableHtml = '';
ob_start();
?>
<form method="POST"
      action="?page=parametres_generaux&action=annees_academiques"
      id="cmAnneesTableForm"
      class="cm-table-form"
      data-cm-ajax-form="true">
    <?php cm_component('form/csrf-token'); ?>
    <input type="hidden" name="submit_delete_multiple" id="cmDeleteMultipleFlag" value="0">
    <div id="cmSelectedIdsHolder"></div>

    <?php
    cm_component('crud/data-table', [
        'id' => 'cmAdminYearsTable',
        'columns' => $columns,
        'rows' => $visibleRows,
        'selectable' => $canDeleteAction,
        'row_key' => 'id',
        'actions' => $tableActions,
        'empty_title' => 'Aucune annee academique',
        'empty_message' => 'Aucune ligne ne correspond aux filtres en cours.',
    ]);
    ?>
</form>
<?php
$tableHtml = (string) ob_get_clean();

$paginationHtml = cm_render_component('crud/pagination', [
    'pagination' => $pagination,
    'base_url' => $baseUrl,
    'param_name' => 'p',
]);

if ($messageSuccess !== '') {
    cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]);
}
if ($messageError !== '') {
    cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageError]);
}
?>

<section class="cm-prd3-screen cm-prd3-crud-screen">
    <div class="cm-crud-wrapper">
        <?= $upperHtml ?>
        <?= $toolbarHtml ?>
        <section class="cm-pole-inferieur">
            <?= $tableHtml ?>
            <?= $paginationHtml ?>
        </section>
    </div>
</section>

<script>
(function () {
    var tableForm = document.getElementById('cmAnneesTableForm');
    if (!tableForm) {
        return;
    }

    var checkAll = tableForm.querySelector('.cm-table-check-all');
    var getRowChecks = function () {
        return Array.prototype.slice.call(tableForm.querySelectorAll('.cm-table-check-row'));
    };
    var deleteFlag = document.getElementById('cmDeleteMultipleFlag');
    var selectedIdsHolder = document.getElementById('cmSelectedIdsHolder');
    var deleteBtn = document.getElementById('cmYearsDeleteBtn');
    var selectAllBtn = document.getElementById('cmYearsSelectAllBtn');
    var clearBtn = document.getElementById('cmYearsClearBtn');

    function checkedIds() {
        return getRowChecks().filter(function (item) {
            return item.checked;
        }).map(function (item) {
            return item.value;
        });
    }

    function rebuildSelectedIds(ids) {
        if (!selectedIdsHolder) {
            return;
        }
        selectedIdsHolder.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = id;
            selectedIdsHolder.appendChild(input);
        });
    }

    function syncDeleteButton() {
        if (!deleteBtn) {
            return;
        }
        var count = checkedIds().length;
        deleteBtn.disabled = count === 0;
        var span = deleteBtn.querySelector('span');
        if (span) {
            span.textContent = 'Supprimer (' + count + ')';
        }
    }

    function syncMasterCheckbox() {
        if (!checkAll) {
            return;
        }
        var rows = getRowChecks();
        if (rows.length === 0) {
            checkAll.checked = false;
            return;
        }
        checkAll.checked = rows.every(function (row) { return row.checked; });
    }

    function bindRowCheckboxes() {
        getRowChecks().forEach(function (row) {
            row.addEventListener('change', function () {
                syncMasterCheckbox();
                syncDeleteButton();
            });
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            getRowChecks().forEach(function (row) {
                row.checked = checkAll.checked;
            });
            syncDeleteButton();
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            getRowChecks().forEach(function (row) {
                row.checked = true;
            });
            syncMasterCheckbox();
            syncDeleteButton();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            getRowChecks().forEach(function (row) {
                row.checked = false;
            });
            syncMasterCheckbox();
            syncDeleteButton();
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            var ids = checkedIds();
            if (ids.length === 0) {
                return;
            }
            if (!window.confirm('Confirmer la suppression des annees selectionnees ?')) {
                return;
            }
            deleteFlag.value = '1';
            rebuildSelectedIds(ids);
            tableForm.requestSubmit();
        });
    }

    tableForm.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        var actionButton = target.closest('.cm-btn-action');
        if (!actionButton) {
            return;
        }

        var rowId = actionButton.getAttribute('data-row-id') || '';
        if (rowId === '') {
            return;
        }

        if (actionButton.classList.contains('is-edit')) {
            var editUrl = '?page=parametres_generaux&action=annees_academiques&id_annee_acad=' + encodeURIComponent(rowId);
            if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                window.CM.ajax.load(editUrl);
            } else {
                window.location.href = editUrl;
            }
            return;
        }

        if (actionButton.classList.contains('is-delete')) {
            if (!window.confirm('Confirmer la suppression de cette annee academique ?')) {
                return;
            }
            deleteFlag.value = '1';
            rebuildSelectedIds([rowId]);
            tableForm.requestSubmit();
        }
    });

    bindRowCheckboxes();
    syncMasterCheckbox();
    syncDeleteButton();
})();
</script>
