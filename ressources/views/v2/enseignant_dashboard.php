<?php
$yearLabel = \AcademicYear::getSelectedLabelFromSession();
if ($yearLabel === '') {
    $yearLabel = date('Y') . '-' . (date('Y') + 1);
}
$teacherId = '';
$teacherName = trim((string) ($_SESSION['nom_utilisateur'] ?? 'Enseignant'));
if ($teacherName === '') {
    $teacherName = 'Enseignant';
}

// Vérifier si l'utilisateur est administrateur
$isAdmin = false;
$libGU = strtolower(trim((string) ($_SESSION['lib_GU'] ?? '')));
if (strpos($libGU, 'admin') !== false) {
    $isAdmin = true;
}

$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$filtreAnnee = isset($_GET['id_annee_acad']) && $_GET['id_annee_acad'] !== '' ? (int) $_GET['id_annee_acad'] : \AcademicYear::getSelectedIdFromSession();
$filtreSession = isset($_GET['id_session']) && $_GET['id_session'] !== '' ? (int) $_GET['id_session'] : null;
$filtreQualiteJury = isset($_GET['id_qualite_jury']) && $_GET['id_qualite_jury'] !== '' ? (int) $_GET['id_qualite_jury'] : null;
$enseignantSelectionne = isset($_GET['id_enseignant_selected']) && $_GET['id_enseignant_selected'] !== '' ? (int) $_GET['id_enseignant_selected'] : null;

