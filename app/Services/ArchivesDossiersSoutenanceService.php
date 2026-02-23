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

    public function __construct($db)
    {
        $this->db = $db;
        $this->auditLog = new AuditLog($db);
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

            // Filtre par statut
            if (!empty($filtres['statut'])) {
                $whereConditions[] = "v.decision_validation = :statut";
                $params['statut'] = $filtres['statut'];
            }

            // Filtre par année
            if (!empty($filtres['annee'])) {
                $whereConditions[] = "YEAR(v.date_validation) = :annee";
                $params['annee'] = (int) $filtres['annee'];
            }

            // Filtre par étudiant
            if (!empty($filtres['etudiant'])) {
                $whereConditions[] = "(e.nom_etu LIKE :etudiant OR e.prenom_etu LIKE :etudiant OR e.num_etu LIKE :etudiant)";
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

            $query = "SELECT 
                        v.id_rapport,
                        v.decision_validation,
                        v.date_validation,
                        v.commentaire_validation,
                        r.nom_rapport,
                        r.theme_rapport,
                        r.date_rapport,
                        e.promotion_etu,
                        e.num_etu,
                        e.nom_etu,
                        e.prenom_etu,
                        e.email_etu,
                        DATEDIFF(v.date_validation, r.date_rapport) as temps_traitement
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_carte_etud
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
            $queryTemps = "SELECT AVG(DATEDIFF(v.date_validation, r.date_rapport)) as temps_moyen
                          FROM valider v
                          LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                          WHERE r.date_rapport IS NOT NULL";
            $stmtTemps = $this->db->prepare($queryTemps);
            $stmtTemps->execute();
            $resultTemps = $stmtTemps->fetch(PDO::FETCH_ASSOC);
            $stats['temps_moyen_traitement'] = round($resultTemps['temps_moyen'] ?? 0, 1);

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
            $query = "SELECT 
                        v.*,
                        r.nom_rapport,
                        r.theme_rapport,
                        r.date_rapport,
                        e.promotion_etu,
                        e.nom_etu,
                        e.prenom_etu,
                        e.email_etu
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_carte_etud
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