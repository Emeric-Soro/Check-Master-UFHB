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
    error_log('PRD5 admin dashboard year fallback: ' . $e->getMessage());
}

$statsUsers = is_array($GLOBALS['stats_utilisateurs'] ?? null) ? $GLOBALS['stats_utilisateurs'] : [];
$statsStudents = is_array($GLOBALS['stats_etudiants'] ?? null) ? $GLOBALS['stats_etudiants'] : [];
$statsTeachers = is_array($GLOBALS['stats_enseignants'] ?? null) ? $GLOBALS['stats_enseignants'] : [];
$statsStaff = is_array($GLOBALS['stats_personnel'] ?? null) ? $GLOBALS['stats_personnel'] : [];

$activeUsers = (int) ($statsUsers['actifs'] ?? 0);
$totalStudents = (int) ($statsStudents['total'] ?? 0);
$totalTeachers = (int) ($statsTeachers['total'] ?? 0);
$totalStaff = (int) ($statsStaff['total'] ?? 0);

$errors24h = 0;
$distribution = [
    'Administrateurs' => 0,
    'Enseignants' => 0,
    'Etudiants' => 0,
];
$recentActivityItems = [];

try {
    if (!class_exists('Database')) {
        require_once dirname(__DIR__, 3) . '/app/config/database.php';
    }
    $pdo = Database::getConnection();

    $sqlErrors = "SELECT COUNT(*) AS total_errors
                  FROM pister
                  WHERE statut_action = 'Erreur'
                    AND date_creation >= (NOW() - INTERVAL 24 HOUR)";
    $errors24h = (int) ($pdo->query($sqlErrors)->fetchColumn() ?: 0);

    $sqlDistribution = "SELECT
                            SUM(CASE WHEN LOWER(COALESCE(g.lib_GU, '')) LIKE '%administrateur%' THEN 1 ELSE 0 END) AS admins,
                            SUM(CASE WHEN LOWER(COALESCE(t.lib_type_utilisateur, '')) LIKE '%enseignant%' THEN 1 ELSE 0 END) AS enseignants,
                            SUM(CASE WHEN LOWER(COALESCE(t.lib_type_utilisateur, '')) LIKE '%etudiant%' THEN 1 ELSE 0 END) AS etudiants
                        FROM utilisateur u
                        LEFT JOIN groupe_utilisateur g ON g.id_GU = u.id_GU
                        LEFT JOIN type_utilisateur t ON t.id_type_utilisateur = g.id_type_utilisateur";
    $rowDistribution = $pdo->query($sqlDistribution)->fetch(PDO::FETCH_ASSOC);
    if (is_array($rowDistribution)) {
        $distribution['Administrateurs'] = (int) ($rowDistribution['admins'] ?? 0);
        $distribution['Enseignants'] = (int) ($rowDistribution['enseignants'] ?? 0);
        $distribution['Etudiants'] = (int) ($rowDistribution['etudiants'] ?? 0);
    }

    $sqlRecentLogins = "SELECT
                            COALESCE(u.nom_utilisateur, CONCAT('Utilisateur #', p.id_utilisateur)) AS nom_utilisateur,
                            p.date_creation
                        FROM pister p
                        LEFT JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
                        WHERE (p.action = 'Connexion' OR p.action = 'Accès' OR p.action = 'Acces')
                          AND p.statut_action = 'Succès'
                        ORDER BY p.date_creation DESC
                        LIMIT 10";
    $recentLogins = $pdo->query($sqlRecentLogins)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($recentLogins as $login) {
        $rawDate = (string) ($login['date_creation'] ?? '');
        $formattedDate = '';
        if ($rawDate !== '') {
            $stamp = strtotime($rawDate);
            if ($stamp !== false) {
                $formattedDate = date('d/m/Y H:i', $stamp);
            }
        }
        $recentActivityItems[] = [
            'type' => 'info',
            'icon' => 'fa-right-to-bracket',
            'text' => 'Connexion reussie: ' . (string) ($login['nom_utilisateur'] ?? 'Utilisateur'),
            'time' => $formattedDate,
        ];
    }
} catch (Throwable $e) {
    error_log('PRD5 admin dashboard data error: ' . $e->getMessage());
}
?>

<section class="cm-prd3-screen">
    <header class="cm-flex-between cm-mb-md">
        <div>
            <h2 class="cm-m-0 cm-text-xl cm-text-bold cm-text-primary">Dashboard Administrateur</h2>
            <p class="cm-m-0 cm-text-muted">Vue globale du systeme et des activites recentes.</p>
        </div>
        <span class="cm-toolbar-year">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <?= htmlspecialchars($yearLabel, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </header>

    <div class="cm-grid cm-dashboard-stats-grid-5">
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format($activeUsers, 0, ',', ' '), 'label' => 'Utilisateurs actifs', 'icon' => 'fa-user-check', 'color' => 'primary']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format($totalStudents, 0, ',', ' '), 'label' => 'Total Etudiants', 'icon' => 'fa-user-graduate', 'color' => 'info']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format($totalTeachers, 0, ',', ' '), 'label' => 'Total Enseignants', 'icon' => 'fa-chalkboard-teacher', 'color' => 'success']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format($totalStaff, 0, ',', ' '), 'label' => 'Total Personnel Admin', 'icon' => 'fa-user-tie', 'color' => 'warning']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format($errors24h, 0, ',', ' '), 'label' => 'Erreurs 24h', 'icon' => 'fa-triangle-exclamation', 'color' => 'danger']); ?>
        </div>
    </div>

    <div class="cm-grid-2 cm-mt-md">
        <?php
        cm_component('dashboard/chart-container', [
            'chart_id' => 'cmAdminRoleDistribution',
            'title' => 'Repartition des profils',
            'subtitle' => 'Administrateurs / Enseignants / Etudiants',
            'type' => 'doughnut',
            'height' => 'lg',
            'data' => [
                'labels' => array_keys($distribution),
                'datasets' => [
                    [
                        'label' => 'Utilisateurs',
                        'data' => array_values($distribution),
                        'backgroundColor' => [
                            'var(--cm-primary)',
                            'var(--cm-feedback-success)',
                            'var(--cm-feedback-info)',
                        ],
                        'borderColor' => 'var(--cm-content-bg)',
                        'borderWidth' => 2,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ],
        ]);
        ?>

        <?php cm_component('dashboard/activity-list', ['title' => 'Dernieres connexions', 'items' => $recentActivityItems]); ?>
    </div>

    <div class="cm-chart-container cm-mt-md">
        <div class="cm-chart-container__header">
            <h3 class="cm-chart-container__title">Actions rapides</h3>
            <p class="cm-chart-container__subtitle">Navigation directe</p>
        </div>
        <div class="cm-chart-container__body">
            <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                <a class="cm-btn is-info" href="?page=gestion_utilisateurs" data-cm-ajax-link="true">
                    <i class="fas fa-users-cog" aria-hidden="true"></i>
                    Gerer les utilisateurs
                </a>
                <a class="cm-btn is-primary" href="?page=piste_audit" data-cm-ajax-link="true">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                    Piste d audit
                </a>
                <a class="cm-btn is-success" href="?page=parametres_generaux" data-cm-ajax-link="true">
                    <i class="fas fa-sliders" aria-hidden="true"></i>
                    Parametrage
                </a>
            </div>
        </div>
    </div>
</section>