$anneeOptions = [];
$sessionOptions = [];
$qualiteJuryOptions = [];
$enseignantOptions = [];
$qualitesJury = [];
$listeEtudiants = [];
$soutenancesProgrammees = [];

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
        if ($yearLabel === '') {
            $yearLabel = date('Y', strtotime((string) $anneeActive->date_deb)) . '-' . date('Y', strtotime((string) $anneeActive->date_fin));
        }
        if ($filtreAnnee === null && !$allYearsSelected) {
            $filtreAnnee = (int) ($anneeActive->id_annee_acad ?? 0);
        }
    }

    $enseignantModel = new Enseignant($pdo);
    
    // Si admin, charger la liste de tous les enseignants
    if ($isAdmin) {
        $tousEnseignants = $enseignantModel->getAllEnseignants();
        foreach ($tousEnseignants as $ens) {
            $enseignantOptions[$ens->id_enseignant] = trim($ens->nom_enseignant . ' ' . $ens->prenom_enseignant);
        }
    }
    
    // Déterminer quel enseignant afficher
    if ($isAdmin && $enseignantSelectionne !== null) {
        // Admin a sélectionné un enseignant
        $enseignant = $enseignantModel->getEnseignantById($enseignantSelectionne);
        if ($enseignant && is_object($enseignant)) {
            $teacherId = (string) ($enseignant->id_enseignant ?? '');
            $fullName = trim((string) ($enseignant->nom_enseignant ?? '') . ' ' . (string) ($enseignant->prenom_enseignant ?? ''));
            if ($fullName !== '') {
                $teacherName = $fullName;
            }
        }
    } else {
        // Enseignant connecté ou admin sans sélection
        $enseignant = $enseignantModel->getEnseignantByLogin((string) ($_SESSION['login_utilisateur'] ?? ''));
        if ($enseignant && is_object($enseignant)) {
            $teacherId = (string) ($enseignant->id_enseignant ?? '');
            $fullName = trim((string) ($enseignant->nom_enseignant ?? '') . ' ' . (string) ($enseignant->prenom_enseignant ?? ''));
            if ($fullName !== '') {
                $teacherName = $fullName;
            }
        }
    }

    // Options pour les filtres
    $stmtAnnees = $pdo->query("SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle FROM annee_academique ORDER BY date_deb DESC");
    $anneeOptions = $stmtAnnees->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $stmtSessions = $pdo->query("SELECT id_session, lib_session FROM session ORDER BY id_session");
    $sessionOptions = $stmtSessions->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Options qualités de jury
    $stmtQualites = $pdo->query("SELECT id_role_jury, lib_role FROM qualite_jury ORDER BY id_role_jury");
    $qualiteJuryOptions = $stmtQualites->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // Qualites de jury - Récupérer TOUS les rôles avec les comptes (même 0)
    if ($teacherId !== '') {
        $juryTable = $pdo->query("SHOW TABLES LIKE 'enseignant_jury'")->fetchColumn() ? 'enseignant_jury' : 'composer_jury';
        $rolesTable = $pdo->query("SHOW TABLES LIKE 'qualite_jury'")->fetchColumn() ? 'qualite_jury' : 'roles_jury';
        $progTable = $pdo->query("SHOW TABLES LIKE 'programmer_soutenance'")->fetchColumn() ? 'programmer_soutenance' : 'programmer';

        $whereConditions = ["CAST(ej.id_enseignant AS CHAR) = :id_enseignant"];
        $params = [':id_enseignant' => $teacherId];

        if ($filtreAnnee !== null) {
            $whereConditions[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad)";
            $params[':id_annee_acad'] = $filtreAnnee;
        }

        if ($filtreSession !== null) {
            $whereConditions[] = "ps.id_session = :id_session";
            $params[':id_session'] = $filtreSession;
        }

        if ($filtreQualiteJury !== null) {
            $whereConditions[] = "ej.id_qualite_jury = :id_qualite_jury";
            $params[':id_qualite_jury'] = $filtreQualiteJury;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Utiliser un LEFT JOIN pour avoir tous les rôles même avec compte 0
        $sqlQualites = "SELECT
                            qj.id_role_jury,
                            qj.lib_role,
                            COUNT(DISTINCT ej.num_soutenance) AS total
                        FROM {$rolesTable} qj
                        LEFT JOIN {$juryTable} ej ON qj.id_role_jury = ej.id_qualite_jury AND CAST(ej.id_enseignant AS CHAR) = :id_enseignant
                        LEFT JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance
                        LEFT JOIN etudiants e ON e.num_carte_etud = ps.num_etud
                        WHERE 1=1
                        " . ($filtreAnnee !== null ? "AND (ej.num_soutenance IS NULL OR EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad))" : "") . "
                        " . ($filtreSession !== null ? "AND (ej.num_soutenance IS NULL OR ps.id_session = :id_session)" : "") . "
                        " . ($filtreQualiteJury !== null ? "AND (ej.num_soutenance IS NULL OR ej.id_qualite_jury = :id_qualite_jury)" : "") . "
                        GROUP BY qj.id_role_jury, qj.lib_role
                        ORDER BY qj.id_role_jury";
        $stmtQualites = $pdo->prepare($sqlQualites);
        $stmtQualites->execute($params);
        $qualitesJury = $stmtQualites->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Tableau des étudiants avec tous les rôles de jury
        $whereEtudiants = ["CAST(ej_main.id_enseignant AS CHAR) = :id_enseignant"];
        $paramsEtudiants = [':id_enseignant' => $teacherId];
        
        if ($filtreAnnee !== null) {
            $whereEtudiants[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad)";
            $paramsEtudiants[':id_annee_acad'] = $filtreAnnee;
        }
        
        if ($filtreSession !== null) {
            $whereEtudiants[] = "ps.id_session = :id_session";
            $paramsEtudiants[':id_session'] = $filtreSession;
        }

        if ($filtreQualiteJury !== null) {
            $whereEtudiants[] = "ej_main.id_qualite_jury = :id_qualite_jury";
            $paramsEtudiants[':id_qualite_jury'] = $filtreQualiteJury;
        }
        
        $whereEtudiantsClause = implode(' AND ', $whereEtudiants);

        $sqlEtudiants = "SELECT DISTINCT
                            e.num_carte_etud,
                            e.nom_etu AS nom_etud,
                            e.prenom_etu AS prenom_etud,
                            ps.theme_soutenance,
                            ps.num_soutenance,
                            (SELECT GROUP_CONCAT(CONCAT(en.nom_enseignant, ' ', en.prenom_enseignant) SEPARATOR ', ')
                             FROM {$juryTable} ej 
                             JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                             WHERE ej.num_soutenance = ps.num_soutenance AND ej.id_qualite_jury = 1) AS president,
                            (SELECT GROUP_CONCAT(CONCAT(en.nom_enseignant, ' ', en.prenom_enseignant) SEPARATOR ', ')
                             FROM {$juryTable} ej 
                             JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                             WHERE ej.num_soutenance = ps.num_soutenance AND ej.id_qualite_jury = 2) AS directeur_memoire,
                            (SELECT GROUP_CONCAT(CONCAT(en.nom_enseignant, ' ', en.prenom_enseignant) SEPARATOR ', ')
                             FROM {$juryTable} ej 
                             JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                             WHERE ej.num_soutenance = ps.num_soutenance AND ej.id_qualite_jury = 3) AS examinateur,
                            (SELECT GROUP_CONCAT(CONCAT(en.nom_enseignant, ' ', en.prenom_enseignant) SEPARATOR ', ')
                             FROM {$juryTable} ej 
                             JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                             WHERE ej.num_soutenance = ps.num_soutenance AND ej.id_qualite_jury = 4) AS encadrant,
                            (SELECT GROUP_CONCAT(CONCAT(en.nom_enseignant, ' ', en.prenom_enseignant) SEPARATOR ', ')
                             FROM {$juryTable} ej 
                             JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                             WHERE ej.num_soutenance = ps.num_soutenance AND ej.id_qualite_jury = 5) AS maitre_stage
                        FROM {$juryTable} ej_main
                        JOIN {$progTable} ps ON ps.num_soutenance = ej_main.num_soutenance
                        JOIN etudiants e ON e.num_carte_etud = ps.num_etud
                        WHERE {$whereEtudiantsClause}
                        ORDER BY e.nom_etu, e.prenom_etu";
        
        $stmtEtudiants = $pdo->prepare($sqlEtudiants);
        $stmtEtudiants->execute($paramsEtudiants);
        $listeEtudiants = $stmtEtudiants->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Soutenances à venir pour cet enseignant
        $whereSoutenances = ["CAST(ej.id_enseignant AS CHAR) = :id_enseignant", "ps.date_soutenance IS NOT NULL", "CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW()"];
        $paramsSoutenances = [':id_enseignant' => $teacherId];
        
        if ($filtreSession !== null) {
            $whereSoutenances[] = "ps.id_session = :id_session";
            $paramsSoutenances[':id_session'] = $filtreSession;
        }

        if ($filtreQualiteJury !== null) {
            $whereSoutenances[] = "ej.id_qualite_jury = :id_qualite_jury";
            $paramsSoutenances[':id_qualite_jury'] = $filtreQualiteJury;
        }
        
        $whereSoutenancesClause = implode(' AND ', $whereSoutenances);
        
        $sqlSoutenances = "SELECT DISTINCT
                            ps.date_soutenance,
                            ps.heure_soutenance,
                            e.num_carte_etud,
                            CONCAT(e.nom_etu, ' ', e.prenom_etu) AS nom_complet_etudiant,
                            ps.theme_soutenance,
                            qj.lib_role,
                            qj.code_qltjury,
                            s.lib_salle AS nom_salle
                        FROM {$juryTable} ej
                        JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance
                        JOIN etudiants e ON e.num_carte_etud = ps.num_etud
                        JOIN {$rolesTable} qj ON qj.id_role_jury = ej.id_qualite_jury
                        LEFT JOIN salles s ON s.id_salle = ps.id_salle
                        WHERE {$whereSoutenancesClause}
                        ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC";
        
        $stmtSoutenances = $pdo->prepare($sqlSoutenances);
        $stmtSoutenances->execute($paramsSoutenances);
        $soutenancesProgrammees = $stmtSoutenances->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
        $juryTable = $pdo->query("SHOW TABLES LIKE 'enseignant_jury'")->fetchColumn() ? 'enseignant_jury' : 'composer_jury';

        $whereReports = "CAST(a.id_enseignant AS CHAR) = :id_enseignant AND r.statut_rapport IN ('en_attente', 'en_cours')";
        $whereSoutenances = "CAST(ej.id_enseignant AS CHAR) = :id_enseignant AND CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW()";
        $whereStudents = "CAST(a.id_enseignant AS CHAR) = :id_enseignant AND a.role IN ('encadrant', 'directeur')";

        if ($filtreAnnee !== null) {
            $whereReports .= " AND EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = r.num_etu AND i.id_annee_acad = :id_annee_acad)";
            $whereSoutenances .= " AND EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad)";
            $whereStudents .= " AND EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = r.num_etu AND i.id_annee_acad = :id_annee_acad)";
        }

        if ($filtreSession !== null) {
            $whereSoutenances .= " AND ps.id_session = :id_session";
        }

        $stmtReports = $pdo->prepare("SELECT COUNT(DISTINCT a.id_rapport) AS total FROM affecter a INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport WHERE {$whereReports}");
        $stmtReports->execute($filtreAnnee !== null ? [':id_enseignant' => $teacherId, ':id_annee_acad' => $filtreAnnee] : [':id_enseignant' => $teacherId]);
        $stats['rapports_a_evaluer'] = (int) ($stmtReports->fetchColumn() ?: 0);

        $stmtSoutenances = $pdo->prepare("SELECT COUNT(DISTINCT ej.num_soutenance) AS total FROM {$juryTable} ej INNER JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance JOIN etudiants e ON e.num_carte_etud = ps.num_etud WHERE {$whereSoutenances}");
        $paramsSoutenances = [':id_enseignant' => $teacherId];
        if ($filtreAnnee !== null) $paramsSoutenances[':id_annee_acad'] = $filtreAnnee;
        if ($filtreSession !== null) $paramsSoutenances[':id_session'] = $filtreSession;
        $stmtSoutenances->execute($paramsSoutenances);
        $stats['soutenances_planifiees'] = (int) ($stmtSoutenances->fetchColumn() ?: 0);

        $stmtStudents = $pdo->prepare("SELECT COUNT(DISTINCT r.num_etu) AS total FROM affecter a INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport WHERE {$whereStudents}");
        $stmtStudents->execute($filtreAnnee !== null ? [':id_enseignant' => $teacherId, ':id_annee_acad' => $filtreAnnee] : [':id_enseignant' => $teacherId]);
        $stats['etudiants_encadres'] = (int) ($stmtStudents->fetchColumn() ?: 0);

        $whereNext = [
            "CAST(ej.id_enseignant AS CHAR) = :id_enseignant",
            "CONCAT(ps.date_soutenance, ' ', COALESCE(ps.heure_soutenance, '00:00:00')) >= NOW()",
        ];
        $paramsNext = [':id_enseignant' => $teacherId];
        if ($filtreAnnee !== null) {
            $whereNext[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad)";
            $paramsNext[':id_annee_acad'] = $filtreAnnee;
        }
        if ($filtreSession !== null) {
            $whereNext[] = "ps.id_session = :id_session";
            $paramsNext[':id_session'] = $filtreSession;
        }
        $stmtNext = $pdo->prepare("SELECT ps.date_soutenance, ps.heure_soutenance FROM {$juryTable} ej INNER JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance INNER JOIN etudiants e ON e.num_carte_etud = ps.num_etud WHERE " . implode(' AND ', $whereNext) . " ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC LIMIT 1");
        $stmtNext->execute($paramsNext);
        $next = $stmtNext->fetch(PDO::FETCH_ASSOC);
        if (is_array($next) && !empty($next['date_soutenance'])) {
            $nextTs = strtotime((string) $next['date_soutenance'] . ' ' . (string) ($next['heure_soutenance'] ?? '00:00:00'));
            if ($nextTs !== false) {
                $stats['prochaine_soutenance'] = date('d/m/Y H:i', $nextTs);
            }
        }

        $timeline = [];

        $whereRecentReports = ["CAST(a.id_enseignant AS CHAR) = :id_enseignant"];
        $paramsRecentReports = [':id_enseignant' => $teacherId];
        if ($filtreAnnee !== null) {
            $whereRecentReports[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = r.num_etu AND i.id_annee_acad = :id_annee_acad)";
            $paramsRecentReports[':id_annee_acad'] = $filtreAnnee;
        }
        $stmtRecentReports = $pdo->prepare("SELECT r.theme_rapport, r.date_redaction_rapport FROM affecter a INNER JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport WHERE " . implode(' AND ', $whereRecentReports) . " ORDER BY r.date_redaction_rapport DESC LIMIT 5");
        $stmtRecentReports->execute($paramsRecentReports);
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

        $whereRecentSout = ["CAST(ej.id_enseignant AS CHAR) = :id_enseignant"];
        $paramsRecentSout = [':id_enseignant' => $teacherId];
        if ($filtreAnnee !== null) {
            $whereRecentSout[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE i.id_etudiant = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad)";
            $paramsRecentSout[':id_annee_acad'] = $filtreAnnee;
        }
        if ($filtreSession !== null) {
            $whereRecentSout[] = "ps.id_session = :id_session";
            $paramsRecentSout[':id_session'] = $filtreSession;
        }
        $stmtRecentSout = $pdo->prepare("SELECT ps.theme_soutenance, ps.date_soutenance, ps.heure_soutenance FROM {$juryTable} ej INNER JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance INNER JOIN etudiants e ON e.num_carte_etud = ps.num_etud WHERE " . implode(' AND ', $whereRecentSout) . " ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC LIMIT 5");
        $stmtRecentSout->execute($paramsRecentSout);
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
    <?php if ($isAdmin): ?>
        <div class="cm-card cm-mb-md">
            <div class="cm-card__body">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-user-shield" style="font-size: 24px;"></i>
                    <div>
                        <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">
                            <?php if ($enseignantSelectionne !== null): ?>
                                Consultation des données de : <strong><?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php else: ?>
                                Sélectionnez un enseignant pour consulter ses informations
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="cm-card cm-mb-md">
        <form method="GET" style="align-items: end;">
            <input type="hidden" name="page" value="tableau_bord_enseignant">

            <input type="hidden" name="id_annee_acad" value="<?= htmlspecialchars((string) (\AcademicYear::getWritableIdFromSession() ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <?php if ($isAdmin): ?>
                <div class="cm-mb-md" style="max-width: 380px;">
                    <?= cm_component('form/select', [
                        'name' => 'id_enseignant_selected',
                        'label' => 'Enseignant à consulter',
                        'options' => $enseignantOptions,
                        'selected' => (string)($enseignantSelectionne ?? ''),
                        'placeholder' => 'Sélectionner un enseignant'
                    ]) ?>
                </div>
            <?php endif; ?>

            <div class="cm-grid-3 cm-mb-md">
                <?= cm_component('form/select', [
                    'name' => 'id_session',
                    'label' => 'Session',
                    'options' => $sessionOptions,
                    'selected' => (string)($filtreSession ?? ''),
                    'placeholder' => 'Toutes les sessions'
                ]) ?>

                <?= cm_component('form/select', [
                    'name' => 'id_qualite_jury',
                    'label' => 'Qualité de jury',
                    'options' => $qualiteJuryOptions,
                    'selected' => (string)($filtreQualiteJury ?? ''),
                    'placeholder' => 'Toutes les qualités'
                ]) ?>

                <div class="cm-flex cm-flex-gap-sm">
                    <button type="submit" class="cm-btn cm-btn--primary">
                        <i class="fas fa-filter cm-mr-sm"></i> Filtrer
                    </button>
                    <a href="?page=tableau_bord_enseignant" class="cm-btn cm-btn--outline">
                        Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="cm-grid-4">
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int) $stats['soutenances_planifiees'], 0, ',', ' '), 'label' => 'Soutenances à venir', 'icon' => 'fa-calendar-check', 'color' => 'info']); ?>
            <?php if (!empty($soutenancesProgrammees)): ?>
                <a class="cm-stat-card__link" href="#section-soutenances-a-venir" onclick="document.getElementById('section-soutenances-a-venir').scrollIntoView({behavior: 'smooth', block: 'start'}); return false;">Voir ▸</a>
            <?php endif; ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int) $stats['etudiants_encadres'], 0, ',', ' '), 'label' => 'Étudiants encadrés', 'icon' => 'fa-users', 'color' => 'success']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => (string) $stats['prochaine_soutenance'], 'label' => 'Prochaine soutenance', 'icon' => 'fa-clock', 'color' => 'primary']); ?>
        </div>
    </div>

    <?php if (!empty($qualitesJury) && $teacherId !== ''): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title">
                    <i class="fas fa-user-tie cm-mr-sm"></i>
                    <?php if ($isAdmin && $enseignantSelectionne !== null): ?>
                        Participations aux jurys de <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        Mes participations aux jurys
                    <?php endif; ?>
                </h3>
                <p class="cm-text-muted"><small>Nombre de soutenances par rôle</small></p>
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
                    'title' => '',
                    'message' => 'Aucune participation a un jury pour les critères sélectionnés.',
                    'icon' => 'fa-users-slash',
                ]) ?>
            </div>
        </div>
    <?php elseif ($isAdmin && $enseignantSelectionne === null): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__body">
                <?= cm_component('ui/empty-state', [
                    'title' => 'Aucun enseignant sélectionné',
                    'message' => 'Veuillez sélectionner un enseignant dans le filtre ci-dessus pour consulter ses informations.',
                    'icon' => 'fa-user-circle',
                ]) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($listeEtudiants)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title">
                    <i class="fas fa-users-cog cm-mr-sm"></i>
                    Liste des étudiants et composition du jury
                </h3>
            </div>
            <div class="cm-card__body">
                <div style="overflow-x: auto;">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>N° Étudiant</th>
                                <th>Nom et Prénom</th>
                                <th>Thème</th>
                                <th>Président</th>
                                <th>Directeur mémoire</th>
                                <th>Examinateur</th>
                                <th>Encadrant</th>
                                <th>Maître de stage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listeEtudiants as $etudiant): 
                                $teacherFullName = $teacherName ?? '';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($etudiant['num_carte_etud'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(($etudiant['nom_etud'] ?? '') . ' ' . ($etudiant['prenom_etud'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small><?= htmlspecialchars($etudiant['theme_soutenance'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td <?php 
                                        $president = $etudiant['president'] ?? '';
                                        if ($president !== '' && stripos($president, $teacherFullName) !== false) {
                                            echo 'style="background-color: #e3f2fd; font-weight: bold;"';
                                        }
                                    ?>><?= htmlspecialchars($president, ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                                    <td <?php 
                                        $directeur = $etudiant['directeur_memoire'] ?? '';
                                        if ($directeur !== '' && stripos($directeur, $teacherFullName) !== false) {
                                            echo 'style="background-color: #e3f2fd; font-weight: bold;"';
                                        }
                                    ?>><?= htmlspecialchars($directeur, ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                                    <td <?php 
                                        $examinateur = $etudiant['examinateur'] ?? '';
                                        if ($examinateur !== '' && stripos($examinateur, $teacherFullName) !== false) {
                                            echo 'style="background-color: #e3f2fd; font-weight: bold;"';
                                        }
                                    ?>><?= htmlspecialchars($examinateur, ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                                    <td <?php 
                                        $encadrant = $etudiant['encadrant'] ?? '';
                                        if ($encadrant !== '' && stripos($encadrant, $teacherFullName) !== false) {
                                            echo 'style="background-color: #e3f2fd; font-weight: bold;"';
                                        }
                                    ?>><?= htmlspecialchars($encadrant, ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                                    <td <?php 
                                        $maitre = $etudiant['maitre_stage'] ?? '';
                                        if ($maitre !== '' && stripos($maitre, $teacherFullName) !== false) {
                                            echo 'style="background-color: #e3f2fd; font-weight: bold;"';
                                        }
                                    ?>><?= htmlspecialchars($maitre, ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($soutenancesProgrammees)): ?>
        <div class="cm-card cm-mt-md" id="section-soutenances-a-venir">
            <div class="cm-card__header">
                <h3 class="cm-card__title">
                    <i class="fas fa-calendar-alt cm-mr-sm"></i>
                    Soutenances à venir
                </h3>
                <p class="cm-text-muted"><small>
                    <?php if ($isAdmin && $enseignantSelectionne !== null): ?>
                        Soutenances où <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?> est membre du jury
                    <?php else: ?>
                        Soutenances où vous êtes membre du jury
                    <?php endif; ?>
                </small></p>
            </div>
            <div class="cm-card__body">
                <div style="overflow-x: auto;">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>N° Étudiant</th>
                                <th>Étudiant</th>
                                <th>Thème</th>
                                <th><?php echo ($isAdmin && $enseignantSelectionne !== null) ? 'Rôle' : 'Votre rôle'; ?></th>
                                <th>Salle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($soutenancesProgrammees as $soutenance): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($soutenance['date_soutenance'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($soutenance['heure_soutenance'] ?? '00:00:00')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($soutenance['num_carte_etud'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($soutenance['nom_complet_etudiant'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small><?= htmlspecialchars($soutenance['theme_soutenance'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td>
                                        <span class="cm-badge cm-badge--<?php 
                                            $code = $soutenance['code_qltjury'] ?? '';
                                            echo $code === 'PJ' ? 'primary' : ($code === 'DM' ? 'info' : ($code === 'EX' ? 'warning' : ($code === 'EN' ? 'success' : 'danger')));
                                        ?>">
                                            <?= htmlspecialchars($soutenance['lib_role'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($soutenance['nom_salle'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cm-mt-md">
                    <?php 
                        $exportParams = [
                            'id_session' => $filtreSession, 
                            'id_qualite_jury' => $filtreQualiteJury
                        ];
                        if ($isAdmin && $enseignantSelectionne !== null) {
                            $exportParams['id_enseignant_selected'] = $enseignantSelectionne;
                        }
                    ?>
                    <a href="../ressources/views/v2/export_planning_enseignant_pdf.php?<?= http_build_query($exportParams) ?>" 
                       class="cm-btn cm-btn--primary" 
                       target="_blank">
                        <i class="fas fa-file-pdf cm-mr-sm"></i>
                        Télécharger le planning (PDF)
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="cm-grid-2 cm-mt-md">
        <?php cm_component('dashboard/activity-list', ['title' => '', 'items' => $activityItems]); ?>
    </div>
</section>
