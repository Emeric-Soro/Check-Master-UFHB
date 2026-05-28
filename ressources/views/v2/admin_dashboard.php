<?php
$yearLabel = \AcademicYear::getSelectedLabelFromSession();
if ($yearLabel === '') {
    $yearLabel = date('Y') . '-' . (date('Y') + 1);
}
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$filtreAnneeAdmin = isset($_GET['id_annee_acad']) && $_GET['id_annee_acad'] !== '' ? (int) $_GET['id_annee_acad'] : \AcademicYear::getSelectedIdFromSession();
$filtreSessionAdmin = isset($_GET['id_session']) && $_GET['id_session'] !== '' ? (int) $_GET['id_session'] : null;
$pageNumAdmin = max(1, (int) ($_GET['page_num'] ?? 1));
$perPageAdmin = 10;

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
        if ($yearLabel === '') {
            $yearLabel = date('Y', strtotime((string) $anneeActive->date_deb)) . '-' . date('Y', strtotime((string) $anneeActive->date_fin));
        }
        if ($filtreAnneeAdmin === null && !$allYearsSelected) {
            $filtreAnneeAdmin = (int) ($anneeActive->id_annee_acad ?? 0);
        }
    }
} catch (Throwable $e) {
    error_log('PRD8 admin dashboard year fallback: ' . $e->getMessage());
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
$recentErrors = [];
$distribution = [
    'Administrateurs' => 0,
    'Enseignants' => 0,
    'Étudiants' => 0,
];
$recentActivityItems = [];

$enseignantsJuryData = [];
$enseignantsJuryPagination = [];
$anneeOptionsAdmin = [];
$sessionOptionsAdmin = [];

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

    if ($errors24h > 0) {
        $sqlRecentErrors = "SELECT p.date_creation, p.action, p.contexte,
                                                                COALESCE(u.nom_utilisateur, CONCAT('Utilisateur #', p.id_utilisateur)) AS nom_utilisateur
                                                        FROM pister p
                                                        LEFT JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
                                                        WHERE p.statut_action = 'Erreur'
                                                            AND p.date_creation >= (NOW() - INTERVAL 24 HOUR)
                                                        ORDER BY p.date_creation DESC
                                                        LIMIT 10";
        $recentErrors = $pdo->query($sqlRecentErrors)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

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
        $distribution['Étudiants'] = (int) ($rowDistribution['etudiants'] ?? 0);
    }

    $sqlRecentLogins = "SELECT
                            COALESCE(u.nom_utilisateur, CONCAT('Utilisateur #', p.id_utilisateur)) AS nom_utilisateur,
                            p.date_creation
                        FROM pister p
                        LEFT JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
                        WHERE (p.action = 'Connexion' OR p.action = 'Accès' OR p.action = 'Accès')
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
            'text' => 'Connexion réussie: ' . (string) ($login['nom_utilisateur'] ?? 'Utilisateur'),
            'time' => $formattedDate,
        ];
    }

    // Options pour les filtres
    $stmtAnnees = $pdo->query("SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle FROM annee_academique ORDER BY date_deb DESC");
    $anneeOptionsAdmin = $stmtAnnees->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $stmtSessions = $pdo->query("SELECT id_session, lib_session FROM session ORDER BY id_session");
    $sessionOptionsAdmin = $stmtSessions->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Tableau des enseignants jury
    $juryTable = $pdo->query("SHOW TABLES LIKE 'enseignant_jury'")->fetchColumn() ? 'enseignant_jury' : 'composer_jury';

    $whereConditions = [];
    $params = [];

    if ($filtreAnneeAdmin !== null) {
        $whereConditions[] = "i2.id_annee_acad = :id_annee_acad";
        $params[':id_annee_acad'] = $filtreAnneeAdmin;
    }

    if ($filtreSessionAdmin !== null) {
        $whereConditions[] = "ps2.id_session = :id_session";
        $params[':id_session'] = $filtreSessionAdmin;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $countSql = "SELECT COUNT(*) FROM (
                     SELECT ens.id_enseignant
                     FROM enseignants ens
                     JOIN {$juryTable} ej ON CAST(ej.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
                     LEFT JOIN affecter a ON CAST(a.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
                     LEFT JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                     LEFT JOIN programmer_soutenance ps2 ON ps2.num_etud = r.num_etu
                     LEFT JOIN etudiants e2 ON e2.num_carte_etud = ps2.num_etud
                     LEFT JOIN inscriptions i2 ON i2.num_carte_etud = e2.num_carte_etud
                     {$whereClause}
                     GROUP BY ens.id_enseignant
                     HAVING COUNT(DISTINCT ej.num_soutenance) > 0
                 ) AS sub_count";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($pageNumAdmin - 1) * $perPageAdmin;

    $sql = "SELECT
                ens.id_enseignant,
                ens.nom_enseignant,
                ens.prenom_enseignant,
                COUNT(DISTINCT ej.num_soutenance) AS nb_soutenances_jury,
                COUNT(DISTINCT CASE WHEN a.role = 'encadrant' THEN ps2.num_soutenance END) AS nb_soutenances_encadrees,
                COUNT(DISTINCT CASE WHEN a.role = 'directeur' THEN ps2.num_soutenance END) AS nb_soutenances_dirigees
            FROM enseignants ens
            JOIN {$juryTable} ej ON CAST(ej.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
            LEFT JOIN affecter a ON CAST(a.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
            LEFT JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
            LEFT JOIN programmer_soutenance ps2 ON ps2.num_etud = r.num_etu
            LEFT JOIN etudiants e2 ON e2.num_carte_etud = ps2.num_etud
            LEFT JOIN inscriptions i2 ON i2.num_carte_etud = e2.num_carte_etud
            {$whereClause}
            GROUP BY ens.id_enseignant, ens.nom_enseignant, ens.prenom_enseignant
            HAVING COUNT(DISTINCT ej.num_soutenance) > 0
            ORDER BY nb_soutenances_encadrees DESC, ens.nom_enseignant ASC
            LIMIT :limit OFFSET :offset";

    $params[':limit'] = $perPageAdmin;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    $enseignantsJuryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lastPage = max(1, (int) ceil($total / $perPageAdmin));
    $enseignantsJuryPagination = [
        'total' => $total,
        'current' => $pageNumAdmin,
        'per_page' => $perPageAdmin,
        'last' => $lastPage,
        'offset' => $offset,
        'has_prev' => $pageNumAdmin > 1,
        'has_next' => $pageNumAdmin < $lastPage,
        'pages' => range(1, $lastPage),
    ];
} catch (Throwable $e) {
    error_log('PRD8 admin dashboard data error: ' . $e->getMessage());
}
?>

<section class="cm-prd3-screen">
    <div class="cm-grid cm-dashboard-stats-grid-5">
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($activeUsers, 0, ',', ' '),
                'label' => 'Utilisateurs actifs',
                'icon' => 'fa-user-check',
                'color' => 'primary',
                'url' => '?page=gestion_utilisateurs',
                'ajax' => true
            ]); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($totalStudents, 0, ',', ' '),
                'label' => 'Total Etudiants',
                'icon' => 'fa-user-graduate',
                'color' => 'info',
                'url' => '?page=maj_etudiant',
                'ajax' => true
            ]); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($totalTeachers, 0, ',', ' '),
                'label' => 'Total Enseignants',
                'icon' => 'fa-chalkboard-teacher',
                'color' => 'success',
                'url' => '?page=repertoire_enseignant',
                'ajax' => true
            ]); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($totalStaff, 0, ',', ' '),
                'label' => 'Total Personnel Admin',
                'icon' => 'fa-user-tie',
                'color' => 'warning',
                'url' => '?page=gestion_rh',
                'ajax' => true
            ]); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($errors24h, 0, ',', ' '),
                'label' => 'Erreurs 24h',
                'icon' => 'fa-triangle-exclamation',
                'color' => 'danger',
                'url' => '?page=piste_audit&statut=Erreur',
                'ajax' => true
            ]); ?>
        </div>
    </div>

    <div class="cm-grid-2 cm-mt-md">
        <?php
        cm_component('dashboard/chart-container', [
            'chart_id' => 'cmAdminRoleDistribution',
            'title' => '',
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
                            '#1d4ed8',
                            '#16a34a',
                            '#f59e0b',
                        ],
                        'hoverBackgroundColor' => [
                            '#1e40af',
                            '#15803d',
                            '#d97706',
                        ],
                        'borderColor' => '#f3f7fb',
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

        <?php cm_component('dashboard/activity-list', ['title' => '', 'items' => $recentActivityItems]); ?>
    </div>

    <?php if (!empty($recentErrors)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title cm-text-danger"><i class="fas fa-triangle-exclamation cm-mr-sm"></i>Erreurs
                    système (24h)</h3>
            </div>
            <div class="cm-card__body">
                <div style="overflow-x:auto">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>Date / Heure</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentErrors as $err): ?>
                                <tr>
                                    <td><?= !empty($err['date_creation']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $err['date_creation'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                    <td><?= htmlspecialchars($err['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($err['contexte'] ?? $err['nom_table'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($err['nom_utilisateur'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="cm-chart-container cm-mt-md">
        <div class="cm-chart-container__header">

            <p class="cm-chart-container__subtitle">Navigation directe</p>
        </div>
        <div class="cm-chart-container__body">
            <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                <?php if (canView('gestion_utilisateurs')): ?>
                    <a class="cm-btn is-info" href="?page=gestion_utilisateurs" data-cm-ajax-link="true">
                        <i class="fas fa-users-cog" aria-hidden="true"></i>
                        Gérer utilisateurs
                    </a>
                <?php endif; ?>

                <?php if (canView('piste_audit')): ?>
                    <a class="cm-btn is-primary" href="?page=piste_audit" data-cm-ajax-link="true">
                        <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        Piste audit
                    </a>
                <?php endif; ?>

                <?php if (canView('parametres_generaux')): ?>
                    <a class="cm-btn is-primary" href="?page=parametres_generaux" data-cm-ajax-link="true">
                        <i class="fas fa-sliders" aria-hidden="true"></i>
                        Parametrage
                    </a>
                <?php endif; ?>

                <?php if (canView('enseignants_jury')): ?>
                    <a class="cm-btn is-warning" href="?page=enseignants_jury" data-cm-ajax-link="true">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        Enseignants Jury
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="cm-card cm-mt-md">
            <div class="cm-card__header cm-flex-between">

                <a href="?page=enseignants_jury" class="cm-btn cm-btn--primary cm-btn--sm" data-cm-ajax-link="true">
                    <i class="fas fa-external-link-alt cm-mr-sm"></i> Voir tout
                </a>
            </div>
            <div class="cm-card__body">
                <style>
                    /* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
                    .cm-content-area form .cm-form-group:has(#FIELD_ID) {
                        width: 10ch !important;
                        min-width: 10ch !important;
                        max-width: 10ch !important;
                    }
                </style>
                <form method="GET" class="cm-grid-3 cm-mb-md" style="align-items: end;">
                    <input type="hidden" name="page" value="dashboard">

                    <input type="hidden" name="id_annee_acad"
                        value="<?= htmlspecialchars((string) (\AcademicYear::getWritableIdFromSession() ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?= cm_component('form/select', [
                        'name' => 'id_session',
                        'label' => 'Période',
                        'options' => $sessionOptionsAdmin,
                        'selected' => (string) ($filtreSessionAdmin ?? ''),
                        'placeholder' => 'Toutes les périodes'
                    ]) ?>

                    <div class="cm-flex cm-flex-gap-sm">
                        <button type="submit" class="cm-btn is-primary is-sm">
                            <i class="fas fa-filter cm-mr-sm"></i> Filtrer
                        </button>
                        <a href="?page=dashboard" class="cm-btn is-light is-sm">
                            Réinitialiser
                        </a>
                    </div>
                </form>

                <table class="cm-table">
                    <thead>
                        <tr>
                            <th>Nom &amp; Prénom</th>
                            <th class="cm-text-center">Jurys</th>
                            <th class="cm-text-center">Encadrées</th>
                            <th class="cm-text-center">Dirigées</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enseignantsJuryData)): ?>
                            <tr>
                                <td colspan="4">
                                    <?= cm_component('ui/empty-state', [
                                        'title' => '',
                                        'message' => 'Aucun enseignant n\'a participé à un jury pour les critères sélectionnés.',
                                        'icon' => 'fa-users',
                                        'in_table' => true,
                                        'colspan' => 4
                                    ]) ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enseignantsJuryData as $ens): ?>
                                <tr>
                                    <td><?= htmlspecialchars(strtoupper($ens['nom_enseignant'] ?? '') . ' ' . ($ens['prenom_enseignant'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="cm-text-center">
                                        <span
                                            class="cm-badge cm-badge--primary"><?= (int) ($ens['nb_soutenances_jury'] ?? 0) ?></span>
                                    </td>
                                    <td class="cm-text-center">
                                        <span
                                            class="cm-badge cm-badge--success"><?= (int) ($ens['nb_soutenances_encadrees'] ?? 0) ?></span>
                                    </td>
                                    <td class="cm-text-center">
                                        <span
                                            class="cm-badge cm-badge--info"><?= (int) ($ens['nb_soutenances_dirigees'] ?? 0) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($enseignantsJuryPagination) && $enseignantsJuryPagination['total'] > 0): ?>
                    <div class="cm-mt-md">
                        <?= cm_component('crud/pagination', [
                            'pagination' => (object) $enseignantsJuryPagination,
                            'base_url' => '?page=dashboard&id_annee_acad=' . urlencode((string) ($filtreAnneeAdmin ?? '')) . '&id_session=' . urlencode((string) ($filtreSessionAdmin ?? ''))
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</section>
