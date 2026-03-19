<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/RapportEtudiant.php';
require_once __DIR__ . '/../../app/models/EvaluationRapport.php';
$pdo = Database::getConnection();
$rapportModel = new RapportEtudiant($pdo);
$evaluationModel = new EvaluationRapport($pdo);
$idUtilisateur = (int) ($_SESSION['id_utilisateur'] ?? 0);
$allRapports = $rapportModel->getAllRapports();
$rapports = [];
$totalNouveaux = 0;
$totalTraites = 0;
foreach ($allRapports as $rapport) {
    $idRapport = (int) ($rapport->id_rapport ?? 0);
    if ($idRapport <= 0) {
        continue;
    }
    $evaluations = $evaluationModel->getEvaluationsRapport($idRapport);
    $nbEvaluations = count($evaluations);
    $dejaEvalue = $idUtilisateur > 0 ? (bool) $evaluationModel->evaluationExiste($idRapport, $idUtilisateur) : false;
    $votesValider = 0;
    $votesRejeter = 0;
    foreach ($evaluations as $evaluation) {
        $decision = strtolower((string) ($evaluation['decision_evaluation'] ?? ''));
        if ($decision === 'valider') {
            $votesValider++;
        } elseif ($decision === 'rejeter') {
            $votesRejeter++;
        }
    }
    $isNouveau = $nbEvaluations === 0;
    if ($isNouveau) {
        $totalNouveaux++;
    } else {
        $totalTraites++;
    }
    $rapports[] = [
        'id_rapport' => $idRapport,
        'num_etu' => (string) ($rapport->num_etu ?? ''),
        'nom_rapport' => (string) ($rapport->nom_rapport ?? 'Rapport'),
        'theme_rapport' => (string) ($rapport->theme_rapport ?? ''),
        'date_rapport' => (string) ($rapport->date_rapport ?? ''),
        'statut_rapport' => strtolower((string) ($rapport->statut_rapport ?? 'en_attente')),
        'nom_etu' => (string) ($rapport->nom_etu ?? ''),
        'prenom_etu' => (string) ($rapport->prenom_etu ?? ''),
        'nb_evaluations' => $nbEvaluations,
        'votes_valider' => $votesValider,
        'votes_rejeter' => $votesRejeter,
        'is_nouveau' => $isNouveau,
        'deja_evalue' => $dejaEvalue,
    ];
}
usort($rapports, static function (array $a, array $b): int {
    $dateA = strtotime((string) ($a['date_rapport'] ?? '1970-01-01'));
    $dateB = strtotime((string) ($b['date_rapport'] ?? '1970-01-01'));
    return $dateB <=> $dateA;
});
$statusFilter = strtolower(trim((string) ($_GET['reception_status'] ?? 'all')));
if ($statusFilter === '') {
    $statusFilter = 'all';
}
$filteredRapports = array_values(array_filter($rapports, static function (array $row) use ($statusFilter): bool {
    if ($statusFilter === 'all') {
        return true;
    }
    if ($statusFilter === 'nouveau') {
        return !empty($row['is_nouveau']);
    }
    if ($statusFilter === 'traite') {
        return empty($row['is_nouveau']);
    }
    return strtolower((string) ($row['statut_rapport'] ?? '')) === $statusFilter;
}));
$allowedLimits = [5, 10, 25, 50, 100];
$perPage = max(5, (int) ($_GET['limit_reception'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_reception'] ?? 1));
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
$baseUrl = '?page=' . urlencode((string) ($_GET['page'] ?? 'rapport_a_valider'))
    . '&reception_status=' . urlencode($statusFilter)
    . '&limit_reception=' . $perPage;
$statusOptions = [
    'all' => 'Tous',
    'nouveau' => 'Nouveaux',
    'traite' => 'Traites',
    'en_attente' => 'En attente',
    'en_cours' => 'En cours',
    'valider' => 'Validés',
    'rejeter' => 'Rejetés',
];
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="">
            </div>
            <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                <?php cm_component('ui/badge', ['text' => 'Total rapports: ' . count($rapports), 'type' => 'info']); ?>
                <?php cm_component('ui/badge', ['text' => 'Nouveaux: ' . $totalNouveaux, 'type' => 'warning']); ?>
                <?php cm_component('ui/badge', ['text' => 'Deja traites: ' . $totalTraites, 'type' => 'success']); ?>
            </div>
        </div>
        <?php cm_toolbar([
            'screen' => 'rapport_a_valider',
            'id_prefix' => 'cmReception',
            'search_value' => $_GET['search'] ?? '',
            'limit' => $perPage,
            'allowed_limits' => $allowedLimits,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmReceptionTable">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th cm-data-table__th--check">
                            <input type="checkbox" id="cmReceptionCheckAll" aria-label="Tout sélectionner">
                        </th>
                        <th class="cm-data-table__th">Nouv.</th>
                        <th class="cm-data-table__th">N° Rapport</th>
                        <th class="cm-data-table__th">N° Carte</th>
                        <th class="cm-data-table__th">Nom &amp; Prénom</th>
                        <th class="cm-data-table__th">Nom rapport</th>
                        <th class="cm-data-table__th">Thème</th>
                        <th class="cm-data-table__th">Date dépôt</th>
                        <th class="cm-data-table__th">Statut</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="cmReceptionTableBody">
                    <?php if (empty($rowsToShow)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 10,
                            'title' => '',
                            'message' => 'Aucun rapport ne correspond aux filtres.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($rowsToShow as $row): ?>
                            <?php
                            $idRapport = (int) ($row['id_rapport'] ?? 0);
                            $numEtu = (string) ($row['num_etu'] ?? '');
                            $isNouveau = !empty($row['is_nouveau']);
                            $searchText = strtolower(
                                (string) $row['nom_rapport'] . ' ' .
                                (string) $row['theme_rapport'] . ' ' .
                                (string) $row['prenom_etu'] . ' ' .
                                (string) $row['nom_etu'] . ' ' .
                                $numEtu
                            );
                            $dateDepot = !empty($row['date_rapport']) ? date('d/m/Y', strtotime((string) $row['date_rapport'])) : '-';
                            $statut = strtolower((string) ($row['statut_rapport'] ?? 'en_attente'));
                            $statutLabel = 'En attente';
                            $badgeType = 'info';
                            if ($statut === 'valider') {
                                $statutLabel = 'Validé';
                                $badgeType = 'success';
                            } elseif ($statut === 'rejeter') {
                                $statutLabel = 'Rejeté';
                                $badgeType = 'danger';
                            } elseif ($statut === 'en_cours') {
                                $statutLabel = 'En cours';
                                $badgeType = 'warning';
                            }
                            ?>
                            <tr class="cm-data-table__row<?php echo $isNouveau ? ' cm-reception-row--new' : ''; ?>"
                                data-rapport-id="<?php echo $idRapport; ?>"
                                data-is-new="<?php echo $isNouveau ? '1' : '0'; ?>"
                                data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="cm-data-table__td cm-data-table__td--check">
                                    <input type="checkbox" class="cm-reception-check-row" value="<?php echo $idRapport; ?>" aria-label="Sélectionner ligne rapport <?php echo $idRapport; ?>">
                                </td>
                                <td class="cm-data-table__td">
                                    <?php if ($isNouveau): ?>
                                        <?php cm_component('ui/badge', ['type' => 'warning', 'text' => 'Nouveau']); ?>
                                    <?php else: ?>
                                        <span class="cm-text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cm-data-table__td"><?php echo $idRapport; ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($numEtu !== '' ? $numEtu : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars(trim((string) $row['prenom_etu'] . ' ' . (string) $row['nom_etu']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $row['nom_rapport'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $row['theme_rapport'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($dateDepot, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td">
                                    <?php cm_component('ui/badge', ['text' => $statutLabel, 'type' => $badgeType]); ?>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions">
                                        <?php if (canView()): ?>
                                            <a class="cm-btn-action is-view"
                                               href="?page=evaluation_dossiers&detail=<?php echo urlencode((string) $idRapport); ?>"
                                               title="Voir rapport">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (canEdit()): ?>
                                            <a class="cm-btn is-info is-sm"
                                               href="?page=evaluation_dossiers&detail=<?php echo urlencode((string) $idRapport); ?>"
                                               title="Transmettre à l'évaluation">
                                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                                <span>Transmettre</span>
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
                'param_name' => 'page_reception',
            ]);
            ?>
            <div class="cm-text-sm cm-text-muted cm-px-md">
                Cliquez sur un rapport marque nouveau pour le traiter (redirection automatique).
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var tableBody = document.getElementById('cmReceptionTableBody');
    var checkAll = document.getElementById('cmReceptionCheckAll');

    function getRows() {
        return Array.from(document.querySelectorAll('#cmReceptionTableBody .cm-data-table__row'));
    }
    function getVisibleRows() {
        return getRows().filter(function (row) {
            return row.style.display !== 'none';
        });
    }
    function getCheckedRows() {
        return getRows().filter(function (row) {
            var cb = row.querySelector('.cm-reception-check-row');
            return cb && cb.checked;
        });
    }
    function updateDeleteState() {
        if (!checkAll) return;
        var visible = getVisibleRows();
        var checkedVisible = visible.filter(function (row) {
            var cb = row.querySelector('.cm-reception-check-row');
            return cb && cb.checked;
        });
        checkAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
    }

    if (tableBody) {
        tableBody.addEventListener('change', function (event) {
            if (event.target && event.target.classList.contains('cm-reception-check-row')) {
                updateDeleteState();
            }
        });
    }
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            getVisibleRows().forEach(function (row) {
                var cb = row.querySelector('.cm-reception-check-row');
                if (cb) {
                    cb.checked = checkAll.checked;
                }
            });
            updateDeleteState();
        });
    }

    function bindRowClickBehavior() {
        getRows().forEach(function (row) {
            var isNew = row.getAttribute('data-is-new') === '1';
            var rapportId = row.getAttribute('data-rapport-id') || '';
            if (!isNew || !rapportId) return;
            row.style.cursor = 'pointer';
            row.addEventListener('click', function (event) {
                if (event.target.closest('a,button,input,select,textarea,label')) return;
                var url = '?page=evaluation_dossiers&detail=' + encodeURIComponent(rapportId);
                if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                    window.CM.ajax.load(url);
                    return;
                }
                window.location.href = url;
            });
        });
    }

    // Toolbar search -> sync check-all state after filtering
    var searchInput = document.getElementById('cmReception_search');
    if (searchInput) {
        searchInput.addEventListener('input', function () { updateDeleteState(); });
        searchInput.addEventListener('keyup', function () { updateDeleteState(); });
    }

    // Toolbar delete event
    document.addEventListener('cm:toolbar:delete', function (event) {
        if (!event.detail || !event.detail.toolbar) return;
        if (event.detail.toolbar.id !== 'cmReception_toolbar') return;
        var checked = getCheckedRows();
        if (checked.length === 0) return;
        checked.forEach(function (row) { row.remove(); });
        updateDeleteState();
    });

    // Toolbar limit change event
    document.addEventListener('cm:toolbar:limit:change', function (event) {
        if (!event.detail || !event.detail.toolbar) return;
        if (event.detail.toolbar.id !== 'cmReception_toolbar') return;
        event.preventDefault();
        var limit = event.detail.limit || '10';
        var url = new URL(window.location.href);
        url.searchParams.set('limit_reception', limit);
        url.searchParams.set('page_reception', '1');
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url.toString());
        } else {
            window.location.href = url.toString();
        }
    });

    bindRowClickBehavior();
    updateDeleteState();
})();
</script>