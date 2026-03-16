<?php
require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';

$dashboardController = new DashboardScolariteController();
$dashboardData = $dashboardController->getDashboardData();

$stats = is_array($dashboardData['stats'] ?? null) ? $dashboardData['stats'] : [];
$inscriptionsParNiveau = is_array($dashboardData['inscriptionsParNiveau'] ?? null) ? $dashboardData['inscriptionsParNiveau'] : [];
$selectedYearId = !empty($_SESSION['global_annee_id']) ? (int) $_SESSION['global_annee_id'] : null;

$anneeLabel = trim((string) ($_SESSION['global_annee_selected'] ?? ''));
if (!empty($GLOBALS['anneeAcademiqueActive']) && is_object($GLOBALS['anneeAcademiqueActive'])) {
    $annee = $GLOBALS['anneeAcademiqueActive'];
    $anneeDebut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : date('Y');
    $anneeFin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : (date('Y') + 1);
    if ($anneeLabel === '') {
        $anneeLabel = $anneeDebut . '-' . $anneeFin;
    }
}

$niveauLabels = [];
$niveauValues = [];
foreach ($inscriptionsParNiveau as $niveau) {
    $niveauLabels[] = (string) ($niveau['niveau'] ?? 'Niveau');
    $niveauValues[] = (int) ($niveau['total'] ?? 0);
}

$genres = [
    'Masculin' => 0,
    'Feminin' => 0,
    'Neutre' => 0,
    'Non précisé' => 0,
];

try {
    $etudiantModel = new Etudiant(Database::getConnection());
    $allEtudiants = $etudiantModel->getAllEtudiants($selectedYearId);
    foreach ($allEtudiants as $etu) {
        $rawGenre = strtolower(trim((string) ($etu->libelle_genre ?? '')));
        if ($rawGenre === 'masculin' || $rawGenre === '1') {
            $genres['Masculin']++;
        } elseif ($rawGenre === 'feminin' || $rawGenre === 'féminin' || $rawGenre === '2') {
            $genres['Feminin']++;
        } elseif ($rawGenre === 'neutre' || $rawGenre === '3') {
            $genres['Neutre']++;
        } else {
            $genres['Non précisé']++;
        }
    }
} catch (Throwable $e) {
    // Affichage dégradé sans bloquer la page.
}

$alertItems = [
    [
        'type' => 'warning',
        'message' => (int) ($stats['reclamations_en_attente'] ?? 0) . ' réclamation(s) non traitée(s).',
        'action_url' => '?page=gestion_reclamations_scolarite',
        'action_label' => 'Traiter',
    ],
    [
        'type' => ((float) ($stats['montant_attente'] ?? 0) > 0) ? 'warning' : 'success',
        'message' => 'Reste global: ' . number_format((float) ($stats['montant_attente'] ?? 0), 0, ',', ' ') . ' FCFA.',
        'action_url' => '?page=gestion_scolarite',
        'action_label' => 'Voir paiements',
    ],
];
?>

<?php
cm_component('layout/page-header', [
    'title' => '',
    'subtitle' => 'Pilotage global des étudiants, paiements et réclamations.',
    'annee' => $anneeLabel,
    'icon' => 'fa-school',
]);
?>

<div class="cm-dashboard-grid">
    <?php
    cm_component('dashboard/stat-widget', [
        'value' => number_format((int) ($stats['etudiants'] ?? 0), 0, ',', ' '),
        'label' => 'Total étudiants inscrits',
        'subtitle' => 'Étudiants inscrits',
        'icon' => 'fa-users',
        'color' => 'primary',
    ]);
    cm_component('dashboard/stat-widget', [
        'value' => number_format((int) ($stats['nouvelles_inscriptions'] ?? 0), 0, ',', ' '),
        'label' => 'Inscriptions en cours',
        'subtitle' => 'Nouvelles inscriptions (7j)',
        'icon' => 'fa-user-plus',
        'color' => 'info',
    ]);
    cm_component('dashboard/stat-widget', [
        'value' => number_format((float) ($stats['montant_percu'] ?? 0), 0, ',', ' ') . ' FCFA',
        'label' => 'Montant total perçu',
        'subtitle' => 'Versements enregistrés',
        'icon' => 'fa-money-bill-wave',
        'color' => 'success',
    ]);
    cm_component('dashboard/stat-widget', [
        'value' => number_format((int) ($stats['reclamations_en_attente'] ?? 0), 0, ',', ' '),
        'label' => 'Alertes groupées',
        'subtitle' => 'Réclamations + reste à payer',
        'icon' => 'fa-triangle-exclamation',
        'color' => 'warning',
    ]);
    ?>
</div>

<div class="cm-grid-2 cm-mb-lg">
    <?php
    cm_component('dashboard/chart-container', [
        'chart_id' => 'cmScolariteNiveaux',
        'title' => '',
        'subtitle' => 'Inscriptions par niveau d\'étude',
        'type' => 'bar',
        'height' => '320px',
        'data' => [
            'labels' => $niveauLabels,
            'datasets' => [
                [
                    'label' => 'Étudiants',
                    'data' => $niveauValues,
                    'backgroundColor' => 'rgba(52, 152, 219, 0.55)',
                    'borderColor' => '#1a5276',
                    'borderWidth' => 1,
                ],
            ],
        ],
        'options' => [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ],
    ]);

    cm_component('dashboard/chart-container', [
        'chart_id' => 'cmScolariteGenres',
        'title' => '',
        'subtitle' => 'Population étudiante',
        'type' => 'bar',
        'height' => '320px',
        'data' => [
            'labels' => array_keys($genres),
            'datasets' => [
                [
                    'label' => 'Étudiants',
                    'data' => array_values($genres),
                    'backgroundColor' => [
                        'rgba(26, 82, 118, 0.65)',
                        'rgba(39, 174, 96, 0.65)',
                        'rgba(52, 152, 219, 0.65)',
                        'rgba(127, 140, 141, 0.65)',
                    ],
                    'borderWidth' => 0,
                ],
            ],
        ],
        'options' => [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ],
    ]);
    ?>
</div>

<div class="cm-grid-2">
    <?php cm_component('dashboard/alert-list', ['title' => '', 'items' => $alertItems, 'max_show' => 4]); ?>

    <div class="cm-chart-container">
        <div class="cm-chart-container__header">

            <p class="cm-chart-container__subtitle">Accès direct aux opérations de scolarite</p>
        </div>
        <div class="cm-chart-container__body">
            <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-info" href="?page=maj_etudiant">
                        <i class="fas fa-user-graduate" aria-hidden="true"></i>
                        Gérer les étudiants
                    </a>
                <?php endif; ?>
                <?php if (canView()): ?>
                    <a class="cm-btn is-info" href="?page=gestion_scolarite">
                        <i class="fas fa-credit-card" aria-hidden="true"></i>
                        Inscriptions / paiements
                    </a>
                <?php endif; ?>
                <?php if (canView()): ?>
                    <a class="cm-btn is-info" href="?page=gestion_dossiers_candidatures">
                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                        Dossiers de candidatures
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>