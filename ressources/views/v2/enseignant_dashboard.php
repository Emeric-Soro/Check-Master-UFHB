<?php
$yearLabel = date('Y') . '-' . (date('Y') + 1);
$teacherId = '';
$teacherName = trim((string) ($_SESSION['nom_utilisateur'] ?? 'Enseignant'));
if ($teacherName === '') {
    $teacherName = 'Enseignant';
}

$filtreAnnee = isset($_GET['id_annee_acad']) && $_GET['id_annee_acad'] !== '' ? (int) $_GET['id_annee_acad'] : null;
$filtreSession = isset($_GET['id_session']) && $_GET['id_session'] !== '' ? (int) $_GET['id_session'] : null;

$anneeOptions = [];
$sessionOptions = [];
$qualitesJury = [];

try {
    if (!class_exists('Database')) {
        require_once dirname(__DIR__, 3) . '/app/config/database.php';
    }
    if (!class_exists('AnneeAcademique')) {
        require_once dirname(__DIR__, 3) . '/app/models/AnneeAcademique.php';
    }
    if (!class_exists('Enseignant')) {
        require_once dirname(__DIR__, 3) . '/app/models/Enseignant.php';
    }

    $pdo = Database::getConnection();
    $anneeModel = new AnneeAcademique($pdo);
    $anneeActive = $anneeModel->getAnneeAcademiqueActive();
    if ($anneeActive && is_object($anneeActive) && !empty($anneeActive->date_deb) && !empty($anneeActive->date_fin)) {
        $yearLabel = date('Y', strtotime((string) $anneeActive->date_deb)) . '-' . date('Y', strtotime((string) $anneeActive->date_fin));
        if ($filtreAnnee === null) {
            $filtreAnnee = (int) ($anneeActive->id_annee_acad ?? 0);
        }
    }

    $enseignantModel = new Enseignant($pdo);
    $enseignant = $enseignantModel->getEnseignantByLogin((string) ($_SESSION['login_utilisateur'] ?? ''));
    if ($enseignant && is_object($enseignant)) {
        $teacherId = (string) ($enseignant->id_enseignant ?? '');
        $fullName = trim((string) ($enseignant->nom_enseignant ?? '') . ' ' . (string) ($enseignant->prenom_enseignant ?? ''));
        if ($fullName !== '') {
            $teacherName = $fullName;
        }
    }

    // Options pour les filtres
    $stmtAnnees = $pdo->query("SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle FROM annee_academique ORDER BY date_deb DESC");
    $anneeOptions = $stmtAnnees->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $stmtSessions = $pdo->query("SELECT id_session, lib_session FROM session ORDER BY id_session");
    $sessionOptions = $stmtSessions->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Qualites de jury
    if ($teacherId !== '') {
        $juryTable = $pdo->query("SHOW TABLES LIKE 'enseignant_jury'")->fetchColumn() ? 'enseignant_jury' : 'composer_jury';
        $rolesTable = $pdo->query("SHOW TABLES LIKE 'qualite_jury'")->fetchColumn() ? 'qualite_jury' : 'roles_jury';
        $progTable = $pdo->query("SHOW TABLES LIKE 'programmer_soutenance'")->fetchColumn() ? 'programmer_soutenance' : 'programmer';

        $whereConditions = ["CAST(ej.id_enseignant AS CHAR) = :id_enseignant"];
        $params = [':id_enseignant' => $teacherId];

        if ($filtreAnnee !== null) {
            $whereConditions[] = "e.id_annee_acad = :id_annee_acad";
            $params[':id_annee_acad'] = $filtreAnnee;
        }

        if ($filtreSession !== null) {
            $whereConditions[] = "ps.id_session = :id_session";
            $params[':id_session'] = $filtreSession;
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sqlQualites = "SELECT
                            qj.id_role_jury,
                            qj.lib_role,
                            COUNT(DISTINCT ej.num_soutenance) AS total
                        FROM {$juryTable} ej
                        JOIN {$rolesTable} qj ON qj.id_role_jury = ej.id_qualite_jury
                        JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance
                        JOIN etudiants e ON e.num_carte_etud = ps.num_etud
                        WHERE {$whereClause}
                        GROUP BY qj.id_role_jury, qj.lib_role
                        ORDER BY qj.id_role_jury";
        $stmtQualites = $pdo->prepare($sqlQualites);
        $stmtQualites->execute($params);
        $qualitesJury = $stmtQualites->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    error_log('PRD8 enseignant dashboard filters error: ' . $e->getMessage());
}

$stats = [
    'rapports_a_evaluer' => 0,
    'soutenances_planifiees' => 0,
    'etudiants_encadres' => 0,
    'prochaine_soutenance' => 'Aucune',
];
$activityItems = [];

try {
    if (!class_exists('Database')) {
        require_once dirname(__DIR__, 3) . '/app/config/database.php';
    }
    $pdo = Database::getConnection();

    if ($teacherId !== '') {
        $progTable = $pdo->query("SHOW TABLES LIKE 'programmer_soutenance'")->fetchColumn() ? 'programmer_soutenance' : 'programmer';

        $whereReports = "CAST(a.id_enseignant AS CHAR) = :id_enseignant AND r.statut_rapport IN ('en_attente', 'en_cours')";
        $whereSoutenances = "CAST(ej.id_enseignant AS CHAR) = :id_enseignant AND CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW()";
        $whereStudents = "CAST(a.id_enseignant AS CHAR) = :id_enseignant AND a.role IN ('encadrant', 'directeur')";

        if ($filtreAnnee !== null) {
            $whereReports .= " AND EXISTS (SELECT 1 FROM etudiants e WHERE e.num_carte_etud = r.num_etu AND e.id_annee_acad = :id_annee_acad)";
            $whereSoutenances .= " AND e.id_annee_acad = :id_annee_acad";
            $whereStudents .= " AND EXISTS (SELECT 1 FROM etudiants e WHERE e.num_carte_etud = r.num_etu AND e.id_annee_acad = :id_annee_acad)";
        }

        if ($filtreSession !== null) {
            $whereSoutenances .= " AND ps.id_session = :id_session";
        }

        $stmtReports = $pdo->prepare("SELECT COUNT(DISTINCT a.id_rapport) AS total FROM affecter a INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport WHERE {$whereReports}");
        $stmtReports->execute($filtreAnnee !== null ? [':id_enseignant' => $teacherId, ':id_annee_acad' => $filtreAnnee] : [':id_enseignant' => $teacherId]);
        $stats['rapports_a_evaluer'] = (int) ($stmtReports->fetchColumn() ?: 0);

        $stmtSoutenances = $pdo->prepare("SELECT COUNT(DISTINCT ej.num_soutenance) AS total FROM enseignant_jury ej INNER JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance JOIN etudiants e ON e.num_carte_etud = ps.num_etud WHERE {$whereSoutenances}");
        $paramsSoutenances = [':id_enseignant' => $teacherId];
        if ($filtreAnnee !== null) $paramsSoutenances[':id_annee_acad'] = $filtreAnnee;
        if ($filtreSession !== null) $paramsSoutenances[':id_session'] = $filtreSession;
        $stmtSoutenances->execute($paramsSoutenances);
        $stats['soutenances_planifiees'] = (int) ($stmtSoutenances->fetchColumn() ?: 0);

        $stmtStudents = $pdo->prepare("SELECT COUNT(DISTINCT r.num_etu) AS total FROM affecter a INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport WHERE {$whereStudents}");
        $stmtStudents->execute($filtreAnnee !== null ? [':id_enseignant' => $teacherId, ':id_annee_acad' => $filtreAnnee] : [':id_enseignant' => $teacherId]);
        $stats['etudiants_encadres'] = (int) ($stmtStudents->fetchColumn() ?: 0);

        $stmtNext = $pdo->prepare("SELECT ps.date_soutenance, ps.heure_soutenance FROM enseignant_jury ej INNER JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance WHERE CAST(ej.id_enseignant AS CHAR) = :id_enseignant AND CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW() ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC LIMIT 1");
        $stmtNext->execute([':id_enseignant' => $teacherId]);
        $next = $stmtNext->fetch(PDO::FETCH_ASSOC);
        if (is_array($next) && !empty($next['date_soutenance'])) {
            $nextTs = strtotime((string) $next['date_soutenance'] . ' ' . (string) ($next['heure_soutenance'] ?? '00:00:00'));
            if ($nextTs !== false) {
                $stats['prochaine_soutenance'] = date('d/m/Y H:i', $nextTs);
            }
        }

        $timeline = [];

        $stmtRecentReports = $pdo->prepare("SELECT r.theme_rapport, r.date_redaction_rapport FROM affecter a INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport WHERE CAST(a.id_enseignant AS CHAR) = :id_enseignant ORDER BY r.date_redaction_rapport DESC LIMIT 5");
        $stmtRecentReports->execute([':id_enseignant' => $teacherId]);
        foreach (($stmtRecentReports->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $rawDate = (string) ($row['date_redaction_rapport'] ?? '');
            $stamp = strtotime($rawDate) ?: time();
            $timeline[] = [
                'ts' => $stamp,
                'type' => 'info',
                'icon' => 'fa-file-lines',
                'text' => 'Rapport recu: ' . (string) ($row['theme_rapport'] ?? 'Sans theme'),
                'time' => $rawDate !== '' ? date('d/m/Y H:i', $stamp) : '',
            ];
        }

        $stmtRecentSout = $pdo->prepare("SELECT ps.theme_soutenance, ps.date_soutenance, ps.heure_soutenance FROM enseignant_jury ej INNER JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance WHERE CAST(ej.id_enseignant AS CHAR) = :id_enseignant ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC LIMIT 5");
        $stmtRecentSout->execute([':id_enseignant' => $teacherId]);
        foreach (($stmtRecentSout->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $rawDate = trim((string) ($row['date_soutenance'] ?? '') . ' ' . (string) ($row['heure_soutenance'] ?? '00:00:00'));
            $stamp = strtotime($rawDate) ?: time();
            $timeline[] = [
                'ts' => $stamp,
                'type' => 'success',
                'icon' => 'fa-calendar-check',
                'text' => 'Soutenance programmee: ' . (string) ($row['theme_soutenance'] ?? 'Sans theme'),
                'time' => !empty($row['date_soutenance']) ? date('d/m/Y H:i', $stamp) : '',
            ];
        }

        usort($timeline, static function (array $a, array $b): int {
            return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0);
        });
        $activityItems = array_map(static function (array $item): array {
            return [
                'type' => (string) ($item['type'] ?? 'info'),
                'icon' => (string) ($item['icon'] ?? 'fa-bell'),
                'text' => (string) ($item['text'] ?? ''),
                'time' => (string) ($item['time'] ?? ''),
            ];
        }, array_slice($timeline, 0, 8));
    }
} catch (Throwable $e) {
    error_log('PRD8 enseignant dashboard data error: ' . $e->getMessage());
}

if ((int) $stats['etudiants_encadres'] === 0) {
    $stats['etudiants_encadres'] = (int) ($GLOBALS['total_etudiants'] ?? 0);
}

if (empty($activityItems)) {
    $mesCours = is_array($GLOBALS['mes_cours'] ?? null) ? $GLOBALS['mes_cours'] : [];
    foreach (array_slice($mesCours, 0, 6) as $cours) {
        $course = is_array($cours) ? $cours : (array) $cours;
        $activityItems[] = [
            'type' => 'info',
            'icon' => 'fa-book-open',
            'text' => 'Cours assigne: ' . (string) ($course['nom'] ?? 'Cours'),
            'time' => '',
        ];
    }
}

// Mapping des icones et couleurs pour les qualites de jury
$roleIcons = [
    'President' => ['icon' => 'fa-gavel', 'color' => 'primary'],
    'Directeur memoire' => ['icon' => 'fa-user-tie', 'color' => 'info'],
    'Examinateur' => ['icon' => 'fa-search', 'color' => 'warning'],
    'Encadrant' => ['icon' => 'fa-chalkboard-teacher', 'color' => 'success'],
    'Maitre de stage' => ['icon' => 'fa-building', 'color' => 'danger'],
];

function normalizeRoleName(string $role): string {
    $normalized = strtolower(trim($role));
    if (strpos($normalized, 'president') !== false) return 'President';
    if (strpos($normalized, 'directeur') !== false) return 'Directeur memoire';
    if (strpos($normalized, 'examina') !== false) return 'Examinateur';
    if (strpos($normalized, 'encadr') !== false) return 'Encadrant';
    if (strpos($normalized, 'maitre') !== false || strpos($normalized, 'stage') !== false) return 'Maitre de stage';
    return $role;
}
?>

<section class="cm-prd3-screen">
    <header class="cm-flex-between cm-mb-md">
        <div>
            <h2 class="cm-m-0 cm-text-xl cm-text-bold cm-text-primary">Bonjour, Pr. <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="cm-m-0 cm-text-muted">Synthese de vos activites pedagogiques.</p>
        </div>
        <span class="cm-toolbar-year">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <?= htmlspecialchars($yearLabel, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </header>

    <div class="cm-card cm-mb-md">
        <form method="GET" class="cm-grid-3" style="align-items: end;">
            <input type="hidden" name="page" value="tableau_bord_enseignant">

            <?= cm_component('form/select', [
                'name' => 'id_annee_acad',
                'label' => 'Annee academique',
                'options' => $anneeOptions,
                'selected' => (string)($filtreAnnee ?? ''),
                'placeholder' => 'Toutes les annees'
            ]) ?>

            <?= cm_component('form/select', [
                'name' => 'id_session',
                'label' => 'Periode',
                'options' => $sessionOptions,
                'selected' => (string)($filtreSession ?? ''),
                'placeholder' => 'Toutes les periodes'
            ]) ?>

            <div class="cm-flex cm-flex-gap-sm">
                <button type="submit" class="cm-btn cm-btn--primary">
                    <i class="fas fa-filter cm-mr-sm"></i> Filtrer
                </button>
                <a href="?page=tableau_bord_enseignant" class="cm-btn cm-btn--outline">
                    Reinitialiser
                </a>
            </div>
        </form>
    </div>

    <div class="cm-grid-4">
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int) $stats['rapports_a_evaluer'], 0, ',', ' '), 'label' => 'Rapports a evaluer', 'icon' => 'fa-file-circle-check', 'color' => 'warning']); ?>
            <a class="cm-stat-card__link" href="?page=rapport_a_valider" data-cm-ajax-link="true">Voir ▸</a>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int) $stats['soutenances_planifiees'], 0, ',', ' '), 'label' => 'Soutenances planifiees', 'icon' => 'fa-calendar-check', 'color' => 'info']); ?>
            <a class="cm-stat-card__link" href="?page=programmation_soutenance" data-cm-ajax-link="true">Voir ▸</a>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int) $stats['etudiants_encadres'], 0, ',', ' '), 'label' => 'Etudiants encadres', 'icon' => 'fa-users', 'color' => 'success']); ?>
            <a class="cm-stat-card__link" href="?page=liste_etudiants_ens" data-cm-ajax-link="true">Liste ▸</a>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => (string) $stats['prochaine_soutenance'], 'label' => 'Prochaine soutenance', 'icon' => 'fa-clock', 'color' => 'primary']); ?>
        </div>
    </div>

    <?php if (!empty($qualitesJury)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title">
                    <i class="fas fa-gavel cm-mr-sm"></i>
                    Mes qualites de jury
                </h3>
            </div>
            <div class="cm-card__body">
                <div class="cm-grid-<?= min(count($qualitesJury), 5) ?>">
                    <?php foreach ($qualitesJury as $qualite): 
                        $roleKey = normalizeRoleName($qualite['lib_role'] ?? '');
                        $icon = $roleIcons[$roleKey]['icon'] ?? 'fa-user';
                        $color = $roleIcons[$roleKey]['color'] ?? 'info';
                    ?>
                        <?php cm_component('dashboard/stat-widget', [
                            'value' => number_format((int) ($qualite['total'] ?? 0), 0, ',', ' '),
                            'label' => htmlspecialchars($qualite['lib_role'] ?? '', ENT_QUOTES, 'UTF-8'),
                            'subtitle' => 'soutenance(s)',
                            'icon' => $icon,
                            'color' => $color
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php elseif ($teacherId !== ''): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__body">
                <?= cm_component('ui/empty-state', [
                    'title' => 'Aucune participation a un jury',
                    'message' => 'Aucune participation a un jury pour les criteres selectionnes.',
                    'icon' => 'fa-users-slash',
                ]) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="cm-grid-2 cm-mt-md">
        <?php cm_component('dashboard/activity-list', ['title' => 'Activites recentes', 'items' => $activityItems]); ?>

        <div class="cm-chart-container">
            <div class="cm-chart-container__header">
                <h3 class="cm-chart-container__title">Actions rapides</h3>
                <p class="cm-chart-container__subtitle">Navigation directe</p>
            </div>
            <div class="cm-chart-container__body">
                <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                    <a class="cm-btn is-info" href="?page=rapport_a_valider" data-cm-ajax-link="true">
                        <i class="fas fa-file-signature" aria-hidden="true"></i>
                        Evaluations
                    </a>
                    <a class="cm-btn is-primary" href="?page=programmation_soutenance" data-cm-ajax-link="true">
                        <i class="fas fa-calendar-days" aria-hidden="true"></i>
                        Planning soutenances
                    </a>
                    <a class="cm-btn is-success" href="?page=liste_etudiants_ens" data-cm-ajax-link="true">
                        <i class="fas fa-list" aria-hidden="true"></i>
                        Liste etudiants
                    </a>
                    <a class="cm-btn is-warning" href="?page=repertoire_enseignant" data-cm-ajax-link="true">
                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                        Repertoire documents
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
