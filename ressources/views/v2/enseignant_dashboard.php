<?php
$yearLabel = date('Y') . '-' . (date('Y') + 1);
$teacherId = '';
$teacherName = trim((string) ($_SESSION['nom_utilisateur'] ?? 'Enseignant'));
if ($teacherName === '') {
    $teacherName = 'Enseignant';
}

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

    $anneeModel = new AnneeAcademique(Database::getConnection());
    $anneeActive = $anneeModel->getAnneeAcademiqueActive();
    if ($anneeActive && is_object($anneeActive) && !empty($anneeActive->date_deb) && !empty($anneeActive->date_fin)) {
        $yearLabel = date('Y', strtotime((string) $anneeActive->date_deb)) . '-' . date('Y', strtotime((string) $anneeActive->date_fin));
    }

    $enseignantModel = new Enseignant(Database::getConnection());
    $enseignant = $enseignantModel->getEnseignantByLogin((string) ($_SESSION['login_utilisateur'] ?? ''));
    if ($enseignant && is_object($enseignant)) {
        $teacherId = (string) ($enseignant->id_enseignant ?? '');
        $fullName = trim((string) ($enseignant->nom_enseignant ?? '') . ' ' . (string) ($enseignant->prenom_enseignant ?? ''));
        if ($fullName !== '') {
            $teacherName = $fullName;
        }
    }
} catch (Throwable $e) {
    error_log('PRD5 enseignant header fallback: ' . $e->getMessage());
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
        $stmtReports = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.id_rapport) AS total
             FROM affecter a
             INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
             WHERE CAST(a.id_enseignant AS CHAR) = :id_enseignant
               AND r.statut_rapport IN ('en_attente', 'en_cours')"
        );
        $stmtReports->execute([':id_enseignant' => $teacherId]);
        $stats['rapports_a_evaluer'] = (int) ($stmtReports->fetchColumn() ?: 0);

        $stmtSoutenances = $pdo->prepare(
            "SELECT COUNT(DISTINCT ej.num_soutenance) AS total
             FROM enseignant_jury ej
             INNER JOIN programmer_soutenance ps ON ps.num_soutenance = ej.num_soutenance
             WHERE CAST(ej.id_enseignant AS CHAR) = :id_enseignant
               AND CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW()"
        );
        $stmtSoutenances->execute([':id_enseignant' => $teacherId]);
        $stats['soutenances_planifiees'] = (int) ($stmtSoutenances->fetchColumn() ?: 0);

        $stmtStudents = $pdo->prepare(
            "SELECT COUNT(DISTINCT r.num_etu) AS total
             FROM affecter a
             INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
             WHERE CAST(a.id_enseignant AS CHAR) = :id_enseignant
               AND a.role IN ('encadrant', 'directeur')"
        );
        $stmtStudents->execute([':id_enseignant' => $teacherId]);
        $stats['etudiants_encadres'] = (int) ($stmtStudents->fetchColumn() ?: 0);

        $stmtNext = $pdo->prepare(
            "SELECT ps.date_soutenance, ps.heure_soutenance
             FROM enseignant_jury ej
             INNER JOIN programmer_soutenance ps ON ps.num_soutenance = ej.num_soutenance
             WHERE CAST(ej.id_enseignant AS CHAR) = :id_enseignant
               AND CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW()
             ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC
             LIMIT 1"
        );
        $stmtNext->execute([':id_enseignant' => $teacherId]);
        $next = $stmtNext->fetch(PDO::FETCH_ASSOC);
        if (is_array($next) && !empty($next['date_soutenance'])) {
            $nextTs = strtotime((string) $next['date_soutenance'] . ' ' . (string) ($next['heure_soutenance'] ?? '00:00:00'));
            if ($nextTs !== false) {
                $stats['prochaine_soutenance'] = date('d/m/Y H:i', $nextTs);
            }
        }

        $timeline = [];

        $stmtRecentReports = $pdo->prepare(
            "SELECT r.theme_rapport, r.date_redaction_rapport
             FROM affecter a
             INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
             WHERE CAST(a.id_enseignant AS CHAR) = :id_enseignant
             ORDER BY r.date_redaction_rapport DESC
             LIMIT 5"
        );
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

        $stmtRecentSout = $pdo->prepare(
            "SELECT ps.theme_soutenance, ps.date_soutenance, ps.heure_soutenance
             FROM enseignant_jury ej
             INNER JOIN programmer_soutenance ps ON ps.num_soutenance = ej.num_soutenance
             WHERE CAST(ej.id_enseignant AS CHAR) = :id_enseignant
             ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC
             LIMIT 5"
        );
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
    error_log('PRD5 enseignant dashboard data error: ' . $e->getMessage());
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
                </div>
            </div>
        </div>
    </div>
</section>
