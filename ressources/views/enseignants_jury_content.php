<?php
/**
 * Enseignants Jury - Liste des enseignants ayant participé à des jurys
 * Affiche pour chaque enseignant le nombre de soutenances encadrées, dirigées et jurys
 */

$yearLabel = date('Y') . '-' . (date('Y') + 1);
$filtreAnneeAdmin = isset($_GET['id_annee_acad']) && $_GET['id_annee_acad'] !== '' ? (int) $_GET['id_annee_acad'] : null;
$filtreSessionAdmin = isset($_GET['id_session']) && $_GET['id_session'] !== '' ? (int) $_GET['id_session'] : null;
$pageNumAdmin = max(1, (int) ($_GET['page_num'] ?? 1));
$perPageAdmin = 20;

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

$sortColumn = $_GET['sort'] ?? 'nom_enseignant';
$sortDirection = $_GET['direction'] ?? 'ASC';

$allowedSortColumns = ['nom_enseignant', 'prenom_enseignant', 'nb_soutenances_jury', 'nb_soutenances_encadrees', 'nb_soutenances_dirigees'];
if (!in_array($sortColumn, $allowedSortColumns)) {
    $sortColumn = 'nom_enseignant';
}
$sortDirection = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

try {
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../../app/config/database.php';
    }
    if (!class_exists('AnneeAcademique')) {
        require_once __DIR__ . '/../../app/models/AnneeAcademique.php';
    }
    $anneeModel = new AnneeAcademique(Database::getConnection());
    $anneeActive = $anneeModel->getAnneeAcademiqueActive();
    if ($anneeActive && is_object($anneeActive) && !empty($anneeActive->date_deb) && !empty($anneeActive->date_fin)) {
        $yearLabel = date('Y', strtotime((string) $anneeActive->date_deb)) . '-' . date('Y', strtotime((string) $anneeActive->date_fin));
        if ($filtreAnneeAdmin === null) {
            $filtreAnneeAdmin = (int) ($anneeActive->id_annee_acad ?? 0);
        }
    }
} catch (Throwable $e) {
    error_log('Enseignants Jury year fallback: ' . $e->getMessage());
}

$enseignantsJuryData = [];
$enseignantsJuryPagination = [];
$anneeOptionsAdmin = [];
$sessionOptionsAdmin = [];
$totalStats = [
    'total_jurys' => 0,
    'total_encadrees' => 0,
    'total_dirigees' => 0
];

