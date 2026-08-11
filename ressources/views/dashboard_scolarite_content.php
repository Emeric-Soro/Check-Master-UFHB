<?php
require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';

$dashboardController = new DashboardScolariteController();
$dashboardData = $dashboardController->getDashboardData();

$stats = is_array($dashboardData['stats'] ?? null) ? $dashboardData['stats'] : [];
$nouvellesInscriptionsDetail = is_array($dashboardData['nouvelles_inscriptions_detail'] ?? null) ? $dashboardData['nouvelles_inscriptions_detail'] : [];
$paiementsEnAttenteDetail = is_array($dashboardData['paiements_en_attente_detail'] ?? null) ? $dashboardData['paiements_en_attente_detail'] : [];
$selectedYearId = !empty($_SESSION['global_annee_id']) ? (int) $_SESSION['global_annee_id'] : null;

$kpiNouvellesInscriptionsUrl = '?page=gestion_scolarite';
if ((int) ($stats['nouvelles_inscriptions'] ?? 0) === 1 && !empty($nouvellesInscriptionsDetail[0]['num_etu'])) {
    $kpiNouvellesInscriptionsUrl .= '&search=' . urlencode((string) $nouvellesInscriptionsDetail[0]['num_etu']);
}

$anneeLabel = trim((string) ($_SESSION['global_annee_selected'] ?? ''));
if (!empty($GLOBALS['anneeAcademiqueActive']) && is_object($GLOBALS['anneeAcademiqueActive'])) {
    $annee = $GLOBALS['anneeAcademiqueActive'];
    $anneeDebut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : date('Y');
    $anneeFin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : (date('Y') + 1);
    if ($anneeLabel === '') {
        $anneeLabel = $anneeDebut . '-' . $anneeFin;
    }
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
    'annee' => \FormattingUtils::formatPromotion($anneeLabel),
    'icon' => 'fa-school',
]);
?>

<div class="cm-dashboard-grid">
    <div>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format((int) ($stats['etudiants'] ?? 0), 0, ',', ' '),
            'label' => 'Total étudiants inscrits',
            'subtitle' => 'Étudiants inscrits',
            'icon' => 'fa-users',
            'color' => 'primary',
            'url' => canView() ? '?page=maj_etudiant' : ''
        ]); ?>
    </div>

    <div>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format((int) ($stats['nouvelles_inscriptions'] ?? 0), 0, ',', ' '),
            'label' => 'Inscriptions en cours',
            'subtitle' => 'Nouvelles inscriptions (7j)',
            'icon' => 'fa-user-plus',
            'color' => 'info',
            'url' => canView() ? $kpiNouvellesInscriptionsUrl : ''
        ]); ?>
    </div>

    <div>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format((float) ($stats['montant_percu'] ?? 0), 0, ',', ' ') . ' FCFA',
            'label' => 'Montant total perçu',
            'subtitle' => 'Versements enregistrés',
            'icon' => 'fa-money-bill-wave',
            'color' => 'success',
            'url' => canView() ? '?page=gestion_scolarite' : ''
        ]); ?>
    </div>

    <div>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format((int) ($stats['reclamations_en_attente'] ?? 0), 0, ',', ' '),
            'label' => 'Alertes groupées',
            'subtitle' => 'Réclamations + reste à payer',
            'icon' => 'fa-triangle-exclamation',
            'color' => 'warning',
            'url' => canView() ? '?page=gestion_reclamations_scolarite' : ''
        ]); ?>
    </div>
</div>

<div class="cm-grid-2 cm-mb-lg">
    <?php
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

    <div class="cm-chart-container">
        <div class="cm-chart-container__header">
            <h3 class="cm-chart-container__title">Accès direct</h3>
            <p class="cm-chart-container__subtitle">Opérations de scolarité</p>
        </div>
        <div class="cm-chart-container__body is-md" style="display: flex; align-items: center;">
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
</div>

<div class="cm-mb-lg">
    <?php cm_component('dashboard/alert-list', ['title' => '', 'items' => $alertItems, 'max_show' => 4]); ?>
</div>

<?php if (!empty($paiementsEnAttenteDetail)): ?>
    <div class="cm-card cm-mt-md">
        <div class="cm-card__header cm-flex-between">
            <h3 class="cm-card__title"><i class="fas fa-clock cm-mr-sm"></i>Paiements en attente</h3>
            <?php if (canView()): ?>
                <a class="cm-stat-card__link" href="?page=gestion_scolarite" data-cm-ajax-link="true">Voir ▸</a>
            <?php endif; ?>
        </div>
        <div class="cm-card__body">
            <div style="overflow-x:auto">
                <table class="cm-table">
                    <thead>
                        <tr>
                            <th>Nom / Prénom</th>
                            <th>Niveau</th>
                            <th>Montant versé (FCFA)</th>
                            <th>Reste à payer (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paiementsEnAttenteDetail as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars(trim(($row['nom_etudiant'] ?? '') . ' ' . ($row['prenom_etudiant'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= htmlspecialchars($row['libelle_niveau'] ?? $row['id_niv_etude'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= number_format((float) ($row['montant_verser'] ?? 0), 0, ',', ' ') ?></td>
                                <td class="cm-text-danger">
                                    <?= number_format((float) ($row['reste_a_payer'] ?? 0), 0, ',', ' ') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>