<?php
/**
 * Routeur pour l'annuaire des enseignants
 * Utilise RepertoireEnseignantService enrichi avec filtres
 */

declare(strict_types=1);
if ((string) ($_GET['page'] ?? '') !== 'annuaire_enseignants') {
    return;
}

try {
    $db = Database::getConnection();

    // Recuperer les filtres
    $filtreGrade = isset($_GET['grade']) ? (int) $_GET['grade'] : null;
    $filtreSpecialite = isset($_GET['specialite']) ? (int) $_GET['specialite'] : null;
    $filtreType = isset($_GET['type_enseignant']) ? (int) $_GET['type_enseignant'] : null;
    $search = trim((string) ($_GET['search'] ?? ''));

    $page = max(1, (int) ($_GET['p'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    // Requete avec filtres
    $where = [];
    $params = [];

    if ($filtreGrade !== null && $filtreGrade > 0) {
        $where[] = "a.id_grade = :grade";
        $params[':grade'] = $filtreGrade;
    }
    if ($filtreSpecialite !== null && $filtreSpecialite > 0) {
        $where[] = "ens.id_specialite = :specialite";
        $params[':specialite'] = $filtreSpecialite;
    }
    if ($filtreType !== null && $filtreType > 0) {
        $where[] = "ens.type_enseignant = :type_ens";
        $params[':type_ens'] = $filtreType;
    }
    if ($search !== '') {
        $where[] = "(ens.nom_enseignant LIKE :search OR ens.prenom_enseignant LIKE :search2 OR ens.mail_enseignant LIKE :search3)";
        $params[':search'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total
    $countSql = "SELECT COUNT(*) FROM enseignants ens
                 LEFT JOIN avoir a ON ens.id_enseignant = a.id_enseignant
                 {$whereClause}";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) ($countStmt->fetchColumn() ?: 0);

    // Donnees
    $sql = "SELECT ens.*, g.lib_grade, s.lib_specialite, te.libelle AS lib_type_enseignant
            FROM enseignants ens
            LEFT JOIN avoir a ON ens.id_enseignant = a.id_enseignant
            LEFT JOIN grade g ON a.id_grade = g.id_grade
            LEFT JOIN specialite s ON ens.id_specialite = s.id_specialite
            LEFT JOIN type_enseignant te ON ens.type_enseignant = te.id_type_enseignant
            {$whereClause}
            ORDER BY ens.nom_enseignant ASC
            LIMIT :limit OFFSET :offset";

    $params[':limit'] = $perPage;
    $params[':offset'] = $offset;

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    $enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Options des filtres
    $grades = $db->query("SELECT id_grade, lib_grade FROM grade ORDER BY lib_grade")->fetchAll(PDO::FETCH_ASSOC);
    $specialites = $db->query("SELECT id_specialite, lib_specialite FROM specialite ORDER BY lib_specialite")->fetchAll(PDO::FETCH_ASSOC);
    $types = $db->query("SELECT id_type_enseignant, libelle AS lib_type_enseignant FROM type_enseignant ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="annuaire_enseignants_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['N', 'Nom', 'Prenom', 'Grade', 'Specialite', 'Type', 'Email', 'Telephone']);
        foreach ($enseignants as $i => $ens) {
            fputcsv($output, [
                $i + 1,
                $ens['nom_enseignant'] ?? '',
                $ens['prenom_enseignant'] ?? '',
                $ens['lib_grade'] ?? '',
                $ens['lib_specialite'] ?? '',
                $ens['lib_type_enseignant'] ?? '',
                $ens['mail_enseignant'] ?? '',
                $ens['tel_enseignant'] ?? '',
            ]);
        }
        fclose($output);
        exit;
    }

    // Pagination
    $totalPages = max(1, (int) ceil($total / $perPage));
    $pagination = [
        'total' => $total,
        'current' => $page,
        'last' => $totalPages,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'pages' => range(1, $totalPages),
    ];

    $GLOBALS['annuaire_enseignants'] = $enseignants;
    $GLOBALS['annuaire_grades'] = $grades;
    $GLOBALS['annuaire_specialites'] = $specialites;
    $GLOBALS['annuaire_types'] = $types;
    $GLOBALS['annuaire_pagination'] = $pagination;
    $GLOBALS['annuaire_filtre_grade'] = $filtreGrade;
    $GLOBALS['annuaire_filtre_specialite'] = $filtreSpecialite;
    $GLOBALS['annuaire_filtre_type'] = $filtreType;
    $GLOBALS['annuaire_search'] = $search;

} catch (Exception $e) {
    error_log('Erreur annuaireEnseignantsRoutes: ' . $e->getMessage());
    $GLOBALS['annuaire_enseignants'] = [];
    $GLOBALS['annuaire_grades'] = [];
    $GLOBALS['annuaire_specialites'] = [];
    $GLOBALS['annuaire_types'] = [];
    $GLOBALS['annuaire_pagination'] = ['total' => 0, 'current' => 1, 'last' => 1, 'per_page' => 20, 'offset' => 0, 'has_prev' => false, 'has_next' => false, 'pages' => [1]];
    $GLOBALS['annuaire_error'] = 'Erreur lors du chargement des enseignants.';
}
