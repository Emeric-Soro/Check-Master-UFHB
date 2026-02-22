<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/EvaluationDossiersController.php';
$controller = new EvaluationDossiersController(Database::getConnection());
$currentPageSlug = (string) ($_GET['page'] ?? 'evaluation_dossiers');
if (isset($_GET['action']) && $_GET['action'] === 'traiter_decision' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $_POST['action'] = 'traiter_decision';
    $controller->traiterAction();
    exit;
}
$routeStats = is_array($stats ?? null) ? $stats : null;
$routeDossiers = is_array($dossiers ?? null) ? $dossiers : null;
$data = $controller->index();
$stats = $routeStats ?? (is_array($data['stats'] ?? null) ? $data['stats'] : []);
$dossiers = $routeDossiers ?? (is_array($data['dossiers'] ?? null) ? $data['dossiers'] : []);
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writableYearLabel = \AcademicYear::getWritableLabelFromSession();
$academicYearLabels = [];
foreach (\AcademicYear::fetchAll(Database::getConnection()) as $academicYear) {
    $academicYearLabels[(int) ($academicYear['id'] ?? 0)] = (string) ($academicYear['label'] ?? '');
}
$selectedDetailId = (int) ($_GET['detail'] ?? 0);
$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_eval_dossiers'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_eval_dossiers'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($dossiers), $perPage, $currentPage)
    : [
        'total' => count($dossiers),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($dossiers, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=' . urlencode($currentPageSlug) . '&limit_eval_dossiers=' . $perPage;
$dossierOptions = [];
$dossierIds = [];
foreach ($dossiers as $dossier) {
    $id = (int) ($dossier['id_rapport'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $dossierIds[] = $id;
    $dossierLabel = '#' . $id . ' - ' . trim((string) ($dossier['prenom_etu'] ?? '') . ' ' . (string) ($dossier['nom_etu'] ?? ''));
    if ($allYearsSelected) {
        $promotionLabel = trim((string) ($dossier['promotion_etu'] ?? ''));
        if ($promotionLabel === '' && !empty($dossier['id_annee_acad'])) {
            $promotionLabel = $academicYearLabels[(int) $dossier['id_annee_acad']] ?? '';
        }
        if ($promotionLabel !== '') {
            $dossierLabel .= ' - ' . $promotionLabel;
        }
    }
    $dossierOptions[$id] = $dossierLabel;
}
$myEvaluationsByRapport = [];
try {
    $pdo = Database::getConnection();
    $enseignantId = 0;
    $idUtilisateur = (int) ($_SESSION['id_utilisateur'] ?? 0);
    if ($idUtilisateur > 0) {
        $stmtUser = $pdo->prepare('SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?');
        $stmtUser->execute([$idUtilisateur]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if (!empty($userRow['login_utilisateur'])) {
            $stmtEns = $pdo->prepare('SELECT id_enseignant FROM enseignants WHERE mail_enseignant = ? LIMIT 1');
            $stmtEns->execute([(string) $userRow['login_utilisateur']]);
            $ensRow = $stmtEns->fetch(PDO::FETCH_ASSOC);
            $enseignantId = (int) ($ensRow['id_enseignant'] ?? 0);
        }
    }
    if ($enseignantId > 0 && !empty($dossierIds)) {
        $placeholders = implode(',', array_fill(0, count($dossierIds), '?'));
        $params = array_merge([$enseignantId], $dossierIds);
        $sql = "
            SELECT
                id_rapport,
                decision_evaluation,
                commentaire,
                COALESCE(date_modification, date_evaluation) AS date_eval
            FROM evaluations_rapports
            WHERE id_evaluateur = ?
            AND id_rapport IN ($placeholders)
        ";
        $stmtEval = $pdo->prepare($sql);
        $stmtEval->execute($params);
        foreach ($stmtEval->fetchAll(PDO::FETCH_ASSOC) as $evalRow) {
            $myEvaluationsByRapport[(int) ($evalRow['id_rapport'] ?? 0)] = $evalRow;
        }
    }
} catch (Throwable $e) {
    $myEvaluationsByRapport = [];
}
$aTraiter = (int) ($stats['a_evaluer'] ?? 0);
$valides = (int) ($stats['valides'] ?? 0);
$rejetes = (int) ($stats['a_corriger'] ?? 0);
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php if (!empty($_SESSION['success'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $_SESSION['success']]); ?>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $_SESSION['error']]); ?>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <div id="cmEvalDecisionAlert"></div>
    <?php if ($allYearsSelected): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'info',
            'message' => "Affichage global sur toutes les années. Les décisions restent limitées à l'année active {$writableYearLabel}.",
        ]); ?>
    <?php endif; ?>
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="">
            </div>
            <div class="cm-grid-3">
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary">A TRAITER</div>
                    <div style="font-size:1.6rem;font-weight:700;"><?php echo $aTraiter; ?></div>
                </div>
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary">VALIDÉS</div>
                    <div style="font-size:1.6rem;font-weight:700;"><?php echo $valides; ?></div>
                </div>
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary">REJETÉS</div>
                    <div style="font-size:1.6rem;font-weight:700;"><?php echo $rejetes; ?></div>
                </div>
            </div>
            <form id="cmEvaluationDecisionForm"
                  method="POST"
                  action="?page=<?php echo htmlspecialchars(urlencode($currentPageSlug), ENT_QUOTES, 'UTF-8'); ?>&action=traiter_decision">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="action" value="traiter_decision">
                <div class="cm-grid-2">
                    <?php
                    cm_component('form/select', [
                        'name' => 'id_rapport',
                        'id' => 'cmDecisionRapport',
                        'label' => 'Rapport a evaluer',
                        'required' => true,
                        'options' => $dossierOptions,
                        'selected' => $selectedDetailId > 0 ? (string) $selectedDetailId : '',
                    ]);
                    cm_component('form/select', [
                        'name' => 'decision',
                        'id' => 'cmDecisionChoice',
                        'label' => 'Decision',
                        'required' => true,
                        'options' => [
                            'valider' => 'Valider',
                            'rejeter' => 'Rejeter',
                        ],
                    ]);
                    ?>
                </div>
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'cm_etudiant_info',
                        'id' => 'cmDecisionEtudiant',
                        'label' => 'Etudiant',
                        'readonly' => true,
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_theme_info',
                        'id' => 'cmDecisionTheme',
                        'label' => 'Theme',
                        'readonly' => true,
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_statut_info',
                        'id' => 'cmDecisionStatut',
                        'label' => 'Statut actuel',
                        'readonly' => true,
                    ]);
                    ?>
                </div>
                <?php
                cm_component('form/textarea', [
                    'name' => 'commentaire',
                    'id' => 'cmDecisionCommentaire',
                    'label' => 'Commentaire (obligatoire si rejet)',
                    'rows' => 4,
                    'placeholder' => 'Saisissez votre commentaire...',
                ]);
                ?>
                <div class="cm-form-buttons">
                    <a id="cmVoirRapportBtn"
                       class="cm-btn is-info"
                       href="#"
                       target="_blank"
                       rel="noopener">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                        Voir rapport
                    </a>
                    <button class="cm-btn is-success" type="submit">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Soumettre decision
                    </button>
                </div>
            </form>
        </div>
        <div class="cm-barre-intermediaire">
            <div class="cm-toolbar">
                <div class="cm-toolbar-left">
                    <label for="cmEvalLimit"><strong>Afficher:</strong></label>
                    <select id="cmEvalLimit"
                            class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs"
                            data-cm-ajax-param="limit_eval_dossiers"
                            data-cm-ajax-reset-param="page_eval_dossiers"
                            data-cm-ajax-reset-value="1">
                        <?php foreach ($allowedLimits as $limit): ?>
                            <option value="<?php echo $limit; ?>" <?php echo $limit === $perPage ? 'selected' : ''; ?>>
                                <?php echo $limit; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="cmEvalSearch" class="cm-form-control cm-toolbar-field-lg" placeholder="Rechercher un dossier...">
                </div>
                <div class="cm-toolbar-center">
                    <button type="button" class="cm-btn is-info is-sm" id="cmEvalSelectAllBtn">
                        <i class="fas fa-square-check" aria-hidden="true"></i>
                        Select. tout
                    </button>
                    <button type="button" class="cm-btn is-light is-sm" id="cmEvalDeselectBtn">
                        <i class="fas fa-square" aria-hidden="true"></i>
                        Deselect.
                    </button>
                    <?php if (function_exists('canDelete') ? canDelete() : true): ?>
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        Supprimer (0)
                    <?php endif; ?>
                </div>
                <div class="cm-toolbar-right">
                    <?php if (function_exists('canView') ? canView() : true): ?>
                        <i class="fas fa-file-export" aria-hidden="true"></i>
                        Export
                    <?php endif; ?>
                    <?php if (function_exists('canView') ? canView() : true): ?>
                        <i class="fas fa-print" aria-hidden="true"></i>
                        Impr.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmEvaluationDossiersTable">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th cm-data-table__th--check">
                            <input type="checkbox" id="cmEvalCheckAll" aria-label="Tout sélectionner">
                        </th>
                        <th class="cm-data-table__th">N Rap</th>
                        <th class="cm-data-table__th">Etudiant</th>
                        <th class="cm-data-table__th">Promotion</th>
                        <th class="cm-data-table__th">Theme</th>
                        <th class="cm-data-table__th">Ma decision</th>
                        <th class="cm-data-table__th">Mon commentaire</th>
                        <th class="cm-data-table__th">Date</th>
                        <th class="cm-data-table__th is-center">Act</th>
                    </tr>
                    </thead>
                    <tbody id="cmEvaluationDossiersBody">
                    <?php if (empty($rowsToShow)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 9,
                            'title' => '',
                            'message' => 'Aucun dossier a afficher.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($rowsToShow as $dossier): ?>
                            <?php
                            $idRapport = (int) ($dossier['id_rapport'] ?? 0);
                            $etudiant = trim((string) ($dossier['prenom_etu'] ?? '') . ' ' . (string) ($dossier['nom_etu'] ?? ''));
                            $theme = (string) ($dossier['theme_rapport'] ?? '');
                            $searchText = strtolower((string) ($dossier['nom_rapport'] ?? '') . ' ' . $etudiant . ' ' . $theme);
                            $promotionLabel = trim((string) ($dossier['promotion_etu'] ?? ''));
                            if ($promotionLabel === '' && !empty($dossier['id_annee_acad'])) {
                                $promotionLabel = $academicYearLabels[(int) $dossier['id_annee_acad']] ?? '-';
                            }
                            $myEval = $myEvaluationsByRapport[$idRapport] ?? null;
                            $myDecision = strtolower((string) ($myEval['decision_evaluation'] ?? ''));
                            $myComment = trim((string) ($myEval['commentaire'] ?? ''));
                            $myDate = !empty($myEval['date_eval']) ? date('d/m/Y', strtotime((string) $myEval['date_eval'])) : '-';
                            if ($myDecision === 'valider') {
                                $decisionLabel = 'Validé';
                                $decisionType = 'success';
                            } elseif ($myDecision === 'rejeter') {
                                $decisionLabel = 'Rejeté';
                                $decisionType = 'danger';
                            } else {
                                $decisionLabel = '-';
                                $decisionType = 'light';
                            }
                            $etape = strtolower((string) ($dossier['etape_validation'] ?? ''));
                            if ($etape === 'valide') {
                                $statutLabel = 'Validé';
                            } elseif ($etape === 'desapprouve_commission') {
                                $statutLabel = 'A corriger';
                            } elseif ($etape === 'approuve_communication') {
                                $statutLabel = 'Nouveau';
                            } else {
                                $statutLabel = $etape !== '' ? ucfirst($etape) : 'En attente';
                            }
                            ?>
                            <tr class="cm-data-table__row"
                                data-id-rapport="<?php echo $idRapport; ?>"
                                data-etudiant="<?php echo htmlspecialchars($etudiant, ENT_QUOTES, 'UTF-8'); ?>"
                                data-theme="<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>"
                                data-statut="<?php echo htmlspecialchars($statutLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="cm-data-table__td cm-data-table__td--check">
                                    <input type="checkbox" class="cm-eval-check-row" value="<?php echo $idRapport; ?>" aria-label="Sélectionner dossier <?php echo $idRapport; ?>">
                                </td>
                                <td class="cm-data-table__td">#<?php echo $idRapport; ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($etudiant, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($promotionLabel !== '' ? $promotionLabel : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td">
                                    <?php cm_component('ui/badge', ['text' => $decisionLabel, 'type' => $decisionType]); ?>
                                </td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($myComment !== '' ? $myComment : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($myDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions">
                                        <?php if (function_exists('canEdit') ? canEdit() : true): ?>
                                        <button type="button"
                                                class="cm-btn-action is-edit cm-btn-evaluer-dossier"
                                                data-id-rapport="<?php echo $idRapport; ?>"
                                                title="Evaluer">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (function_exists('canView') ? canView() : true): ?>
                                        <a class="cm-btn-action is-view"
                                           href="?page=evaluations_dossiers_soutenance&fichier=<?php echo urlencode((string) $idRapport); ?>"
                                           target="_blank"
                                           rel="noopener"
                                           title="Voir rapport">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
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
                'param_name' => 'page_eval_dossiers',
            ]);
            ?>
        </div>
    </div>
</div>
<script>
(function () {
    const decisionForm = document.getElementById('cmEvaluationDecisionForm');
    const rapportSelect = document.getElementById('cmDecisionRapport');
    const decisionSelect = document.getElementById('cmDecisionChoice');
    const commentaireInput = document.getElementById('cmDecisionCommentaire');
    const etudiantField = document.getElementById('cmDecisionEtudiant');
    const themeField = document.getElementById('cmDecisionTheme');
    const statutField = document.getElementById('cmDecisionStatut');
    const viewBtn = document.getElementById('cmVoirRapportBtn');
    const alertBox = document.getElementById('cmEvalDecisionAlert');
    const searchInput = document.getElementById('cmEvalSearch');
    const exportBtn = document.getElementById('cmEvalExport');
    const printBtn = document.getElementById('cmEvalPrint');
    const checkAll = document.getElementById('cmEvalCheckAll');
    const selectAllBtn = document.getElementById('cmEvalSelectAllBtn');
    const deselectBtn = document.getElementById('cmEvalDeselectBtn');
    const deleteBtn = document.getElementById('cmEvalDeleteBtn');
    function getRows() {
        return Array.from(document.querySelectorAll('#cmEvaluationDossiersBody .cm-data-table__row'));
    }
    function getVisibleRows() {
        return getRows().filter(function (row) {
            return row.style.display !== 'none';
        });
    }
    function getCheckedRows() {
        return getRows().filter(function (row) {
            const cb = row.querySelector('.cm-eval-check-row');
            return cb && cb.checked;
        });
    }
    function setAlert(type, message) {
        if (!alertBox) {
            return;
        }
        const cssType = type === 'success' ? 'success' : 'danger';
        alertBox.innerHTML = '<div class="cm-alert is-' + cssType + '"><div class="cm-alert__content"><span class="cm-alert__message">' +
            String(message || '').replace(/[<>&]/g, '') +
            '</span></div></div>';
    }
    function syncSelectedRapport() {
        if (!rapportSelect) {
            return;
        }
        const selectedId = rapportSelect.value;
        const row = getRows().find(function (tr) {
            return String(tr.getAttribute('data-id-rapport')) === String(selectedId);
        });
        if (!row) {
            if (etudiantField) etudiantField.value = '';
            if (themeField) themeField.value = '';
            if (statutField) statutField.value = '';
            if (viewBtn) viewBtn.setAttribute('href', '#');
            return;
        }
        if (etudiantField) etudiantField.value = row.getAttribute('data-etudiant') || '';
        if (themeField) themeField.value = row.getAttribute('data-theme') || '';
        if (statutField) statutField.value = row.getAttribute('data-statut') || '';
        if (viewBtn) {
            viewBtn.setAttribute('href', '?page=evaluations_dossiers_soutenance&fichier=' + encodeURIComponent(selectedId));
        }
    }
    function updateDeleteState() {
        const count = getCheckedRows().length;
        if (deleteBtn) {
            deleteBtn.disabled = count === 0;
            deleteBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i> Supprimer (' + count + ')';
        }
        if (checkAll) {
            const visible = getVisibleRows();
            const checkedVisible = visible.filter(function (row) {
                const cb = row.querySelector('.cm-eval-check-row');
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
        updateDeleteState();
    }
    if (rapportSelect) {
        rapportSelect.addEventListener('change', syncSelectedRapport);
        syncSelectedRapport();
    }
    document.querySelectorAll('.cm-btn-evaluer-dossier').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!rapportSelect) {
                return;
            }
            rapportSelect.value = btn.getAttribute('data-id-rapport') || '';
            syncSelectedRapport();
            if (decisionSelect) {
                decisionSelect.focus();
            }
        });
    });
    document.addEventListener('change', function (event) {
        if (event.target && event.target.classList.contains('cm-eval-check-row')) {
            updateDeleteState();
        }
    });
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            getVisibleRows().forEach(function (row) {
                const cb = row.querySelector('.cm-eval-check-row');
                if (cb) {
                    cb.checked = checkAll.checked;
                }
            });
            updateDeleteState();
        });
    }
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            getVisibleRows().forEach(function (row) {
                const cb = row.querySelector('.cm-eval-check-row');
                if (cb) {
                    cb.checked = true;
                }
            });
            updateDeleteState();
        });
    }
    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            getRows().forEach(function (row) {
                const cb = row.querySelector('.cm-eval-check-row');
                if (cb) {
                    cb.checked = false;
                }
            });
            updateDeleteState();
        });
    }
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            getCheckedRows().forEach(function (row) {
                row.remove();
            });
            updateDeleteState();
            syncSelectedRapport();
        });
    }
    if (decisionForm) {
        decisionForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const decision = decisionSelect ? decisionSelect.value : '';
            const commentaire = commentaireInput ? commentaireInput.value.trim() : '';
            if (decision === 'rejeter' && commentaire === '') {
                setAlert('error', 'Le commentaire est obligatoire pour un rejet.');
                if (commentaireInput) {
                    commentaireInput.focus();
                }
                return;
            }
            const formData = new FormData(decisionForm);
            fetch(decisionForm.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (payload && payload.success) {
                        setAlert('success', payload.message || 'Decision enregistree.');
                        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                        }
                        return;
                    }
                    setAlert('error', (payload && payload.message) ? payload.message : 'Erreur lors de la soumission.');
                })
                .catch(function () {
                    setAlert('error', 'Erreur reseau lors de la soumission.');
                });
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', applySearch);
    }
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['N Rap', 'Etudiant', 'Theme', 'Decision', 'Commentaire', 'Date'];
            const csvRows = [headers.join(';')];
            getVisibleRows().forEach(function (row) {
                const cols = row.querySelectorAll('.cm-data-table__td');
                if (cols.length < 7) {
                    return;
                }
                const values = [
                    cols[1].innerText.trim(),
                    cols[2].innerText.trim(),
                    cols[3].innerText.trim(),
                    cols[4].innerText.trim(),
                    cols[5].innerText.trim(),
                    cols[6].innerText.trim()
                ].map(function (value) {
                    return '"' + value.replace(/"/g, '""') + '"';
                });
                csvRows.push(values.join(';'));
            });
            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'mes_evaluations_commission.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        });
    }
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
    applySearch();
})();
</script>
