<?php
// require_once __DIR__ . '/../../app/controllers/MemoireController.php';

// Pour l'instant, on simule les données - à remplacer par un vrai contrôleur
// $controller = new MemoireController();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$message = '';
$messageType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_memoire') {
        // Traiter l'upload du mémoire
        // $result = $controller->uploadMemoire();
        // $message = (string) ($result['message'] ?? '');
        // $messageType = !empty($result['success']) ? 'success' : 'error';
    } elseif ($_POST['action'] === 'supprimer_memoire') {
        // Supprimer un mémoire
        // $result = $controller->supprimerMemoire();
        // $message = (string) ($result['message'] ?? '');
        // $messageType = !empty($result['success']) ? 'success' : 'error';
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $messageType === 'success',
            'message' => $message,
        ]);
        exit;
    }
}

// Récupérer les étudiants ayant soutenu (à adapter selon votre base de données)
// $etudiants = $controller->getEtudiantsAvecSoutenance();
$etudiants = [];

// Récupérer les mémoires déjà mis en ligne
// $memoires = $controller->getMemoiresEnLigne();
$memoires = [];

$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_memoire'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_memoire'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($memoires), $perPage, $currentPage)
    : [
        'total' => count($memoires),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($memoires, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=mise_en_ligne_memoire&limit_memoire=' . $perPage;

$etudiantOptions = [];
foreach ($etudiants as $etudiant) {
    $num = (string) ($etudiant['num_etu'] ?? '');
    if ($num === '') {
        continue;
    }
    $etudiantOptions[$num] = (string) ($etudiant['nom_complet'] ?? 'Etudiant');
}
?>

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php if ($message !== ''): ?>
        <?php cm_component('ui/alert-box', [
            'type' => $messageType === 'success' ? 'success' : 'danger',
            'message' => $message,
        ]); ?>
    <?php endif; ?>

    <div id="cmMemoireAlert"></div>

    <div class="cm-crud-wrapper">
        <!-- Formulaire de mise en ligne -->
        <div class="cm-pole-superieur">
            <div class="cm-text-md cm-text-semibold cm-mb-md">Mise en ligne de mémoire</div>
            <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmMemoireForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}

.cm-memoire-toolbar .cm-toolbar-left {
    flex: 1 1 20rem !important;
}

.cm-memoire-toolbar .cm-toolbar-center {
    flex: 1 1 26rem !important;
}

.cm-memoire-toolbar .cm-toolbar-right {
    flex: 0 0 auto !important;
}

.cm-memoire-toolbar .cm-toolbar-left .cm-toolbar-field-lg {
    min-width: 13rem !important;
    max-width: 18rem !important;
}
</style>
<form id="cmMemoireForm" method="POST" action="?page=mise_en_ligne_memoire" enctype="multipart/form-data"
                data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="action" value="upload_memoire">
                <input type="hidden" name="num_etu" id="cmMemoireNumEtu" value="">

                <div class="cm-grid-2">
                    <?php
                    cm_component('form/select', [
                        'name' => 'cm_memoire_etudiant',
                        'id' => 'cmMemoireEtudiantSelect',
                        'label' => 'Etudiant',
                        'required' => true,
                        'options' => $etudiantOptions,
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_memoire_promotion',
                        'id' => 'cmMemoirePromotion',
                        'label' => 'Promotion',
                        'readonly' => true,
                        'maxlength' => 9,
                        'attrs' => ['size' => '9'],
                    ]);
                    cm_component('form/textarea', [
                        'name' => 'cm_memoire_theme',
                        'id' => 'cmMemoireTheme',
                        'label' => 'Theme',
                        'readonly' => true,
                        'rows' => 3,
                    ]);
                    ?>
                    <div class="cm-form-group">
                        <label for="cmMemoirePdf" class="cm-form-label">
                            Fichier PDF du mémoire <span class="cm-text-danger">*</span>
                        </label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="file" name="memoire_pdf" id="cmMemoirePdf" class="cm-form-control"
                                accept=".pdf,application/pdf" required style="display: none;">
                            <button type="button" class="cm-btn is-light" id="cmMemoirePdfBtn"
                                style="white-space: nowrap;">
                                <i class="fas fa-folder-open" aria-hidden="true"></i>
                                Parcourir...
                            </button>
                            <span id="cmMemoirePdfLabel" style="color: #6b7280; font-size: 0.875rem;"> Fichier non
                                sélectionné</span>
                        </div>
                        <small class="cm-form-help">Format accepté : PDF uniquement (max. 20 MB)</small>
                    </div>
                </div>

                <?php
                cm_component('crud/form-actions', [
                    'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']],
                    'actions' => [
                        ['label' => 'Réinitialiser', 'type' => 'button', 'class' => 'cm-btn is-secondary is-sm', 'attrs' => ['id' => 'cmMemoireResetBtn']],
                        ['label' => "Valider l'opération", 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm', 'attrs' => ['id' => 'cmMemoireSubmitBtn']],
                    ],
                ]);
                ?>
            </form>
        </div>

        <!-- Barre d'outils -->
        <div class="cm-barre-intermediaire">
            <div class="cm-toolbar cm-memoire-toolbar">
                <div class="cm-toolbar-left">
                    <label for="cmMemoireLimit"><strong>Afficher:</strong></label>
                    <select id="cmMemoireLimit" class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs"
                        data-cm-ajax-param="limit_memoire" data-cm-ajax-reset-param="page_memoire"
                        data-cm-ajax-reset-value="1">
                        <?php foreach ($allowedLimits as $limit): ?>
                            <option value="<?php echo $limit; ?>" <?php echo $limit === $perPage ? 'selected' : ''; ?>>
                                <?php echo $limit; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" id="cmMemoireSearch" class="cm-form-control cm-toolbar-field-lg"
                        placeholder="Rechercher un mémoire...">
                </div>

                <div class="cm-toolbar-center">
                    <button type="button" class="cm-btn is-secondary is-sm" id="cmMemoireSelectAllBtn"
                        data-select-all="1">
                        <i class="fas fa-square-check" aria-hidden="true"></i>
                        Tout sélectionner
                    </button>
                    <button type="button" class="cm-btn is-secondary is-sm" id="cmMemoireDeselectBtn"
                        data-deselect-all="1">
                        <i class="fas fa-square" aria-hidden="true"></i>
                        Tout désélectionner
                    </button>
                    <button type="button" class="cm-btn is-danger is-sm" id="cmMemoireDeleteBtn"
                        data-bulk-delete="1" disabled>
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        Supprimer (0)
                    </button>
                </div>

                <div class="cm-toolbar-right">
                    <button type="button" class="cm-btn is-secondary is-sm" id="cmMemoireExport"
                        data-export="1">
                        <i class="fas fa-file-export" aria-hidden="true"></i>
                        Exporter
                    </button>
                    <button type="button" class="cm-btn is-light is-sm" id="cmMemoirePrint"
                        data-print="1" onclick="window.print()">
                        <i class="fas fa-print" aria-hidden="true"></i>
                        Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Tableau récapitulatif -->
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmMemoireTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" id="cmMemoireCheckAll" class="cm-table-check-all" aria-label="Tout selectionner">
                            </th>
                            <th class="cm-data-table__th">N°</th>
                            <th class="cm-data-table__th">Nom &amp; Prénom Étudiant</th>
                            <th class="cm-data-table__th">Promotion</th>
                            <th class="cm-data-table__th">Thème</th>
                            <th class="cm-data-table__th">Fichier</th>
                            <th class="cm-data-table__th">Date dépôt</th>
                            <th class="cm-data-table__th">Taille (Mo)</th>
                            <th class="cm-data-table__th is-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmMemoireTableBody">
                        <?php if (empty($rowsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 9,
                                'title' => 'Aucun mémoire',
                                'message' => 'Aucun mémoire n\'a encore été mis en ligne.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $index => $memoire): ?>
                                <?php
                                $numEtu = (string) ($memoire['num_etu'] ?? '');
                                $searchText = strtolower(
                                    (string) ($memoire['nom_etudiant'] ?? '') . ' ' .
                                    (string) ($memoire['matricule'] ?? '') . ' ' .
                                    (string) ($memoire['theme'] ?? '') . ' ' .
                                    (string) ($memoire['promotion'] ?? '')
                                );
                                $dateDepot = !empty($memoire['date_depot'])
                                    ? date('d/m/Y H:i', strtotime((string) $memoire['date_depot']))
                                    : '-';
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="cm-data-table__td cm-data-table__td--check">
                                        <input type="checkbox" class="cm-table-check-row cm-memoire-check-row"
                                            value="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-label="Selectionner ligne <?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo (int) ($pagination['offset'] ?? 0) + $index + 1; ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($memoire['nom_etudiant'] ?? 'Etudiant'), ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small><?php echo htmlspecialchars((string) ($memoire['matricule'] ?? $numEtu), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($memoire['promotion'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <small><?php echo htmlspecialchars((string) ($memoire['theme'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <i class="fas fa-file-pdf cm-text-danger" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars((string) ($memoire['fichier'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($dateDepot, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($memoire['taille'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-table-actions">
                                            <button type="button" class="cm-btn-action is-edit cm-memoire-edit"
                                                data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Modifier">
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                            </button>
                                            <?php if (function_exists('canDelete') ? canDelete() : true): ?>
                                                <button type="button" class="cm-btn-action is-delete cm-memoire-delete-one"
                                                    data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Supprimer">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="cm-btn-action is-view cm-memoire-view"
                                                data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Télécharger">
                                                <i class="fas fa-download" aria-hidden="true"></i>
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
                'param_name' => 'page_memoire',
            ]);
            ?>
        </div>
    </div>
</div>

<script>
    (function () {
        const etudiants = <?php echo json_encode($etudiants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        const etudiantSelect = document.getElementById('cmMemoireEtudiantSelect');
        const numEtuInput = document.getElementById('cmMemoireNumEtu');
        const promotionInput = document.getElementById('cmMemoirePromotion');
        const themeInput = document.getElementById('cmMemoireTheme');
        const pdfInput = document.getElementById('cmMemoirePdf');
        const pdfBtn = document.getElementById('cmMemoirePdfBtn');
        const pdfLabel = document.getElementById('cmMemoirePdfLabel');
        const resetBtn = document.getElementById('cmMemoireResetBtn');
        const alertBox = document.getElementById('cmMemoireAlert');

        // Gérer le clic sur le bouton parcourir
        if (pdfBtn && pdfInput) {
            pdfBtn.addEventListener('click', function () {
                pdfInput.click();
            });
        }

        // Gérer le changement de fichier
        if (pdfInput && pdfLabel) {
            pdfInput.addEventListener('change', function () {
                if (pdfInput.files && pdfInput.files.length > 0) {
                    pdfLabel.textContent = pdfInput.files[0].name;
                    pdfLabel.style.color = '#111827';
                } else {
                    pdfLabel.textContent = 'fichier non sélectionné';
                    pdfLabel.style.color = '#6b7280';
                }
            });
        }

        const searchInput = document.getElementById('cmMemoireSearch');
        const tableBody = document.getElementById('cmMemoireTableBody');
        const exportBtn = document.getElementById('cmMemoireExport');
        const printBtn = document.getElementById('cmMemoirePrint');
        const checkAll = document.getElementById('cmMemoireCheckAll');
        const selectAllBtn = document.getElementById('cmMemoireSelectAllBtn');
        const deselectBtn = document.getElementById('cmMemoireDeselectBtn');
        const deleteBtn = document.getElementById('cmMemoireDeleteBtn');

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
            return Array.from(document.querySelectorAll('#cmMemoireTableBody .cm-data-table__row'));
        }

        function getVisibleRows() {
            return getRows().filter(function (row) {
                return row.style.display !== 'none';
            });
        }

        function getCheckedRows() {
            return getRows().filter(function (row) {
                const cb = row.querySelector('.cm-memoire-check-row');
                return cb && cb.checked;
            });
        }

        function updateDeleteState() {
            const checked = getCheckedRows();
            if (deleteBtn) {
                deleteBtn.disabled = checked.length === 0;
                deleteBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i> Supprimer (' + checked.length + ')';
            }
            if (checkAll) {
                const visible = getVisibleRows();
                const checkedVisible = visible.filter(function (row) {
                    const cb = row.querySelector('.cm-memoire-check-row');
                    return cb && cb.checked;
                });
                checkAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
            }
        }

        function getEtudiantByNumEtu(numEtu) {
            return (etudiants || []).find(function (item) {
                return String(item.num_etu || '') === String(numEtu || '');
            }) || null;
        }

        function fillEtudiantInfo(numEtu) {
            const info = getEtudiantByNumEtu(numEtu);
            if (!info) {
                if (promotionInput) promotionInput.value = '';
                if (themeInput) themeInput.value = '';
                return;
            }

            if (promotionInput) promotionInput.value = info.promotion || '';
            if (themeInput) themeInput.value = info.theme || '';
        }

        function deleteMemoire(numEtu) {
            const formData = new FormData();
            formData.append('action', 'supprimer_memoire');
            formData.append('num_etu', numEtu);

            const formToken = document.querySelector('#cmMemoireForm input[name="csrf_token"]');
            if (formToken && formToken.value) {
                formData.append('csrf_token', formToken.value);
            }

            return fetch('?page=mise_en_ligne_memoire', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(function (response) {
                return response.json();
            });
        }

        // Event: Changement d'étudiant
        if (etudiantSelect) {
            etudiantSelect.addEventListener('change', function () {
                const numEtu = etudiantSelect.value || '';
                if (numEtuInput) {
                    numEtuInput.value = numEtu;
                }
                fillEtudiantInfo(numEtu);
            });
        }

        // Event: Réinitialiser le formulaire
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (etudiantSelect) etudiantSelect.value = '';
                if (numEtuInput) numEtuInput.value = '';
                if (promotionInput) promotionInput.value = '';
                if (themeInput) themeInput.value = '';
                if (pdfInput) pdfInput.value = '';
                if (pdfLabel) {
                    pdfLabel.textContent = 'Aucun fichier';
                    pdfLabel.style.color = '#6b7280';
                }
            });
        }

        // Event: Recherche dans le tableau
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

        // Event: Change sur les checkboxes
        if (tableBody) {
            tableBody.addEventListener('change', function (event) {
                if (event.target && event.target.classList.contains('cm-memoire-check-row')) {
                    updateDeleteState();
                }
            });
        }

        // Event: Tout cocher/décocher
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-memoire-check-row');
                    if (cb) {
                        cb.checked = checkAll.checked;
                    }
                });
                updateDeleteState();
            });
        }

        // Event: Tout sélectionner
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-memoire-check-row');
                    if (cb) {
                        cb.checked = true;
                    }
                });
                updateDeleteState();
            });
        }

        // Event: Tout désélectionner
        if (deselectBtn) {
            deselectBtn.addEventListener('click', function () {
                getRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-memoire-check-row');
                    if (cb) {
                        cb.checked = false;
                    }
                });
                updateDeleteState();
            });
        }

        // Event: Télécharger un mémoire
        document.querySelectorAll('.cm-memoire-view').forEach(function (button) {
            button.addEventListener('click', function () {
                const numEtu = button.getAttribute('data-num-etu') || '';
                if (!numEtu) {
                    return;
                }
                const url = '?page=mise_en_ligne_memoire&action=telecharger&num_etu=' + encodeURIComponent(numEtu);
                window.open(url, '_blank');
            });
        });

        // Event: Supprimer un mémoire
        document.querySelectorAll('.cm-memoire-delete-one').forEach(function (button) {
            button.addEventListener('click', async function () {
                const numEtu = button.getAttribute('data-num-etu') || '';
                if (!numEtu) {
                    return;
                }

                if (!window.CM || !window.CM.confirm) {
                    if (!confirm('Supprimer ce mémoire ?')) {
                        return;
                    }
                } else {
                    const confirmed = await window.CM.confirm({
                        title: 'Suppression',
                        message: 'Supprimer ce mémoire ?',
                        type: 'danger',
                        confirmText: 'Supprimer',
                    });
                    if (!confirmed) {
                        return;
                    }
                }

                deleteMemoire(numEtu)
                    .then(function (payload) {
                        if (!payload || !payload.success) {
                            setAlert('error', payload && payload.message ? payload.message : 'Suppression impossible.');
                            return;
                        }
                        setAlert('success', payload.message || 'Mémoire supprimé.');
                        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(function () {
                        setAlert('error', 'Erreur réseau.');
                    });
            });
        });

        // Event: Suppression multiple
        if (deleteBtn) {
            deleteBtn.addEventListener('click', async function () {
                const nums = getCheckedRows().map(function (row) {
                    const cb = row.querySelector('.cm-memoire-check-row');
                    return cb ? cb.value : '';
                }).filter(Boolean);

                if (nums.length === 0) {
                    return;
                }

                if (!window.CM || !window.CM.confirm) {
                    if (!confirm('Supprimer ' + nums.length + ' mémoire(s) ?')) {
                        return;
                    }
                } else {
                    const confirmed = await window.CM.confirm({
                        title: 'Suppression multiple',
                        message: 'Supprimer ' + nums.length + ' mémoire(s) ?',
                        type: 'danger',
                        confirmText: 'Supprimer',
                    });
                    if (!confirmed) {
                        return;
                    }
                }

                Promise.all(nums.map(deleteMemoire))
                    .then(function (results) {
                        const failed = results.filter(function (item) {
                            return !item || !item.success;
                        });
                        if (failed.length > 0) {
                            setAlert('error', 'Certaines suppressions ont échoué.');
                        } else {
                            setAlert('success', 'Suppressions effectuées.');
                        }
                        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(function () {
                        setAlert('error', 'Erreur réseau.');
                    });
            });
        }

        // Event: Export CSV
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                const headers = ['N°', 'Etudiant', 'Promotion', 'Thème', 'Fichier', 'Date dépôt', 'Taille'];
                const csvRows = [headers.join(';')];

                getVisibleRows().forEach(function (row) {
                    const cells = row.querySelectorAll('.cm-data-table__td');
                    if (cells.length < 9) {
                        return;
                    }
                    const values = [
                        cells[1].innerText.trim(),
                        cells[2].innerText.trim().replace(/\s+/g, ' '),
                        cells[3].innerText.trim(),
                        cells[4].innerText.trim(),
                        cells[5].innerText.trim().replace(/\s+/g, ' '),
                        cells[6].innerText.trim(),
                        cells[7].innerText.trim()
                    ].map(function (value) {
                        return '"' + value.replace(/"/g, '""') + '"';
                    });
                    csvRows.push(values.join(';'));
                });

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'memoires_en_ligne.csv';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            });
        }

        // Event: Impression
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }

        // Validation du fichier PDF
        if (pdfInput) {
            pdfInput.addEventListener('change', function () {
                const file = pdfInput.files[0];
                if (!file) {
                    if (pdfLabel) {
                        pdfLabel.textContent = 'Aucun fichier';
                        pdfLabel.style.color = '#6b7280';
                    }
                    return;
                }

                // Vérifier l'extension
                if (!file.name.toLowerCase().endsWith('.pdf')) {
                    setAlert('error', 'Veuillez sélectionner un fichier PDF.');
                    pdfInput.value = '';
                    if (pdfLabel) {
                        pdfLabel.textContent = 'Aucun fichier';
                        pdfLabel.style.color = '#6b7280';
                    }
                    return;
                }

                // Vérifier la taille (20 MB max)
                const maxSize = 20 * 1024 * 1024; // 20 MB
                if (file.size > maxSize) {
                    setAlert('error', 'Le fichier est trop volumineux (max. 20 MB).');
                    pdfInput.value = '';
                    if (pdfLabel) {
                        pdfLabel.textContent = 'Aucun fichier';
                        pdfLabel.style.color = '#6b7280';
                    }
                    return;
                }
            });
        }

        // Gestion des erreurs de formulaire AJAX
        document.addEventListener('cm:ajax:form:error', function (event) {
            const payload = event && event.detail ? event.detail.payload : null;
            if (payload && payload.message) {
                setAlert('error', payload.message);
            }
        });

        // Gestion du succès de formulaire AJAX
        document.addEventListener('cm:ajax:form:success', function (event) {
            const payload = event && event.detail ? event.detail.payload : null;
            if (payload && payload.message) {
                setAlert('success', payload.message);
                // Réinitialiser le formulaire
                if (resetBtn) {
                    resetBtn.click();
                }
                // Recharger la page
                if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                    window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                } else {
                    setTimeout(function () {
                        window.location.reload();
                    }, 1500);
                }
            }
        });

        // Initialisation
        updateDeleteState();
        if (etudiantSelect && etudiantSelect.value) {
            if (numEtuInput) {
                numEtuInput.value = etudiantSelect.value;
            }
            fillEtudiantInfo(etudiantSelect.value);
        }
    })();
</script>
