<?php
require_once __DIR__ . '/../../app/controllers/ValidationMemoireController.php';

$messageSuccess = (string) ($_SESSION['success'] ?? '');
$messageError = (string) ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);

$controller = new ValidationMemoireController();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$requestedAction = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $requestedAction === 'enregistrer_decision') {
    $result = $controller->enregistrerDecision();
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($result);
        exit;
    }
    $_SESSION[$result['success'] ? 'success' : 'error'] = (string) ($result['message'] ?? '');
    header('Location: layout.php?page=validation_memoires');
    exit;
}

$data = $controller->index();
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$memoires = is_array($data['memoires'] ?? null) ? $data['memoires'] : [];

$statusFilter = strtolower(trim((string) ($_GET['vm_status'] ?? 'all')));
if ($statusFilter === '') {
    $statusFilter = 'all';
}

$filteredMemoires = array_values(array_filter($memoires, static function (array $memoire) use ($statusFilter): bool {
    if ($statusFilter === 'all') {
        return true;
    }
    $statut = strtolower((string) ($memoire['statut'] ?? ''));
    return $statut === $statusFilter;
}));

$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_memoires'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_memoires'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($filteredMemoires), $perPage, $currentPage)
    : [
        'total' => count($filteredMemoires),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($filteredMemoires, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=validation_memoires&vm_status=' . urlencode($statusFilter) . '&limit_memoires=' . $perPage;

$roleLabels = [
    'directeur' => 'Directeur',
    'encadrant' => 'Encadrant',
    'responsable_filiere' => 'Responsable filiere',
];

$statusLabels = [
    'en_attente' => ['label' => 'En attente', 'type' => 'info'],
    'en_cours' => ['label' => 'En cours', 'type' => 'info'],
    'valide' => ['label' => 'Valide', 'type' => 'success'],
    'rejete' => ['label' => 'Rejete', 'type' => 'warning'],
];

$myUserId = (int) ($_SESSION['id_utilisateur'] ?? 0);
?>

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'success',
            'message' => $messageSuccess,
        ]); ?>
    <?php endif; ?>
    <?php if ($messageError !== ''): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'danger',
            'message' => $messageError,
        ]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="cm-grid-4">
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($stats['total'] ?? 0)),
                    'label' => 'Total memoires',
                    'icon' => 'fa-book',
                    'color' => 'primary',
                    'url' => '?page=validation_memoires&vm_status=all',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($stats['en_attente'] ?? 0)),
                    'label' => 'En attente',
                    'icon' => 'fa-hourglass-half',
                    'color' => 'info',
                    'url' => '?page=validation_memoires&vm_status=en_attente',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($stats['valides'] ?? 0)),
                    'label' => 'Valides',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'url' => '?page=validation_memoires&vm_status=valide',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'value' => (string) ((int) ($stats['rejetes'] ?? 0)),
                    'label' => 'Rejetes',
                    'icon' => 'fa-xmark-circle',
                    'color' => 'warning',
                    'url' => '?page=validation_memoires&vm_status=rejete',
                ]); ?>
            </div>
            <div class="cm-grid-2">
                <?php cm_component('form/select', [
                    'name' => 'cm_memoire_status',
                    'id' => 'cmMemoireStatusFilter',
                    'label' => 'Filtre statut',
                    'options' => [
                        'all' => 'Tous',
                        'en_attente' => 'En attente',
                        'en_cours' => 'En cours',
                        'valide' => 'Valide',
                        'rejete' => 'Rejete',
                    ],
                    'selected' => $statusFilter,
                    'attrs' => [
                        'data-cm-ajax-param' => 'vm_status',
                        'data-cm-ajax-reset-param' => 'page_memoires',
                        'data-cm-ajax-reset-value' => '1',
                    ],
                ]); ?>
            </div>
        </div>

        <?php cm_toolbar([
            'screen' => 'validation_memoires',
            'id_prefix' => 'cmMemoireVal',
            'search_value' => $_GET['search'] ?? '',
            'limit' => $perPage,
            'allowed_limits' => $allowedLimits,
            'can_delete' => canDelete('validation_memoires'),
            'can_view' => canView('validation_memoires'),
        ]); ?>

        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmMemoireValidationTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Etudiant</th>
                            <th class="cm-data-table__th">Promotion</th>
                            <th class="cm-data-table__th">Theme</th>
                            <th class="cm-data-table__th">Memoire</th>
                            <th class="cm-data-table__th">Encadrement</th>
                            <th class="cm-data-table__th">Statut</th>
                            <th class="cm-data-table__th">Suivi</th>
                            <th class="cm-data-table__th is-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rowsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 8,
                                'title' => 'Aucun memoire',
                                'message' => 'Aucun memoire ne correspond aux filtres selectionnes.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $memoire): ?>
                                <?php
                                $statut = strtolower((string) ($memoire['statut'] ?? 'en_attente'));
                                $statutMeta = $statusLabels[$statut] ?? $statusLabels['en_attente'];
                                $numSoutenance = (string) ($memoire['num_soutenance'] ?? '');
                                $memoireName = (string) ($memoire['nom_fichier'] ?? $memoire['fichier'] ?? 'memoire.pdf');
                                $memoireTheme = (string) ($memoire['theme_soutenance'] ?? $memoire['theme'] ?? '');
                                $promotion = (string) ($memoire['promotion'] ?? $memoire['promotion_etu'] ?? '');
                                $etudiantNom = trim(
                                    (string) ($memoire['nom_etudiant'] ?? $memoire['nom_etu'] ?? '') .
                                    ' ' .
                                    (string) ($memoire['prenom_etu'] ?? '')
                                );
                                if ($etudiantNom === '') {
                                    $etudiantNom = trim((string) ($memoire['nom_etudiant'] ?? ''));
                                }
                                $encadrement = is_array($memoire['encadrement'] ?? null) ? $memoire['encadrement'] : [];
                                $roleUtilisateur = (string) ($memoire['role_utilisateur'] ?? '');
                                $evaluations = is_array($memoire['evaluations'] ?? null) ? $memoire['evaluations'] : [];

                                $decisionsParRole = [];
                                foreach ($evaluations as $evaluation) {
                                    $roleKey = strtolower(trim((string) ($evaluation['type_evaluateur'] ?? '')));
                                    if ($roleKey === '' || isset($decisionsParRole[$roleKey])) {
                                        continue;
                                    }
                                    $decisionsParRole[$roleKey] = strtolower(trim((string) ($evaluation['decision'] ?? '')));
                                }

                                $myDecision = null;
                                $myCommentaire = '';
                                foreach ($evaluations as $evaluation) {
                                    if (
                                        (int) ($evaluation['id_evaluateur'] ?? 0) === $myUserId
                                        && (string) ($evaluation['type_evaluateur'] ?? '') === $roleUtilisateur
                                    ) {
                                        $myDecision = strtolower((string) ($evaluation['decision'] ?? ''));
                                        $myCommentaire = (string) ($evaluation['commentaire'] ?? '');
                                        break;
                                    }
                                }
                                ?>
                                <tr class="cm-data-table__row">
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($etudiantNom !== '' ? $etudiantNom : 'Etudiant', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($promotion, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($memoireTheme, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php if ($numSoutenance !== ''): ?>
                                            <a class="cm-link"
                                                href="?page=docviewer&type=memoire&id=<?php echo urlencode($numSoutenance); ?>&action=preview"
                                                target="_blank">
                                                <?php echo htmlspecialchars($memoireName, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($memoireName, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="cm-text-xs">
                                            <div>Directeur:
                                                <?php echo htmlspecialchars((string) ($encadrement['directeur']['nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div>Encadrant:
                                                <?php echo htmlspecialchars((string) ($encadrement['encadrant']['nom'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <span
                                            class="cm-badge is-<?php echo htmlspecialchars($statutMeta['type'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($statutMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="cm-text-xs">
                                            <div>Directeur:
                                                <?php echo htmlspecialchars($decisionsParRole['directeur'] ?? 'En attente', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div>Encadrant:
                                                <?php echo htmlspecialchars($decisionsParRole['encadrant'] ?? 'En attente', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div>Resp. filiere:
                                                <?php echo htmlspecialchars($decisionsParRole['responsable_filiere'] ?? 'En attente', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <?php if ($roleUtilisateur !== '' && canEdit('validation_memoires')): ?>
                                            <details class="cm-details">
                                                <summary class="cm-link">Donner avis
                                                    (<?php echo htmlspecialchars($roleLabels[$roleUtilisateur] ?? $roleUtilisateur, ENT_QUOTES, 'UTF-8'); ?>)
                                                </summary>
                                                <?php if ($myDecision !== null): ?>
                                                    <div class="cm-text-xs cm-mb-sm">Votre avis:
                                                        <?php echo htmlspecialchars($myDecision, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                                <form method="POST" action="?page=validation_memoires" class="cm-form">
                                                    <?php cm_component('form/csrf-token'); ?>
                                                    <input type="hidden" name="action" value="enregistrer_decision">
                                                    <input type="hidden" name="id_document"
                                                        value="<?php echo (int) ($memoire['id_document'] ?? 0); ?>">
                                                    <div class="cm-form-group">
                                                        <textarea name="commentaire" rows="3" class="cm-form-control"
                                                            placeholder="Commentaire (optionnel)"><?php echo htmlspecialchars($myCommentaire, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    </div>
                                                    <div class="cm-flex cm-flex-gap-sm cm-mt-sm">
                                                        <button type="submit" name="decision" value="valider"
                                                            class="cm-btn is-primary is-sm">Valider</button>
                                                        <button type="submit" name="decision" value="rejeter"
                                                            class="cm-btn is-danger is-sm">Rejeter</button>
                                                    </div>
                                                </form>
                                            </details>
                                        <?php else: ?>
                                            <span class="cm-text-xs">Non autorise</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($pagination['last'] ?? 1) > 1): ?>
                <div class="cm-mt-md">
                    <?php cm_component('crud/pagination', [
                        'pagination' => (object) $pagination,
                        'base_url' => $baseUrl,
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