try {
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../../app/config/database.php';
    }
    $pdo = Database::getConnection();

    $stmtAnnees = $pdo->query("SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle FROM annee_academique ORDER BY date_deb DESC");
    $anneeOptionsAdmin = $stmtAnnees->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $stmtSessions = $pdo->query("SELECT id_session, lib_session FROM session ORDER BY id_session");
    $sessionOptionsAdmin = $stmtSessions->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $juryTable = $pdo->query("SHOW TABLES LIKE 'enseignant_jury'")->fetchColumn() ? 'enseignant_jury' : 'composer_jury';

    $whereConditions = [];
    $params = [];

    if ($filtreAnneeAdmin !== null) {
        $whereConditions[] = "e2.id_annee_acad = :id_annee_acad";
        $params[':id_annee_acad'] = $filtreAnneeAdmin;
    }

    if ($filtreSessionAdmin !== null) {
        $whereConditions[] = "ps2.id_session = :id_session";
        $params[':id_session'] = $filtreSessionAdmin;
    }

    if (!empty($searchTerm)) {
        $whereConditions[] = "(ens.nom_enseignant LIKE :search OR ens.prenom_enseignant LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
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
                     {$whereClause}
                     GROUP BY ens.id_enseignant
                     HAVING COUNT(DISTINCT ej.num_soutenance) > 0
                 ) AS sub_count";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($pageNumAdmin - 1) * $perPageAdmin;

    $orderBy = "ens.nom_enseignant ASC";
    if ($sortColumn === 'nom_enseignant') {
        $orderBy = "ens.nom_enseignant {$sortDirection}, ens.prenom_enseignant {$sortDirection}";
    } elseif ($sortColumn === 'prenom_enseignant') {
        $orderBy = "ens.prenom_enseignant {$sortDirection}, ens.nom_enseignant {$sortDirection}";
    } elseif (in_array($sortColumn, ['nb_soutenances_jury', 'nb_soutenances_encadrees', 'nb_soutenances_dirigees'])) {
        $orderBy = "{$sortColumn} {$sortDirection}, ens.nom_enseignant ASC";
    }

    $sql = "SELECT
                ens.id_enseignant,
                ens.nom_enseignant,
                ens.prenom_enseignant,
                ens.email_enseignant,
                ens.telephone_enseignant,
                COUNT(DISTINCT ej.num_soutenance) AS nb_soutenances_jury,
                COUNT(DISTINCT CASE WHEN a.role = 'encadrant' THEN ps2.num_soutenance END) AS nb_soutenances_encadrees,
                COUNT(DISTINCT CASE WHEN a.role = 'directeur' THEN ps2.num_soutenance END) AS nb_soutenances_dirigees
            FROM enseignants ens
            JOIN {$juryTable} ej ON CAST(ej.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
            LEFT JOIN affecter a ON CAST(a.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
            LEFT JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
            LEFT JOIN programmer_soutenance ps2 ON ps2.num_etud = r.num_etu
            LEFT JOIN etudiants e2 ON e2.num_carte_etud = ps2.num_etud
            {$whereClause}
            GROUP BY ens.id_enseignant, ens.nom_enseignant, ens.prenom_enseignant, ens.email_enseignant, ens.telephone_enseignant
            HAVING COUNT(DISTINCT ej.num_soutenance) > 0
            ORDER BY {$orderBy}
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

    $totalStatsSql = "SELECT
                        SUM(DISTINCT nb_soutenances_jury) as total_jurys,
                        SUM(DISTINCT nb_soutenances_encadrees) as total_encadrees,
                        SUM(DISTINCT nb_soutenances_dirigees) as total_dirigees
                      FROM (
                        SELECT
                            ens.id_enseignant,
                            COUNT(DISTINCT ej.num_soutenance) AS nb_soutenances_jury,
                            COUNT(DISTINCT CASE WHEN a.role = 'encadrant' THEN ps2.num_soutenance END) AS nb_soutenances_encadrees,
                            COUNT(DISTINCT CASE WHEN a.role = 'directeur' THEN ps2.num_soutenance END) AS nb_soutenances_dirigees
                        FROM enseignants ens
                        JOIN {$juryTable} ej ON CAST(ej.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
                        LEFT JOIN affecter a ON CAST(a.id_enseignant AS CHAR) = CAST(ens.id_enseignant AS CHAR)
                        LEFT JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                        LEFT JOIN programmer_soutenance ps2 ON ps2.num_etud = r.num_etu
                        LEFT JOIN etudiants e2 ON e2.num_carte_etud = ps2.num_etud
                        {$whereClause}
                        GROUP BY ens.id_enseignant
                        HAVING COUNT(DISTINCT ej.num_soutenance) > 0
                      ) as stats";

    $statsStmt = $pdo->prepare($totalStatsSql);
    foreach ($params as $key => $value) {
        if ($key !== ':limit' && $key !== ':offset') {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statsStmt->bindValue($key, $value, $type);
        }
    }
    $statsStmt->execute();
    $totalStats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: $totalStats;

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
    error_log('Enseignants Jury data error: ' . $e->getMessage());
}

function getSortUrl($column, $currentColumn, $currentDirection, $baseUrl) {
    $newDirection = ($currentColumn === $column && $currentDirection === 'ASC') ? 'DESC' : 'ASC';
    $separator = strpos($baseUrl, '?') === false ? '?' : '&';
    return $baseUrl . $separator . 'sort=' . $column . '&direction=' . $newDirection;
}

function getSortIcon($column, $currentColumn, $currentDirection) {
    if ($currentColumn !== $column) {
        return '<i class="fas fa-sort cm-text-muted"></i>';
    }
    return $currentDirection === 'ASC' 
        ? '<i class="fas fa-sort-up cm-text-primary"></i>' 
        : '<i class="fas fa-sort-down cm-text-primary"></i>';
}

$baseUrl = '?page=enseignants_jury';
if ($filtreAnneeAdmin !== null) {
    $baseUrl .= '&id_annee_acad=' . $filtreAnneeAdmin;
}
if ($filtreSessionAdmin !== null) {
    $baseUrl .= '&id_session=' . $filtreSessionAdmin;
}
if (!empty($searchTerm)) {
    $baseUrl .= '&search=' . urlencode($searchTerm);
}
?>

<section class="cm-prd3-screen">
    <header class="cm-flex-between cm-mb-md">
        <div>
            <h2 class="cm-m-0 cm-text-xl cm-text-bold cm-text-primary">
                <i class="fas fa-users cm-mr-sm"></i>
                Enseignants - Participation aux Jurys
            </h2>
            <p class="cm-m-0 cm-text-muted">Liste des enseignants ayant participé à des jurys de soutenance.</p>
        </div>
        <span class="cm-toolbar-year">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <?= htmlspecialchars($yearLabel, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </header>

    <div class="cm-grid cm-dashboard-stats-grid-4 cm-mb-md">
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format($enseignantsJuryPagination['total'] ?? 0, 0, ',', ' '), 'label' => 'Enseignants', 'icon' => 'fa-chalkboard-teacher', 'color' => 'primary']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int)($totalStats['total_jurys'] ?? 0), 0, ',', ' '), 'label' => 'Total Jurys', 'icon' => 'fa-gavel', 'color' => 'info']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int)($totalStats['total_encadrees'] ?? 0), 0, ',', ' '), 'label' => 'Total Encadrées', 'icon' => 'fa-user-graduate', 'color' => 'success']); ?>
        </div>
        <div>
            <?php cm_component('dashboard/stat-widget', ['value' => number_format((int)($totalStats['total_dirigees'] ?? 0), 0, ',', ' '), 'label' => 'Total Dirigées', 'icon' => 'fa-user-tie', 'color' => 'warning']); ?>
        </div>
    </div>

    <div class="cm-card">
        <div class="cm-card__header">
            <h3 class="cm-card__title">
                <i class="fas fa-filter cm-mr-sm"></i>
                Filtres et Recherche
            </h3>
        </div>
        <div class="cm-card__body">
            <form method="GET" class="cm-grid-4 cm-mb-0" style="align-items: end;">
                <input type="hidden" name="page" value="enseignants_jury">

                <?= cm_component('form/select', [
                    'name' => 'id_annee_acad',
                    'label' => 'Année académique',
                    'options' => $anneeOptionsAdmin,
                    'selected' => (string)($filtreAnneeAdmin ?? ''),
                    'placeholder' => 'Toutes les années'
                ]) ?>

                <?= cm_component('form/select', [
                    'name' => 'id_session',
                    'label' => 'Période',
                    'options' => $sessionOptionsAdmin,
                    'selected' => (string)($filtreSessionAdmin ?? ''),
                    'placeholder' => 'Toutes les périodes'
                ]) ?>

                <div class="cm-form-group">
                    <label class="cm-form-label">Recherche</label>
                    <div class="cm-input-group">
                        <span class="cm-input-group__icon"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="cm-form-control" 
                               placeholder="Nom ou prénom..." 
                               value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="cm-flex cm-flex-gap-sm">
                    <button type="submit" class="cm-btn cm-btn--primary">
                        <i class="fas fa-filter cm-mr-sm"></i> Filtrer
                    </button>
                    <a href="?page=enseignants_jury" class="cm-btn cm-btn--outline">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="cm-card cm-mt-md">
        <div class="cm-card__header cm-flex-between">
            <h3 class="cm-card__title">
                <i class="fas fa-list cm-mr-sm"></i>
                Liste des Enseignants
            </h3>
            <div class="cm-flex cm-flex-gap-sm">
                <a href="?page=dashboard" class="cm-btn cm-btn--outline" data-cm-ajax-link="true">
                    <i class="fas fa-arrow-left cm-mr-sm"></i> Retour au Dashboard
                </a>
                <button type="button" class="cm-btn cm-btn--success" onclick="exportTableToCSV('enseignants_jury.csv')">
                    <i class="fas fa-download cm-mr-sm"></i> Exporter
                </button>
            </div>
        </div>
        <div class="cm-card__body cm-p-0">
            <div class="cm-table-responsive">
                <table class="cm-table" id="enseignantsTable">
                    <thead>
                        <tr>
                            <th>
                                <a href="<?= getSortUrl('nom_enseignant', $sortColumn, $sortDirection, $baseUrl) ?>" class="cm-text-primary">
                                    Nom <?= getSortIcon('nom_enseignant', $sortColumn, $sortDirection) ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= getSortUrl('prenom_enseignant', $sortColumn, $sortDirection, $baseUrl) ?>" class="cm-text-primary">
                                    Prénom <?= getSortIcon('prenom_enseignant', $sortColumn, $sortDirection) ?>
                                </a>
                            </th>
                            <th>Contact</th>
                            <th class="cm-text-center">
                                <a href="<?= getSortUrl('nb_soutenances_jury', $sortColumn, $sortDirection, $baseUrl) ?>" class="cm-text-primary">
                                    Jurys <?= getSortIcon('nb_soutenances_jury', $sortColumn, $sortDirection) ?>
                                </a>
                            </th>
                            <th class="cm-text-center">
                                <a href="<?= getSortUrl('nb_soutenances_encadrees', $sortColumn, $sortDirection, $baseUrl) ?>" class="cm-text-primary">
                                    Encadrées <?= getSortIcon('nb_soutenances_encadrees', $sortColumn, $sortDirection) ?>
                                </a>
                            </th>
                            <th class="cm-text-center">
                                <a href="<?= getSortUrl('nb_soutenances_dirigees', $sortColumn, $sortDirection, $baseUrl) ?>" class="cm-text-primary">
                                    Dirigées <?= getSortIcon('nb_soutenances_dirigees', $sortColumn, $sortDirection) ?>
                                </a>
                            </th>
                            <th class="cm-text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enseignantsJuryData)): ?>
                            <tr>
                                <td colspan="7">
                                    <?= cm_component('ui/empty-state', [
                                        'title' => 'Aucun enseignant trouvé',
                                        'message' => 'Aucun enseignant n\'a participé à un jury pour les critères sélectionnés.',
                                        'icon' => 'fa-users',
                                        'in_table' => true,
                                        'colspan' => 7
                                    ]) ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enseignantsJuryData as $ens): 
                                $total = (int)($ens['nb_soutenances_encadrees'] ?? 0) + (int)($ens['nb_soutenances_dirigees'] ?? 0);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars(strtoupper($ens['nom_enseignant'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($ens['prenom_enseignant'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if (!empty($ens['email_enseignant'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($ens['email_enseignant'], ENT_QUOTES, 'UTF-8') ?>" class="cm-text-primary">
                                                <i class="fas fa-envelope cm-mr-xs"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($ens['telephone_enseignant'])): ?>
                                            <span class="cm-text-muted cm-text-sm">
                                                <i class="fas fa-phone cm-mr-xs"></i><?= htmlspecialchars($ens['telephone_enseignant'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cm-text-center">
                                        <span class="cm-badge cm-badge--info"><?= (int) ($ens['nb_soutenances_jury'] ?? 0) ?></span>
                                    </td>
                                    <td class="cm-text-center">
                                        <span class="cm-badge cm-badge--success"><?= (int) ($ens['nb_soutenances_encadrees'] ?? 0) ?></span>
                                    </td>
                                    <td class="cm-text-center">
                                        <span class="cm-badge cm-badge--warning"><?= (int) ($ens['nb_soutenances_dirigees'] ?? 0) ?></span>
                                    </td>
                                    <td class="cm-text-center">
                                        <span class="cm-badge cm-badge--primary cm-badge--lg"><?= $total ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($enseignantsJuryPagination) && $enseignantsJuryPagination['total'] > 0): ?>
                <div class="cm-card__footer">
                    <?= cm_component('crud/pagination', [
                        'pagination' => (object) $enseignantsJuryPagination,
                        'base_url' => '?page=enseignants_jury&id_annee_acad=' . urlencode((string)($filtreAnneeAdmin ?? '')) . '&id_session=' . urlencode((string)($filtreSessionAdmin ?? '')) . '&search=' . urlencode($searchTerm) . '&sort=' . $sortColumn . '&direction=' . $sortDirection
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function exportTableToCSV(filename) {
    const csv = [];
    const rows = document.querySelectorAll("#enseignantsTable tr");
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/,/g, ";");
            row.push(data);
        }
        
        csv.push(row.join(","));
    }
    
    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {type: "text/csv"});
    const downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>
