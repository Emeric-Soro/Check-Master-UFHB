<?php
require_once __DIR__ . '/../../app/controllers/ProcessusValidationController.php';
$controller = new ProcessusValidationController();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$resolveEnseignantId = static function (ProcessusValidationController $ctrl): ?string {
    $resolved = $ctrl->resolveEnseignantIdFromSession($_SESSION);
    if ($resolved !== null && trim((string) $resolved) !== '') {
        return trim((string) $resolved);
    }

    $idUtilisateur = (int) ($_SESSION['id_utilisateur'] ?? 0);
    if ($idUtilisateur > 0) {
        $resolved = $ctrl->resoudreIdEnseignantDepuisUtilisateur($idUtilisateur);
        if ($resolved !== null && trim((string) $resolved) !== '') {
            return trim((string) $resolved);
        }
    }

    return null;
};
$requestedAction = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $requestedAction === 'finaliser') {
    $idRapport = (int) ($_POST['id_rapport'] ?? 0);
    $commentaire = trim((string) ($_POST['commentaire_validation'] ?? ''));
    $idEnseignant = $resolveEnseignantId($controller);
    if ($idRapport > 0) {
        $result = $controller->finaliserRapport($idRapport, $idEnseignant, $commentaire !== '' ? $commentaire : null);
    } else {
        $result = [
            'success' => false,
            'message' => 'Impossible de finaliser: rapport introuvable.',
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
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $requestedAction === 'vote_groupe_admin') {
    $idRapport = (int) ($_POST['id_rapport'] ?? 0);
    $idUtilisateur = (int) ($_SESSION['id_utilisateur'] ?? 0);
    $result = $controller->appliquerVoteAdminAuxMembres($idRapport, $idUtilisateur, $_SESSION);
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
    <style>
        .cm-process-votes {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 190px;
        }
        .cm-process-votes__summary {
            font-weight: 600;
            white-space: nowrap;
        }
        .cm-process-votes__list {
            display: flex;
            flex-wrap: wrap;
            gap: 3px 8px;
            color: var(--cm-text-muted, #6b7280);
            font-size: 0.78rem;
            line-height: 1.25;
        }
        .cm-process-vote {
            white-space: nowrap;
        }
        .cm-process-vote.is-valid {
            color: var(--cm-success, #16a34a);
        }
        .cm-process-vote.is-rejected {
            color: var(--cm-danger, #dc2626);
        }
    </style>
    <div id="cmProcessAlert"></div>
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="">
            </div>
            <div class="cm-grid-4">
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['total_rapports'] ?? 0)),
                    'label' => 'Total approuves',
                    'icon' => 'fa-file-lines',
                    'color' => 'primary',
                    'url' => '?page=processus_validation&pv_status=all'
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['en_cours'] ?? 0)),
                    'label' => 'En cours',
                    'icon' => 'fa-hourglass-half',
                    'color' => 'info',
                    'url' => '?page=processus_validation&pv_status=en_cours'
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['valides'] ?? 0)),
                    'label' => 'Validés',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'url' => '?page=processus_validation&pv_status=valide'
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($statistiques['rejetes'] ?? 0)),
                    'label' => 'Rejetés',
                    'icon' => 'fa-xmark-circle',
                    'color' => 'warning',
                    'url' => '?page=processus_validation&pv_status=rejete'
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
                        'valide' => 'Validé',
                        'rejete' => 'Rejeté',
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
        <?php cm_toolbar([
            'screen' => 'processus_validation',
            'id_prefix' => 'cmProcess',
            'search_value' => $_GET['search'] ?? '',
            'limit' => $perPage,
            'allowed_limits' => $allowedLimits,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmProcessTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Nom &amp; Prénom</th>
                            <th class="cm-data-table__th">Statut global</th>
                            <th class="cm-data-table__th">Votes</th>
                            <th class="cm-data-table__th">Date approbation</th>
                            <th class="cm-data-table__th is-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmProcessTableBody">
                        <?php if (empty($rowsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 5,
                                'title' => '',
                                'message' => 'Aucune ligne disponible pour ces filtres.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $rapport): ?>
                                <?php
                                $vote = is_array($rapport['statut_vote'] ?? null) ? $rapport['statut_vote'] : [];
                                $statut = strtolower((string) ($vote['statut'] ?? 'en_cours'));
                                if ($statut === 'valide') {
                                    $badgeType = 'success';
                                    $statutLabel = 'Validé';
                                } elseif ($statut === 'rejete') {
                                    $badgeType = 'warning';
                                    $statutLabel = 'Rejeté';
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
                                $votesValider = (int) ($vote['votes_valider'] ?? 0);
                                $votesRejeter = (int) ($vote['votes_rejeter'] ?? 0);
                                $totalVotes = (int) ($vote['total_votes'] ?? 0);
                                $canFinalize = !empty($vote['total_votes']) && (int) $vote['total_votes'] >= 4 && empty($vote['finalise']);
                                $adminUserId = (int) ($_SESSION['id_utilisateur'] ?? 0);
                                $hasAdminVote = false;
                                foreach ((array) ($rapport['evaluations'] ?? []) as $evaluation) {
                                    if ((int) ($evaluation['id_evaluateur'] ?? 0) === $adminUserId) {
                                        $hasAdminVote = true;
                                        break;
                                    }
                                }
                                $canAdminGroupVote = (function_exists('isAdmin') ? isAdmin() : false)
                                    && $hasAdminVote
                                    && empty($vote['finalise'])
                                    && (int) ($vote['total_votes'] ?? 0) > 0
                                    && (int) ($vote['total_votes'] ?? 0) < 4;
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="cm-data-table__td">
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($etudiant, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size: 0.85em; color: var(--cm-text-muted); margin-top: 3px;">
                                            Promotion : <?php echo htmlspecialchars(\FormattingUtils::formatPromotion((string) ($rapport['promotion_etu'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php cm_component('ui/badge', ['text' => $statutLabel, 'type' => $badgeType]); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="cm-process-votes">
                                            <span class="cm-process-votes__summary">
                                                <?php echo $votesValider; ?> validé<?php echo $votesValider > 1 ? 's' : ''; ?>
                                                <span aria-hidden="true">·</span>
                                                <?php echo $votesRejeter; ?> rejeté<?php echo $votesRejeter > 1 ? 's' : ''; ?>
                                                <span aria-hidden="true">(<?php echo $totalVotes; ?>/4)</span>
                                            </span>
                                            <?php if (!empty($rapport['evaluations'])): ?>
                                                <div class="cm-process-votes__list" aria-label="Personnes ayant voté">
                                                    <?php 
                                                    // Trier par ordre chronologique (du plus ancien vote au plus récent)
                                                    $evaluationsChronologiques = array_reverse((array) $rapport['evaluations']);
                                                    foreach ($evaluationsChronologiques as $index => $evaluation): 
                                                    ?>
                                                        <?php
                                                        $votant = trim((string) ($evaluation['nom_enseignant'] ?? '') . ' ' . (string) ($evaluation['prenom_enseignant'] ?? ''));
                                                        if ($votant === '') {
                                                            $votant = trim((string) ($evaluation['login_utilisateur'] ?? 'Membre de la commission'));
                                                        }
                                                        $decision = strtolower((string) ($evaluation['decision_evaluation'] ?? ''));
                                                        $decisionLabel = $decision === 'valider' ? '✓' : ($decision === 'rejeter' ? '✕' : '•');
                                                        $decisionClass = $decision === 'valider' ? 'is-valid' : ($decision === 'rejeter' ? 'is-rejected' : '');
                                                        ?>
                                                        <span class="cm-process-vote <?php echo $decisionClass; ?>" title="<?php echo htmlspecialchars($decision === 'valider' ? 'Validation' : ($decision === 'rejeter' ? 'Rejet' : 'Vote'), ENT_QUOTES, 'UTF-8'); ?>">
                                                            <span style="font-weight: bold; opacity: 0.8; margin-right: 2px; color: var(--cm-text-muted, #6b7280);"><?php echo $index + 1; ?>.</span><?php echo $decisionLabel; ?> <?php echo htmlspecialchars($votant, ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="cm-process-votes__list">Aucun votant</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($dateApprob, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-table-actions" style="justify-content:center;">
                                            <a class="cm-btn-action is-view"
                                                href="?page=evaluation_dossiers&detail=<?php echo urlencode((string) $idRapport); ?>"
                                                title="Voir détails">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                            <button type="button" class="cm-btn-action is-view" title="Voir rapport"
                                                onclick="CM.openDocViewer('rapport', '<?php echo htmlspecialchars((string) $idRapport, ENT_QUOTES, 'UTF-8'); ?>', {title: 'Rapport #<?php echo htmlspecialchars((string) $idRapport, ENT_QUOTES, 'UTF-8'); ?>'})">
                                                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                            </button>
                                            <?php if ($canAdminGroupVote && (function_exists('canEdit') ? canEdit() : true)): ?>
                                                <form method="POST" action="?page=processus_validation&amp;action=vote_groupe_admin" data-cm-ajax-form="true"
                                                    class="cm-inline-admin-group-vote-form"
                                                    style="display:inline-flex;">
                                                    <?php cm_component('form/csrf-token'); ?>
                                                    <input type="hidden" name="id_rapport" value="<?php echo $idRapport; ?>">
                                                    <button type="submit" class="cm-btn-action is-edit"
                                                        title="Appliquer mon vote aux membres manquants"
                                                        onclick="return confirm('Appliquer votre vote aux membres sans vote ?');">
                                                        <i class="fas fa-users" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($canFinalize && (function_exists('canEdit') ? canEdit() : true)): ?>
                                                <style>
                                                    /* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
                                                    .cm-content-area form .cm-form-group:has(#FIELD_ID) {
                                                        width: 10ch !important;
                                                        min-width: 10ch !important;
                                                        max-width: 10ch !important;
                                                    }
                                                </style>
                                                <form method="POST" action="?page=processus_validation&amp;action=finaliser" data-cm-ajax-form="true"
                                                    class="cm-inline-finalize-form"
                                                    style="display:inline-flex; align-items:center; gap:6px;">
                                                    <?php cm_component('form/csrf-token'); ?>
                                                    <input type="hidden" name="id_rapport" value="<?php echo $idRapport; ?>">
                                                    <input type="text" name="commentaire_validation" class="cm-form-control is-sm"
                                                        placeholder="Commentaire" style="width:120px;">
                                                    <button type="submit" class="cm-btn is-primary is-sm">
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
            CM.alert.show(alertBox, cssType, String(message || '').replace(/[<>&]/g, ''));
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
                const headers = ['Nom & Prénom', 'Statut', 'Votes', 'Date approbation'];
                const csvRows = [headers.join(';')];
                tableRows.forEach(function (row) {
                    if (row.style.display === 'none') {
                        return;
                    }
                    const cols = row.querySelectorAll('.cm-data-table__td');
                    if (cols.length < 4) {
                        return;
                    }
                    const line = [
                        cols[0].innerText.trim(),
                        cols[1].innerText.trim(),
                        cols[2].innerText.trim(),
                        cols[3].innerText.trim()
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
