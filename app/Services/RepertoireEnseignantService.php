<?php
namespace CheckMaster\Services;

use Exception;
use PDO;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

class RepertoireEnseignantService
{
    private PDO $pdo;
    private array $tableCache = [];

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
    }

    private function tableExists(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->tableCache)) {
            return $this->tableCache[$tableName];
        }
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->tableCache[$tableName] = false;
            return false;
        }
    }

    private function getProgrammationTable(): string
    {
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
        }
        return 'programmer';
    }

    private function getJuryTable(): string
    {
        if ($this->tableExists('enseignant_jury')) {
            return 'enseignant_jury';
        }
        return 'composer_jury';
    }

    private function getRolesTable(): string
    {
        if ($this->tableExists('qualite_jury')) {
            return 'qualite_jury';
        }
        return 'roles_jury';
    }

    private function getJuryRefColumn(string $juryTable): string
    {
        return $juryTable === 'composer_jury' ? 'num_jury' : 'num_soutenance';
    }

    private function getProgrammationJuryColumn(string $progTable): string
    {
        return $progTable === 'programmer' ? 'num_jury' : 'num_soutenance';
    }

    public function index(): void
    {
        try {
            $teacherId = '';
            $teacherName = trim((string) ($_SESSION['nom_utilisateur'] ?? 'Enseignant'));

            if (!class_exists('Enseignant')) {
                require_once __DIR__ . '/../models/Enseignant.php';
            }
            $enseignantModel = new \Enseignant($this->pdo);
            $enseignant = $enseignantModel->getEnseignantByLogin((string) ($_SESSION['login_utilisateur'] ?? ''));
            if ($enseignant && is_object($enseignant)) {
                $teacherId = (string) ($enseignant->id_enseignant ?? '');
                $fullName = trim((string) ($enseignant->nom_enseignant ?? '') . ' ' . (string) ($enseignant->prenom_enseignant ?? ''));
                if ($fullName !== '') {
                    $teacherName = $fullName;
                }
            }

            $tab = $_GET['tab'] ?? 'rapports';
            $pageNum = max(1, (int) ($_GET['page_num'] ?? 1));
            $perPage = 15;
            $selectedYearId = \AcademicYear::getSelectedIdFromSession();

            $filtreAnnee = isset($_GET['id_annee_acad']) && $_GET['id_annee_acad'] !== ''
                ? (int) $_GET['id_annee_acad']
                : (\AcademicYear::isAllSelectedFromSession() ? null : ($selectedYearId ?? $this->getCurrentAnneeAcademique()));
            $filtreSession = isset($_GET['id_session']) && $_GET['id_session'] !== '' ? (int) $_GET['id_session'] : null;
            $search = isset($_GET['search']) && $_GET['search'] !== '' ? trim($_GET['search']) : null;

            $anneeOptions = $this->getAnneeOptions();
            $sessionOptions = $this->getSessionOptions();

            $data = [];
            $pagination = [];

            $tabCounts = $this->getTabCounts($teacherId, $filtreAnnee, $filtreSession, $search);
            if ($teacherId !== '') {
                switch ($tab) {
                    case 'comptes_rendus':
                        $result = $this->getComptesRendus($teacherId, $filtreAnnee, $filtreSession, $search, $pageNum, $perPage);
                        $data = $result['data'];
                        $pagination = $result['pagination'];
                        break;
                    case 'memoires':
                        $result = $this->getMemoires($teacherId, $filtreAnnee, $filtreSession, $search, $pageNum, $perPage);
                        $data = $result['data'];
                        $pagination = $result['pagination'];
                        break;
                    case 'rapports':
                    default:
                        $result = $this->getRapports($teacherId, $filtreAnnee, $filtreSession, $search, $pageNum, $perPage);
                        $data = $result['data'];
                        $pagination = $result['pagination'];
                        break;
                }
            }

            $GLOBALS['repertoire_data'] = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherName,
                'tab' => $tab,
                'data' => $data,
                'pagination' => $pagination,
                'annee_options' => $anneeOptions,
                'session_options' => $sessionOptions,
                'filtre_annee' => $filtreAnnee,
                'filtre_session' => $filtreSession,
                'search' => $search,
                'tab_counts' => $tabCounts,
            ];

        } catch (Exception $e) {
            error_log('Erreur RepertoireEnseignantService::index: ' . $e->getMessage());
            $GLOBALS['repertoire_data'] = [
                'teacher_id' => '',
                'teacher_name' => $_SESSION['nom_utilisateur'] ?? 'Enseignant',
                'tab' => $_GET['tab'] ?? 'rapports',
                'data' => [],
                'pagination' => [],
                'annee_options' => [],
                'session_options' => [],
                'filtre_annee' => null,
                'filtre_session' => null,
                'search' => null,
                'error' => 'Erreur lors du chargement des données.',
            ];
        }
    }

    public function getRapports(string $idEnseignant, ?int $annee, ?int $session, ?string $search, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $whereConditions = ["a.id_enseignant = :id_enseignant"];
            $params = [':id_enseignant' => $idEnseignant];

            if ($annee !== null) {
                $whereConditions[] = "i.id_annee_acad = :id_annee_acad";
                $params[':id_annee_acad'] = $annee;
            }

            if ($session !== null) {
                $whereConditions[] = "ps.id_session = :id_session";
                $params[':id_session'] = $session;
            }

            if ($search !== null && $search !== '') {
                $whereConditions[] = "(r.theme_rapport LIKE CONCAT('%', :search, '%') OR e.nom_etu LIKE CONCAT('%', :search, '%') OR e.prenom_etu LIKE CONCAT('%', :search, '%'))";
                $params[':search'] = $search;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $countSql = "SELECT COUNT(DISTINCT r.id_rapport) 
                         FROM affecter a
                         JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                         JOIN etudiants e ON e.num_carte_etud = r.num_etu
                         LEFT JOIN LATERAL (
                             SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                             FROM inscriptions i2 
                             WHERE i2.num_carte_etud = e.num_carte_etud 
                             ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                         ) i ON TRUE
                         LEFT JOIN programmer_soutenance ps ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                         WHERE {$whereClause}";

            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT
                        r.id_rapport,
                        r.theme_rapport,
                        r.statut_rapport,
                        r.date_redaction_rapport,
                        r.chemin_fichier,
                        e.num_carte_etud,
                        e.nom_etu,
                        e.prenom_etu,
                        CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS annee_academique,
                        s.lib_session
                    FROM affecter a
                    JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                    JOIN etudiants e ON e.num_carte_etud = r.num_etu
                    LEFT JOIN LATERAL (
                        SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                        FROM inscriptions i2 
                        WHERE i2.num_carte_etud = e.num_carte_etud 
                        ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                    ) i ON TRUE
                    LEFT JOIN annee_academique aa ON aa.id_annee_acad = i.id_annee_acad
                    LEFT JOIN programmer_soutenance ps ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    LEFT JOIN session s ON s.id_session = ps.id_session
                    WHERE {$whereClause}
                    ORDER BY r.date_redaction_rapport DESC
                    LIMIT :limit OFFSET :offset";

            $params[':limit'] = $perPage;
            $params[':offset'] = $offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => $this->buildPagination($total, $page, $perPage),
            ];
        } catch (Exception $e) {
            error_log('Erreur getRapports: ' . $e->getMessage());
            return ['data' => [], 'pagination' => $this->buildPagination(0, 1, $perPage)];
        }
    }

    public function getComptesRendus(string $idEnseignant, ?int $annee, ?int $session, ?string $search, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $whereConditions = ["(a.id_enseignant = :id_enseignant OR rd.id_enseignant = :id_enseignant)"];
            $params = [':id_enseignant' => $idEnseignant];

            if ($annee !== null) {
                $whereConditions[] = "i.id_annee_acad = :id_annee_acad";
                $params[':id_annee_acad'] = $annee;
            }

            if ($session !== null) {
                $whereConditions[] = "ps.id_session = :id_session";
                $params[':id_session'] = $session;
            }

            if ($search !== null && $search !== '') {
                $whereConditions[] = "(cr.nom_CR LIKE CONCAT('%', :search, '%') OR e.nom_etu LIKE CONCAT('%', :search, '%'))";
                $params[':search'] = $search;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $countSql = "SELECT COUNT(DISTINCT cr.id_CR)
                         FROM compte_rendu cr
                         LEFT JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR
                         LEFT JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                         LEFT JOIN etudiants e ON e.num_carte_etud = COALESCE(r.num_etu, cr.num_etu)
                         LEFT JOIN LATERAL (
                             SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                             FROM inscriptions i2 
                             WHERE i2.num_carte_etud = e.num_carte_etud 
                             ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                         ) i ON TRUE
                         LEFT JOIN affecter a ON a.id_rapport = r.id_rapport
                         LEFT JOIN rendre rd ON rd.id_CR = cr.id_CR
                         LEFT JOIN programmer_soutenance ps ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                         WHERE {$whereClause}";

            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT
                        cr.id_CR,
                        cr.nom_CR,
                        cr.date_CR,
                        cr.chemin_fichier_pdf,
                        e.num_carte_etud,
                        e.nom_etu,
                        e.prenom_etu,
                        COALESCE(GROUP_CONCAT(DISTINCT r.id_rapport ORDER BY r.id_rapport SEPARATOR ', '), '—') AS rapports_inclus,
                        CASE
                            WHEN cr.chemin_fichier_pdf IS NOT NULL AND cr.chemin_fichier_pdf <> '' THEN 'Publié'
                            ELSE 'Brouillon'
                        END AS statut_CR
                    FROM compte_rendu cr
                    LEFT JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR
                    LEFT JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                    LEFT JOIN etudiants e ON e.num_carte_etud = COALESCE(r.num_etu, cr.num_etu)
                    LEFT JOIN LATERAL (
                        SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                        FROM inscriptions i2 
                        WHERE i2.num_carte_etud = e.num_carte_etud 
                        ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                    ) i ON TRUE
                    LEFT JOIN affecter a ON a.id_rapport = r.id_rapport
                    LEFT JOIN rendre rd ON rd.id_CR = cr.id_CR
                    LEFT JOIN programmer_soutenance ps ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    WHERE {$whereClause}
                    GROUP BY cr.id_CR, cr.nom_CR, cr.date_CR, cr.chemin_fichier_pdf, e.num_carte_etud, e.nom_etu, e.prenom_etu
                    ORDER BY cr.date_CR DESC
                    LIMIT :limit OFFSET :offset";

            $params[':limit'] = $perPage;
            $params[':offset'] = $offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => $this->buildPagination($total, $page, $perPage),
            ];
        } catch (Exception $e) {
            error_log('Erreur getComptesRendus: ' . $e->getMessage());
            return ['data' => [], 'pagination' => $this->buildPagination(0, 1, $perPage)];
        }
    }

    public function getMemoires(string $idEnseignant, ?int $annee, ?int $session, ?string $search, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $progTable = $this->getProgrammationTable();

            $whereConditions = [];
            $params = [];

            $juryExistsSql = "SELECT 1 FROM {$this->getJuryTable()} ej 
                              WHERE ej.num_soutenance = ps.num_soutenance 
                              AND ej.id_enseignant = :id_enseignant";

            $affecterExistsSql = "SELECT 1 FROM rapport_etudiants r 
                                  JOIN affecter a ON a.id_rapport = r.id_rapport 
                                  WHERE r.num_etu = ps.num_etud 
                                  AND a.id_enseignant = :id_enseignant 
                                  AND a.role IN ('encadrant', 'directeur')";

            $whereConditions[] = "EXISTS ({$juryExistsSql}) OR EXISTS ({$affecterExistsSql})";
            $params[':id_enseignant'] = $idEnseignant;

            if ($annee !== null) {
                $whereConditions[] = "ps.id_annee_acad = :id_annee_acad";
                $params[':id_annee_acad'] = $annee;
            }

            if ($session !== null) {
                $whereConditions[] = "ps.id_session = :id_session";
                $params[':id_session'] = $session;
            }

            if ($search !== null && $search !== '') {
                $whereConditions[] = "(ps.theme_soutenance LIKE CONCAT('%', :search, '%') OR e.nom_etu LIKE CONCAT('%', :search, '%'))";
                $params[':search'] = $search;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $countSql = "SELECT COUNT(DISTINCT ps.num_soutenance)
                         FROM {$progTable} ps
                         JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                         WHERE {$whereClause}";

            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT
                        ps.num_soutenance,
                        ps.theme_soutenance,
                        ps.date_soutenance,
                        ps.heure_soutenance,
                        e.num_carte_etud,
                        e.nom_etu,
                        e.prenom_etu,
                        s.lib_session,
                        CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS annee_academique,
                        SUM(COALESCE(ev.note, 0)) AS note_memoire
                    FROM {$progTable} ps
                    JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                    LEFT JOIN session s ON s.id_session = ps.id_session
                    LEFT JOIN annee_academique aa ON aa.id_annee_acad = ps.id_annee_acad
                    LEFT JOIN evaluer ev ON ev.num_etudiant = ps.num_etud AND ev.num_jury = ps.num_soutenance
                    WHERE {$whereClause}
                    GROUP BY ps.num_soutenance, ps.theme_soutenance, ps.date_soutenance, ps.heure_soutenance,
                             e.num_carte_etud, e.nom_etu, e.prenom_etu, s.lib_session, aa.date_deb, aa.date_fin
                    ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC
                    LIMIT :limit OFFSET :offset";

            $params[':limit'] = $perPage;
            $params[':offset'] = $offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => $this->buildPagination($total, $page, $perPage),
            ];
        } catch (Exception $e) {
            error_log('Erreur getMemoires: ' . $e->getMessage());
            return ['data' => [], 'pagination' => $this->buildPagination(0, 1, $perPage)];
        }
    }

    public function getQualitesJury(string $idEnseignant, ?int $annee, ?int $session): array
    {
        try {
            $juryTable = $this->getJuryTable();
            $rolesTable = $this->getRolesTable();
            $progTable = $this->getProgrammationTable();

            $whereConditions = ["ej.id_enseignant = :id_enseignant"];
            $params = [':id_enseignant' => $idEnseignant];

            if ($annee !== null) {
                $whereConditions[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
                $params[':id_annee_acad'] = $annee;
            }

            if ($session !== null) {
                $whereConditions[] = "ps.id_session = :id_session";
                $params[':id_session'] = $session;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $sql = "SELECT
                        qj.id_role_jury,
                        qj.lib_role,
                        COUNT(DISTINCT ej.num_soutenance) AS total
                    FROM {$juryTable} ej
                    JOIN {$rolesTable} qj ON qj.id_role_jury = ej.id_qualite_jury
                    JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance
                    JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                    WHERE {$whereClause}
                    GROUP BY qj.id_role_jury, qj.lib_role
                    ORDER BY qj.id_role_jury";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getQualitesJury: ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsEnseignantsJury(?int $annee, ?int $session, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $juryTable = $this->getJuryTable();

            $whereConditions = [];
            $params = [];

            if ($annee !== null) {
                $whereConditions[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e2.num_carte_etud OR i.num_carte_etud = e2.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
                $params[':id_annee_acad'] = $annee;
            }

            if ($session !== null) {
                $whereConditions[] = "ps2.id_session = :id_session";
                $params[':id_session'] = $session;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $countSql = "SELECT COUNT(*) FROM (
                             SELECT ens.id_enseignant
                             FROM enseignants ens
                             JOIN {$juryTable} ej ON ej.id_enseignant = ens.id_enseignant
                             LEFT JOIN affecter a ON a.id_enseignant = ens.id_enseignant
                             LEFT JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                             LEFT JOIN programmer_soutenance ps2 ON (ps2.num_etud = r.num_etu)
                             LEFT JOIN etudiants e2 ON (e2.num_carte_etud = ps2.num_etud OR e2.num_ident_etud = ps2.num_etud)
                             {$whereClause}
                             GROUP BY ens.id_enseignant
                             HAVING COUNT(DISTINCT ej.num_soutenance) > 0
                         ) AS sub_count";

            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT
                        ens.id_enseignant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant,
                        COUNT(DISTINCT ej.num_soutenance) AS nb_soutenances_jury,
                        COUNT(DISTINCT CASE WHEN a.role = 'encadrant' THEN ps2.num_soutenance END) AS nb_soutenances_encadrees,
                        COUNT(DISTINCT CASE WHEN a.role = 'directeur' THEN ps2.num_soutenance END) AS nb_soutenances_dirigees
                    FROM enseignants ens
                    JOIN {$juryTable} ej ON ej.id_enseignant = ens.id_enseignant
                    LEFT JOIN affecter a ON a.id_enseignant = ens.id_enseignant
                    LEFT JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                     LEFT JOIN programmer_soutenance ps2 ON (ps2.num_etud = r.num_etu)
                     LEFT JOIN etudiants e2 ON (e2.num_carte_etud = ps2.num_etud OR e2.num_ident_etud = ps2.num_etud)
                    {$whereClause}
                    GROUP BY ens.id_enseignant, ens.nom_enseignant, ens.prenom_enseignant
                    HAVING COUNT(DISTINCT ej.num_soutenance) > 0
                    ORDER BY nb_soutenances_encadrees DESC, ens.nom_enseignant ASC
                    LIMIT :limit OFFSET :offset";

            $params[':limit'] = $perPage;
            $params[':offset'] = $offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => $this->buildPagination($total, $page, $perPage),
            ];
        } catch (Exception $e) {
            error_log('Erreur getStatsEnseignantsJury: ' . $e->getMessage());
            return ['data' => [], 'pagination' => $this->buildPagination(0, 1, $perPage)];
        }
    }

    private function buildPagination(int $total, int $currentPage, int $perPage): array
    {
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $currentPage), $lastPage);

        $pages = [];
        for ($i = 1; $i <= $lastPage; $i++) {
            $pages[] = $i;
        }

        return [
            'total' => $total,
            'current' => $currentPage,
            'per_page' => $perPage,
            'last' => $lastPage,
            'offset' => ($currentPage - 1) * $perPage,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $lastPage,
            'pages' => $pages,
        ];
    }

    private function getAnneeOptions(): array
    {
        try {
            $sql = "SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle 
                    FROM annee_academique 
                    ORDER BY date_deb DESC";
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $options = [];
            foreach ($results as $row) {
                $options[$row['id_annee_acad']] = $row['libelle'];
            }
            return $options;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getSessionOptions(): array
    {
        try {
            $sql = "SELECT id_session, lib_session FROM session ORDER BY id_session";
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $options = [];
            foreach ($results as $row) {
                $options[$row['id_session']] = $row['lib_session'];
            }
            return $options;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getCurrentAnneeAcademique(): ?int
    {
        try {
            $sql = "SELECT id_annee_acad FROM annee_academique 
                    WHERE CURDATE() BETWEEN date_deb AND date_fin 
                    LIMIT 1";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetchColumn();
            return $result ? (int) $result : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get counts for all 3 tabs (rapports, comptes_rendus, memoires).
     */
    private function getTabCounts(string $idEnseignant, ?int $annee, ?int $session, ?string $search): array
    {
        $counts = ['rapports' => 0, 'comptes_rendus' => 0, 'memoires' => 0];
        if ($idEnseignant === '')
            return $counts;

        try {
            // --- Rapports ---
            $w = ["a.id_enseignant = :id_enseignant"];
            $p = [':id_enseignant' => $idEnseignant];
            if ($annee !== null) {
                $w[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
                $p[':id_annee_acad'] = $annee;
            }
            if ($session !== null) {
                $w[] = "ps.id_session = :id_session";
                $p[':id_session'] = $session;
            }
            if ($search !== null && $search !== '') {
                $w[] = "(r.theme_rapport LIKE CONCAT('%', :search, '%') OR e.nom_etu LIKE CONCAT('%', :search, '%') OR e.prenom_etu LIKE CONCAT('%', :search, '%'))";
                $p[':search'] = $search;
            }
            $wc = implode(' AND ', $w);
            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT r.id_rapport) FROM affecter a JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport JOIN etudiants e ON e.num_carte_etud = r.num_etu LEFT JOIN programmer_soutenance ps ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud) WHERE {$wc}");
            $stmt->execute($p);
            $counts['rapports'] = (int) $stmt->fetchColumn();

            // --- Comptes-rendus ---
            $w = ["(a.id_enseignant = :id_enseignant OR rd.id_enseignant = :id_enseignant)"];
            $p = [':id_enseignant' => $idEnseignant];
            if ($annee !== null) {
                $w[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
                $p[':id_annee_acad'] = $annee;
            }
            if ($session !== null) {
                $w[] = "ps.id_session = :id_session";
                $p[':id_session'] = $session;
            }
            if ($search !== null && $search !== '') {
                $w[] = "(cr.nom_CR LIKE CONCAT('%', :search, '%') OR e.nom_etu LIKE CONCAT('%', :search, '%'))";
                $p[':search'] = $search;
            }
            $wc = implode(' AND ', $w);
            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT cr.id_CR) FROM compte_rendu cr LEFT JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR LEFT JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport LEFT JOIN etudiants e ON e.num_carte_etud = COALESCE(r.num_etu, cr.num_etu) LEFT JOIN affecter a ON a.id_rapport = r.id_rapport LEFT JOIN rendre rd ON rd.id_CR = cr.id_CR LEFT JOIN programmer_soutenance ps ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud) WHERE {$wc}");
            $stmt->execute($p);
            $counts['comptes_rendus'] = (int) $stmt->fetchColumn();

            // --- Memoires ---
            $progTable = $this->getProgrammationTable();
            $juryTable = $this->getJuryTable();
            $juryExists = "SELECT 1 FROM {$juryTable} ej WHERE ej.num_soutenance = ps.num_soutenance AND ej.id_enseignant = :id_enseignant";
            $affecterExists = "SELECT 1 FROM rapport_etudiants r JOIN affecter a ON a.id_rapport = r.id_rapport WHERE r.num_etu = ps.num_etud AND a.id_enseignant = :id_enseignant AND a.role IN ('encadrant', 'directeur')";
            $w = ["EXISTS ({$juryExists}) OR EXISTS ({$affecterExists})"];
            $p = [':id_enseignant' => $idEnseignant];
            if ($annee !== null) {
                $w[] = "EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
                $p[':id_annee_acad'] = $annee;
            }
            if ($session !== null) {
                $w[] = "ps.id_session = :id_session";
                $p[':id_session'] = $session;
            }
            if ($search !== null && $search !== '') {
                $w[] = "(ps.theme_soutenance LIKE CONCAT('%', :search, '%') OR e.nom_etu LIKE CONCAT('%', :search, '%'))";
                $p[':search'] = $search;
            }
            $wc = implode(' AND ', $w);
            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT ps.num_soutenance) FROM {$progTable} ps JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud) WHERE {$wc}");
            $stmt->execute($p);
            $counts['memoires'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('RepertoireEnseignantService::getTabCounts error: ' . $e->getMessage());
        }

        return $counts;
    }

}
