<?php

namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

use AuditLog;
use PDO;
use Exception;

/**
 * Service des archives des dossiers de soutenance
 *
 * Contient la logique métier pour :
 * - Récupération des rapports archivés avec filtres
 * - Statistiques des archives
 * - Détails d'un rapport spécifique
 */
class ArchivesDossiersSoutenanceService
{
    private $db;
    private $auditLog;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    public function __construct($db)
    {
        $this->db = $db;
        $this->auditLog = new AuditLog($db);
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$table] = $exists;
            return $exists;
        } catch (\Throwable $e) {
            $this->tableExistsCache[$table] = false;
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }

        if (!$this->tableExists($table)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (\Throwable $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function getRapportDateColumn(): ?string
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return 'date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return 'date_redaction_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_modification')) {
            return 'date_modification';
        }

        return null;
    }

    private function getRapportDateExpr(string $alias = 'r'): string
    {
        $column = $this->getRapportDateColumn();
        if ($column === null) {
            return 'NULL';
        }

        return $alias . '.' . $column;
    }

    private function getRapportDateSelect(string $alias = 'r'): string
    {
        $column = $this->getRapportDateColumn();
        if ($column === null) {
            return 'NULL AS date_rapport';
        }

        return $alias . '.' . $column . ' AS date_rapport';
    }

    private function getEtudiantNumeroExpr(string $alias = 'e'): string
    {
        $column = $this->columnExists('etudiants', 'num_etu') ? 'num_etu' : 'num_carte_etud';
        return $alias . '.' . $column;
    }

    /**
     * Récupère les rapports archivés avec filtres
     * @param array $filtres
     * @return array
     */
    public function getRapportsArchives($filtres = [])
    {
        try {
            $whereConditions = [];
            $params = [];
            $rapportDateExpr = $this->getRapportDateExpr('r');
            $rapportDateSelect = $this->getRapportDateSelect('r');
            $studentNumberExpr = $this->getEtudiantNumeroExpr('e');

            // Filtre par statut
            if (!empty($filtres['statut'])) {
                $whereConditions[] = "v.decision_validation = :statut";
                $params['statut'] = $filtres['statut'];
            }

            // Filtre par année
            if (!empty($filtres['annee'])) {
                $anneeFiltre = trim((string) $filtres['annee']);
                if (strpos($anneeFiltre, '-') !== false) {
                    $whereConditions[] = "e.promotion_etu = :annee";
                    $params['annee'] = $anneeFiltre;
                } else {
                    $whereConditions[] = "YEAR(v.date_validation) = :annee";
                    $params['annee'] = (int) $anneeFiltre;
                }
            }

            // Filtre par étudiant
            if (!empty($filtres['etudiant'])) {
                $whereConditions[] = "(e.nom_etu LIKE :etudiant OR e.prenom_etu LIKE :etudiant OR {$studentNumberExpr} LIKE :etudiant)";
                $params['etudiant'] = '%' . $filtres['etudiant'] . '%';
            }

            // Filtre par date
            if (!empty($filtres['date_debut'])) {
                $whereConditions[] = "v.date_validation >= :date_debut";
                $params['date_debut'] = $filtres['date_debut'];
            }

            if (!empty($filtres['date_fin'])) {
                $whereConditions[] = "v.date_validation <= :date_fin";
                $params['date_fin'] = $filtres['date_fin'];
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            $tempsTraitementExpr = $rapportDateExpr === 'NULL'
                ? 'NULL'
                : "DATEDIFF(v.date_validation, {$rapportDateExpr})";

            $query = "SELECT 
                        v.id_rapport,
                        v.decision_validation,
                        v.date_validation,
                        v.commentaire_validation,
                        r.nom_rapport,
                        r.theme_rapport,
                        {$rapportDateSelect},
                        e.promotion_etu,
                        {$studentNumberExpr} AS num_etu,
                        e.nom_etu,
                        e.prenom_etu,
                        e.email_etu,
                        {$tempsTraitementExpr} as temps_traitement
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                      $whereClause
                      ORDER BY v.date_validation DESC";

            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportsArchives: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques des archives
     * @return array
     */
    public function getStatistiquesArchives()
    {
        try {
            $stats = [];

            // Nombre total d'archives
            $query = "SELECT COUNT(*) as total FROM valider";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_archives'] = $result['total'] ?? 0;

            // Répartition par statut
            $queryStatuts = "SELECT 
                              decision_validation as statut,
                              COUNT(*) as nombre
                            FROM valider 
                            GROUP BY decision_validation";
            $stmtStatuts = $this->db->prepare($queryStatuts);
            $stmtStatuts->execute();
            $stats['repartition_statuts'] = $stmtStatuts->fetchAll(PDO::FETCH_ASSOC);

            // Répartition par année
            $query = "SELECT 
                        YEAR(date_validation) as annee,
                        COUNT(*) as nombre
                      FROM valider 
                      GROUP BY YEAR(date_validation)
                      ORDER BY annee DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['repartition_annees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Temps moyen de traitement
            $rapportDateExpr = $this->getRapportDateExpr('r');
            if ($rapportDateExpr === 'NULL') {
                $stats['temps_moyen_traitement'] = 0.0;
            } else {
                $queryTemps = "SELECT AVG(DATEDIFF(v.date_validation, {$rapportDateExpr})) as temps_moyen
                              FROM valider v
                              LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                              WHERE {$rapportDateExpr} IS NOT NULL";
                $stmtTemps = $this->db->prepare($queryTemps);
                $stmtTemps->execute();
                $resultTemps = $stmtTemps->fetch(PDO::FETCH_ASSOC);
                $stats['temps_moyen_traitement'] = round($resultTemps['temps_moyen'] ?? 0, 1);
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Erreur getStatistiquesArchives: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails d'un rapport spécifique
     * @param int $idRapport
     * @return array|null
     */
    public function getRapportDetails($idRapport)
    {
        try {
            $studentNumberExpr = $this->getEtudiantNumeroExpr('e');
            $query = "SELECT 
                        v.*,
                        r.nom_rapport,
                        r.theme_rapport,
                        " . $this->getRapportDateSelect('r') . ",
                        e.promotion_etu,
                        {$studentNumberExpr} AS num_etu,
                        e.nom_etu,
                        e.prenom_etu,
                        e.email_etu
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                      WHERE v.id_rapport = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $idRapport);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportDetails: " . $e->getMessage());
            return null;
        }
    }
}
