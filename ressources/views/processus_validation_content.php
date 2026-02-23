<?php
require_once __DIR__ . '/../../app/controllers/ProcessusValidationController.php';

$controller = new ProcessusValidationController();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$resolveEnseignantId = static function (ProcessusValidationController $ctrl): ?int {
    $candidateKeys = ['id_enseignant', 'id_utilisateur', 'enseignant_id'];
    foreach ($candidateKeys as $key) {
        if (!empty($_SESSION[$key])) {
            $candidate = (int) $_SESSION[$key];
            if ($candidate > 0 && $ctrl->verifierIdEnseignant($candidate)) {
                return $candidate;
            }
        }
    }

    $payload = $ctrl->getDonneesPage();
    $membres = is_array($payload['membres_commission'] ?? null) ? $payload['membres_commission'] : [];
    if (!empty($membres[0]['id_enseignant'])) {
        return (int) $membres[0]['id_enseignant'];
    }
    return null;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['action'] ?? '') === 'finaliser') {
    $idRapport = (int) ($_POST['id_rapport'] ?? 0);
    $commentaire = trim((string) ($_POST['commentaire_validation'] ?? ''));
    $idEnseignant = $resolveEnseignantId($controller);

    if ($idRapport > 0 && $idEnseignant) {
        $result = $controller->finaliserRapport($idRapport, $idEnseignant, $commentaire !== '' ? $commentaire : null);
    } else {
        $result = [
            'success' => false,
            'message' => 'Impossible de finaliser: identifiant manquant.',
        ];
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($result);
        exit;
    }

    $_SESSION[$result['success'] ? 'success' : 'error'] = (string) ($result['message'] ?? '');
    header('Location: layout.php?page=processus_validation');
    exit;
}

$donnees = $controller->getDonneesPage();
$statistiques = is_array($donnees['statistiques'] ?? null) ? $donnees['statistiques'] : [];
$rapports = is_array($donnees['rapports'] ?? null) ? $donnees['rapports'] : [];
$membresCommission = is_array($donnees['membres_commission'] ?? null) ? $donnees['membres_commission'] : [];

$statusFilter = strtolower(trim((string) ($_GET['pv_status'] ?? 'all')));
if ($statusFilter === '') {
    $statusFilter = 'all';
}
$memberFilter = trim((string) ($_GET['pv_member'] ?? ''));

$filteredRapports = array_values(array_filter($rapports, static function (array $rapport) use ($statusFilter, $memberFilter): bool {
    $vote = is_array($rapport['statut_vote'] ?? null) ? $rapport['statut_vote'] : [];
    $statut = strtolower((string) ($vote['statut'] ?? ''));

    if ($statusFilter !== 'all' && $statusFilter !== '' && $statut !== $statusFilter) {
        return false;
    }

    if ($memberFilter !== '') {
        $found = false;
        $evaluations = is_array($rapport['evaluations'] ?? null) ? $rapport['evaluations'] : [];
        foreach ($evaluations as $evaluation) {
            $fullName = trim((string) ($evaluation['nom_enseignant'] ?? '') . ' ' . (string) ($evaluation['prenom_enseignant'] ?? ''));
            if (strcasecmp($fullName, $memberFilter) === 0) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return false;
        }
    }

    return true;
}));

