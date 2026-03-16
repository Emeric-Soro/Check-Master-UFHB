<?php
$archives = is_array($GLOBALS['archives'] ?? null) ? $GLOBALS['archives'] : [];
$currentPage = max(1, (int) ($GLOBALS['currentPage'] ?? 1));
$totalPages = max(1, (int) ($GLOBALS['totalPages'] ?? 1));
$totalArchives = max(0, (int) ($GLOBALS['totalArchives'] ?? count($archives)));
$search = trim((string) ($GLOBALS['search'] ?? ''));
$year = trim((string) ($GLOBALS['year'] ?? ''));
$perPage = max(5, (int) ($GLOBALS['limit_archive_cr'] ?? 10));
$allowedLimits = [5, 10, 25, 50];
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$pagination = function_exists('cm_paginate')
    ? cm_paginate($totalArchives, $perPage, $currentPage)
    : [
        'total' => $totalArchives,
        'per_page' => $perPage,
        'current' => $currentPage,
        'last' => $totalPages,
        'offset' => ($currentPage - 1) * $perPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'pages' => [$currentPage],
    ];
$baseUrl = '?page=archive_comptes_rendus'
    . '&search=' . urlencode($search)
    . '&year=' . urlencode($year)
    . '&limit_archive_cr=' . $perPage;
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div id="cmArchiveAlert"></div>
    <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmArchiveCsrfForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form id="cmArchiveCsrfForm" style="display:none;">
        <?php cm_component('form/csrf-token'); ?>
    </form>
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="">
            </div>
        </div>
        <?php cm_toolbar([
            'screen' => 'archive_comptes_rendus',
            'id_prefix' => 'cmArchive',
            'search_value' => $search,
            'limit' => $perPage,
            'allowed_limits' => $allowedLimits,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmArchiveTable">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th cm-data-table__th--check">
                            <input type="checkbox" id="cmArchiveCheckAll" aria-label="Tout sélectionner">
                        </th>
                        <th class="cm-data-table__th">N CR</th>
                        <th class="cm-data-table__th">Nom CR</th>
                        <th class="cm-data-table__th">Etudiant</th>
                        <th class="cm-data-table__th">Date</th>
                        <th class="cm-data-table__th">Statut</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="cmArchiveTableBody">
                    <?php if (empty($archives)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 7,
                            'title' => '',
                            'message' => 'Aucun compte rendu archive.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($archives as $archive): ?>
                            <?php
                            $idCr = (int) ($archive['id_CR'] ?? 0);
                            $nomCr = (string) ($archive['nom_CR'] ?? '');
                            $etudiant = trim((string) ($archive['prenom_etu'] ?? '') . ' ' . (string) ($archive['nom_etu'] ?? ''));
                            $dateCr = !empty($archive['date_CR']) ? date('d/m/Y', strtotime((string) $archive['date_CR'])) : '-';
                            $searchText = strtolower($nomCr . ' ' . $etudiant . ' ' . (string) ($archive['email_etu'] ?? ''));
                            ?>
                            <tr class="cm-data-table__row"
                                data-id="<?php echo $idCr; ?>"
                                data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="cm-data-table__td cm-data-table__td--check">
                                    <input type="checkbox" class="cm-archive-check-row" value="<?php echo $idCr; ?>" aria-label="Sélectionner archive <?php echo $idCr; ?>">
                                </td>
                                <td class="cm-data-table__td">#<?php echo $idCr; ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($nomCr, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($etudiant, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($dateCr, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td">
                                    <?php cm_component('ui/badge', ['text' => 'Finalise', 'type' => 'success']); ?>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions">
                                        <a class="cm-btn-action is-view"
                                           href="?page=archive_comptes_rendus&action=view&id=<?php echo $idCr; ?>"
                                           target="_blank"
                                           rel="noopener"
                                           title="Voir">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if (!empty($archive['chemin_fichier_pdf'])): ?>
                                            <a class="cm-btn-action is-view"
                                               href="?page=archive_comptes_rendus&action=download_pdf&chemin=<?php echo urlencode((string) $archive['chemin_fichier_pdf']); ?>"
                                               target="_blank"
                                               rel="noopener"
                                               title="Telecharger PDF">
                                                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="cm-btn-action is-delete cm-archive-delete-one"
                                                data-id="<?php echo $idCr; ?>"
                                                title="Supprimer">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
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
                'param_name' => 'page_num',
            ]);
            ?>
        </div>
    </div>
</div>
<script>
(function () {
    const alertBox = document.getElementById('cmArchiveAlert');
    const searchInput = document.getElementById('cmArchiveSearch');
    const checkAll = document.getElementById('cmArchiveCheckAll');
    const selectAllBtn = document.getElementById('cmArchiveSelectAllBtn');
    const deselectBtn = document.getElementById('cmArchiveDeselectBtn');
    const deleteBtn = document.getElementById('cmArchiveDeleteBtn');
    function setAlert(type, message) {
        if (!alertBox) {
            return;
        }
        const cssType = type === 'success' ? 'success' : 'danger';
        alertBox.innerHTML = '<div class=\"cm-alert is-' + cssType + '\"><div class=\"cm-alert__content\"><span class=\"cm-alert__message\">' +
            String(message || '').replace(/[<>&]/g, '') + '</span></div></div>';
    }
    function getRows() {
        return Array.from(document.querySelectorAll('#cmArchiveTableBody .cm-data-table__row'));
    }
    function getVisibleRows() {
        return getRows().filter(function (row) { return row.style.display !== 'none'; });
    }
    function getCheckedRows() {
        return getRows().filter(function (row) {
            const cb = row.querySelector('.cm-archive-check-row');
            return cb && cb.checked;
        });
    }
    function updateDeleteState() {
        const count = getCheckedRows().length;
        if (deleteBtn) {
            deleteBtn.disabled = count === 0;
            deleteBtn.innerHTML = '<i class=\"fas fa-trash\" aria-hidden=\"true\"></i> Supprimer (' + count + ')';
        }
        if (checkAll) {
            const visible = getVisibleRows();
            const checkedVisible = visible.filter(function (row) {
                const cb = row.querySelector('.cm-archive-check-row');
                return cb && cb.checked;
            });
            checkAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
        }
    }
    function deleteArchive(id) {
        const formData = new FormData();
        formData.append('id_CR', id);
        const tokenInput = document.querySelector('#cmArchiveCsrfForm input[name=\"csrf_token\"]');
        if (tokenInput && tokenInput.value) {
            formData.append('csrf_token', tokenInput.value);
        }
        return fetch('?page=archive_comptes_rendus&action=delete', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) { return response.json(); });
    }
    document.querySelectorAll('.cm-archive-delete-one').forEach(function (button) {
        button.addEventListener('click', async function () {
            const id = button.getAttribute('data-id') || '';
            if (!id) {
                return;
            }
            const confirmed = await window.CM.confirm({
                title: 'Suppression',
                message: 'Supprimer cette archive ?',
                type: 'danger',
                confirmText: 'Supprimer',
            });
            if (!confirmed) {
                return;
            }
            deleteArchive(id).then(function (payload) {
                if (!payload || !payload.success) {
                    setAlert('error', payload && payload.message ? payload.message : 'Suppression impossible.');
                    return;
                }
                setAlert('success', payload.message || 'Archive supprimee.');
                const row = button.closest('.cm-data-table__row');
                if (row) row.remove();
                updateDeleteState();
            }).catch(function () {
                setAlert('error', 'Erreur reseau.');
            });
        });
    });
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async function () {
            const checked = getCheckedRows();
            if (checked.length === 0) {
                return;
            }
            const confirmed = await window.CM.confirm({
                title: 'Suppression multiple',
                message: 'Supprimer ' + checked.length + ' archive(s) ?',
                type: 'danger',
                confirmText: 'Supprimer',
            });
            if (!confirmed) {
                return;
            }
            Promise.all(checked.map(function (row) {
                const cb = row.querySelector('.cm-archive-check-row');
                return cb ? deleteArchive(cb.value) : Promise.resolve({ success: false });
            })).then(function () {
                checked.forEach(function (row) { row.remove(); });
                updateDeleteState();
                setAlert('success', 'Suppression terminee.');
            }).catch(function () {
                setAlert('error', 'Erreur reseau.');
            });
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = (searchInput.value || '').trim().toLowerCase();
            getRows().forEach(function (row) {
                const text = row.getAttribute('data-search') || '';
                row.style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
            });
            updateDeleteState();
        });
    }
    document.addEventListener('change', function (event) {
        if (event.target && event.target.classList.contains('cm-archive-check-row')) {
            updateDeleteState();
        }
    });
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            getVisibleRows().forEach(function (row) {
                const cb = row.querySelector('.cm-archive-check-row');
                if (cb) cb.checked = checkAll.checked;
            });
            updateDeleteState();
        });
    }
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            getVisibleRows().forEach(function (row) {
                const cb = row.querySelector('.cm-archive-check-row');
                if (cb) cb.checked = true;
            });
            updateDeleteState();
        });
    }
    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            getRows().forEach(function (row) {
                const cb = row.querySelector('.cm-archive-check-row');
                if (cb) cb.checked = false;
            });
            updateDeleteState();
        });
    }
    updateDeleteState();
})();
</script>