$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_processus'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_processus'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($filteredRapports), $perPage, $currentPage)
    : [
        'total' => count($filteredRapports),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($filteredRapports, (int) ($pagination['offset'] ?? 0), $perPage);

$baseUrl = '?page=processus_validation'
    . '&pv_status=' . urlencode($statusFilter)
    . '&pv_member=' . urlencode($memberFilter)
    . '&limit_processus=' . $perPage;

$memberOptions = ['' => 'Tous'];
foreach ($membresCommission as $membre) {
    $label = trim((string) ($membre['nom_enseignant'] ?? '') . ' ' . (string) ($membre['prenom_enseignant'] ?? ''));
    if ($label !== '') {
        $memberOptions[$label] = $label;
    }
}
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

    <div id="cmProcessAlert"></div>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="cm-pole-superieur-title">
                <h2>
                    <i class="fas fa-list-check" aria-hidden="true"></i>
                    Suivi global de validation
                </h2>
            </div>

            <div class="cm-grid-4">
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['total_rapports'] ?? 0)),
                    'label' => 'Total approuves',
                    'icon' => 'fa-file-lines',
                    'color' => 'primary',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['en_cours'] ?? 0)),
                    'label' => 'En cours',
                    'icon' => 'fa-hourglass-half',
                    'color' => 'info',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['valides'] ?? 0)),
                    'label' => 'Valides',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['rejetes'] ?? 0)),
                    'label' => 'Rejetes',
                    'icon' => 'fa-xmark-circle',
                    'color' => 'warning',
                ]); ?>
            </div>

            <div class="cm-grid-2">
                <?php
                cm_component('form/select', [
                    'name' => 'cm_process_status',
                    'id' => 'cmProcessStatusFilter',
                    'label' => 'Filtre statut',
                    'options' => [
                        'all' => 'Tous',
                        'en_cours' => 'En cours',
                        'pret_a_finaliser' => 'Pret a finaliser',
                        'valide' => 'Valide',
                        'rejete' => 'Rejete',
                    ],
                    'selected' => $statusFilter,
                    'attrs' => [
                        'data-cm-ajax-param' => 'pv_status',
                        'data-cm-ajax-reset-param' => 'page_processus',
                        'data-cm-ajax-reset-value' => '1',
                    ],
                ]);

                cm_component('form/select', [
                    'name' => 'cm_process_member',
                    'id' => 'cmProcessMemberFilter',
                    'label' => 'Filtre membre',
                    'options' => $memberOptions,
                    'selected' => $memberFilter,
                    'attrs' => [
                        'data-cm-ajax-param' => 'pv_member',
                        'data-cm-ajax-reset-param' => 'page_processus',
                        'data-cm-ajax-reset-value' => '1',
                    ],
                ]);
                ?>
            </div>
        </div>

        <div class="cm-barre-intermediaire">
            <div class="cm-toolbar">
                <div class="cm-toolbar-left">
                    <label for="cmProcessLimit"><strong>Afficher:</strong></label>
                    <select id="cmProcessLimit"
                            class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs"
                            data-cm-ajax-param="limit_processus"
                            data-cm-ajax-reset-param="page_processus"
                            data-cm-ajax-reset-value="1">
                        <?php foreach ($allowedLimits as $limit): ?>
                            <option value="<?php echo $limit; ?>" <?php echo $limit === $perPage ? 'selected' : ''; ?>>
                                <?php echo $limit; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cm-toolbar-center">
                    <input type="text" id="cmProcessSearch" class="cm-form-control" placeholder="Rechercher un rapport ou etudiant...">
                </div>
                <div class="cm-toolbar-right">
                    <button type="button" class="cm-btn is-info is-sm" id="cmProcessExport">
                        <i class="fas fa-file-export" aria-hidden="true"></i>
                        Exporter
                    </button>
                    <button type="button" class="cm-btn is-info is-sm" id="cmProcessPrint">
                        <i class="fas fa-print" aria-hidden="true"></i>
                        Imprimer
                    </button>
                </div>
            </div>
        </div>

        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmProcessTable">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th">Rapport</th>
                        <th class="cm-data-table__th">Etudiant</th>
                        <th class="cm-data-table__th">Promotion</th>
                        <th class="cm-data-table__th">Statut global</th>
                        <th class="cm-data-table__th">Votes</th>
                        <th class="cm-data-table__th">Date approb.</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="cmProcessTableBody">
                    <?php if (empty($rowsToShow)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 7,
                            'title' => 'Aucun rapport',
                            'message' => 'Aucune ligne disponible pour ces filtres.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($rowsToShow as $rapport): ?>
                            <?php
                            $vote = is_array($rapport['statut_vote'] ?? null) ? $rapport['statut_vote'] : [];
                            $statut = strtolower((string) ($vote['statut'] ?? 'en_cours'));
                            if ($statut === 'valide') {
                                $badgeType = 'success';
                                $statutLabel = 'Valide';
                            } elseif ($statut === 'rejete') {
                                $badgeType = 'warning';
                                $statutLabel = 'Rejete';
                            } elseif ($statut === 'pret_a_finaliser') {
                                $badgeType = 'info';
                                $statutLabel = 'Pret a finaliser';
                            } else {
                                $badgeType = 'info';
                                $statutLabel = 'En cours';
                            }

                            $idRapport = (int) ($rapport['id_rapport'] ?? 0);
                            $etudiant = trim((string) ($rapport['nom_etu'] ?? '') . ' ' . (string) ($rapport['prenom_etu'] ?? ''));
                            $searchText = strtolower((string) ($rapport['nom_rapport'] ?? '') . ' ' . $etudiant . ' ' . (string) ($rapport['theme_rapport'] ?? ''));
                            $dateApprob = !empty($rapport['date_approv']) ? date('d/m/Y H:i', strtotime((string) $rapport['date_approv'])) : '-';
                            $votesText = (int) ($vote['votes_valider'] ?? 0) . ' val. / ' . (int) ($vote['votes_rejeter'] ?? 0) . ' rej. (' . (int) ($vote['total_votes'] ?? 0) . '/4)';
                            $canFinalize = !empty($vote['total_votes']) && (int) $vote['total_votes'] >= 4 && empty($vote['finalise']);
                            ?>
                            <tr class="cm-data-table__row" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($rapport['nom_rapport'] ?? 'Rapport'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($etudiant, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($rapport['promotion_etu'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td">
                                    <?php cm_component('ui/badge', ['text' => $statutLabel, 'type' => $badgeType]); ?>
                                </td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($votesText, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($dateApprob, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions" style="justify-content:center;">
                                        <a class="cm-btn-action is-view"
                                           href="?page=evaluation_dossiers&detail=<?php echo urlencode((string) $idRapport); ?>"
                                           title="Consulter">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <?php if ($canFinalize && (function_exists('canEdit') ? canEdit() : true)): ?>
                                            <form method="POST"
                                                  action="?page=processus_validation"
                                                  data-cm-ajax-form="true"
                                                  class="cm-inline-finalize-form"
                                                  style="display:inline-flex; align-items:center; gap:6px;">
                                                <?php cm_component('form/csrf-token'); ?>
                                                <input type="hidden" name="action" value="finaliser">
                                                <input type="hidden" name="id_rapport" value="<?php echo $idRapport; ?>">
                                                <input type="text"
                                                       name="commentaire_validation"
                                                       class="cm-form-control is-sm"
                                                       placeholder="Commentaire"
                                                       style="width:120px;">
                                                <button type="submit" class="cm-btn is-success is-sm">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    Finaliser
                                                </button>
                                            </form>
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
                'param_name' => 'page_processus',
            ]);
            ?>
        </div>
    </div>
</div>

<script>
(function () {
    const tableRows = Array.from(document.querySelectorAll('#cmProcessTableBody .cm-data-table__row'));
    const searchInput = document.getElementById('cmProcessSearch');
    const exportBtn = document.getElementById('cmProcessExport');
    const printBtn = document.getElementById('cmProcessPrint');
    const alertBox = document.getElementById('cmProcessAlert');

    function setAlert(type, message) {
        if (!alertBox) {
            return;
        }
        const cssType = type === 'success' ? 'success' : 'danger';
        alertBox.innerHTML = '<div class="cm-alert is-' + cssType + '"><div class="cm-alert__content"><span class="cm-alert__message">' +
            String(message || '').replace(/[<>&]/g, '') +
            '</span></div></div>';
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = (searchInput.value || '').trim().toLowerCase();
            tableRows.forEach(function (row) {
                const text = row.getAttribute('data-search') || '';
                row.style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['Rapport', 'Etudiant', 'Promotion', 'Statut', 'Votes', 'Date approbation'];
            const csvRows = [headers.join(';')];

            tableRows.forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                const cols = row.querySelectorAll('.cm-data-table__td');
                if (cols.length < 6) {
                    return;
                }
                const line = [
                    cols[0].innerText.trim(),
                    cols[1].innerText.trim(),
                    cols[2].innerText.trim(),
                    cols[3].innerText.trim(),
                    cols[4].innerText.trim(),
                    cols[5].innerText.trim()
                ].map(function (v) {
                    return '"' + v.replace(/"/g, '""') + '"';
                });
                csvRows.push(line.join(';'));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'processus_validation.csv';
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

    document.addEventListener('cm:ajax:form:error', function (event) {
        const payload = event && event.detail ? event.detail.payload : null;
        if (payload && payload.message) {
            setAlert('error', payload.message);
        }
    });
})();
</script>
