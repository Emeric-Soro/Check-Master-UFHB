<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/RapportEtudiant.php";
require_once __DIR__ . "/../models/EvaluationRapport.php";

use RapportEtudiant;
use EvaluationRapport;
use PDO;
use Exception;

/**
 * Service métier du tableau de bord de la commission
 *
 * Contient toute la logique métier et les requêtes de données pour le tableau de bord :
 * - Statistiques des rapports
 * - Taux de validation
 * - Temps moyen de traitement
 * - Rapports en attente
 * - Activités récentes
 * - Graphiques d'évolution
 */
class DashboardCommissionService
{
    /** @var PDO */
    private $db;

    /** @var RapportEtudiant */
    private $rapportEtudiant;

    /** @var EvaluationRapport */
    private $evaluationRapport;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->rapportEtudiant = new RapportEtudiant($db);
        $this->evaluationRapport = new EvaluationRapport($db);
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists($tableName, $columnName)
    {
        $key = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }
        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function rapportDateExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return $alias . '.date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return $alias . '.date_redaction_rapport';
        }
        return 'NULL';
    }

    private function rapportTitleExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'nom_rapport')) {
            return $alias . '.nom_rapport';
        }
        return $alias . '.theme_rapport';
    }

    /**
     * Récupère l'année académique active (celle dont la date actuelle est comprise entre date_deb et date_fin)
     *
     * @return array|null
     */
    public function getAnneeAcademiqueActive()
    {
        try {
            if (!$this->tableExists('annee_academique')) {
                return null;
            }
            $query = "SELECT id_annee_acad, date_deb, date_fin 
                      FROM annee_academique 
                      WHERE CURDATE() BETWEEN date_deb AND date_fin 
                      LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // Si aucune année trouvée avec la date actuelle, retourner la plus récente
                $query = "SELECT id_annee_acad, date_deb, date_fin 
                          FROM annee_academique 
                          ORDER BY date_deb DESC 
                          LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erreur getAnneeAcademiqueActive: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère le nombre de rapports rejetés
     *
     * @return int
     */
    public function getNombreRapportsRejetes()
    {
        try {
            $query = "SELECT COUNT(DISTINCT id_rapport) as rejetes
                      FROM valider
                      WHERE decision_validation = 'rejeter'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['rejetes'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getNombreRapportsRejetes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère toutes les données pour le tableau de bord de la commission
     *
     * @return array Les données du tableau de bord
     */
    public function getDashboardData()
    {
        $stats = [
            'annee_academique' => null,
            'total_rapports' => 0,
            'taux_validation' => 0,
            'temps_moyen' => 0,
            'en_attente' => 0,
            'rapports_rejetes' => 0,
            'evolution_mensuelle' => [],
            'repartition_statuts' => [],
            'performance_categories' => [],
            'activites_recentes' => [],
            'rapports_details' => [],
            'evaluations_rapports' => []
        ];

        try {
            $stats['annee_academique'] = $this->getAnneeAcademiqueActive();
            $stats['total_rapports'] = $this->getTotalRapports();
            $stats['taux_validation'] = $this->getTauxValidation();
            $stats['temps_moyen'] = $this->getTempsMoyenTraitement();
            $stats['en_attente'] = $this->getRapportsEnAttente();
            $stats['rapports_rejetes'] = $this->getNombreRapportsRejetes();
            $stats['evolution_mensuelle'] = $this->getEvolutionMensuelle();
            $stats['repartition_statuts'] = $this->getRepartitionStatuts();
            $stats['performance_categories'] = $this->getPerformanceCategories();
            $stats['activites_recentes'] = $this->getActivitesRecentes();
            $stats['rapports_details'] = $this->getRapportsDetails();
            $stats['evaluations_rapports'] = $this->getEvaluationsRapports();
        } catch (Exception $e) {
            error_log("Erreur dans getDashboardData: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Récupère le nombre total de rapports
     *
     * @return int
     */
    public function getTotalRapports()
    {
        try {
            $query = "SELECT COUNT(DISTINCT id_rapport) as total FROM valider";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getTotalRapports: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le taux de validation des rapports
     *
     * @return float
     */
    public function getTauxValidation()
    {
        try {
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as valides,
                        COUNT(DISTINCT v.id_rapport) as total
                      FROM valider v";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                return round(($result['valides'] / $result['total']) * 100, 1);
            }
            return 0;
        } catch (Exception $e) {
            error_log("Erreur getTauxValidation: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le temps moyen de traitement
     *
     * @return float
     */
    public function getTempsMoyenTraitement()
    {
        try {
            $dateExpr = $this->rapportDateExpr('r');
            if ($dateExpr === 'NULL') {
                return 0;
            }
            $query = "SELECT AVG(DATEDIFF(v.date_validation, $dateExpr)) as temps_moyen
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      WHERE v.date_validation IS NOT NULL 
                      AND $dateExpr IS NOT NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['temps_moyen'] ?? 0, 1);
        } catch (Exception $e) {
            error_log("Erreur getTempsMoyenTraitement: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le nombre de rapports en attente
     *
     * @return int
     */
    public function getRapportsEnAttente()
    {
        try {
            if ($this->tableExists('approuver')) {
                $query = "SELECT COUNT(DISTINCT a.id_rapport) as en_attente
                          FROM approuver a
                          LEFT JOIN valider v ON a.id_rapport = v.id_rapport
                          WHERE v.id_rapport IS NULL";
            } else {
                $query = "SELECT COUNT(*) as en_attente
                          FROM rapport_etudiants r
                          LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                          WHERE v.id_rapport IS NULL";
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['en_attente'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getRapportsEnAttente: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère l'évolution mensuelle des rapports
     *
     * @return array
     */
    public function getEvolutionMensuelle()
    {
        try {
            $query = "SELECT 
                        DATE_FORMAT(v.date_validation, '%Y-%m') as mois,
                        COUNT(DISTINCT v.id_rapport) as total,
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as finalises,
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'rejeter' THEN v.id_rapport END) as rejetes
                      FROM valider v
                      WHERE v.date_validation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(v.date_validation, '%Y-%m')
                      ORDER BY mois";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getEvolutionMensuelle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la répartition par statut
     *
     * @return array
     */
    public function getRepartitionStatuts()
    {
        try {
            if ($this->tableExists('approuver')) {
                $query = "SELECT 
                            v.decision_validation as statut,
                            COUNT(DISTINCT v.id_rapport) as nombre
                          FROM valider v
                          GROUP BY v.decision_validation
                          
                          UNION ALL
                          
                          SELECT 
                            'en_attente' as statut,
                            COUNT(DISTINCT a.id_rapport) as nombre
                          FROM approuver a
                          LEFT JOIN valider v ON a.id_rapport = v.id_rapport
                          WHERE v.id_rapport IS NULL";
            } else {
                $query = "SELECT 
                            v.decision_validation as statut,
                            COUNT(DISTINCT v.id_rapport) as nombre
                          FROM valider v
                          GROUP BY v.decision_validation
                          
                          UNION ALL
                          
                          SELECT 
                            'en_attente' as statut,
                            COUNT(*) as nombre
                          FROM rapport_etudiants r
                          LEFT JOIN valider v2 ON r.id_rapport = v2.id_rapport
                          WHERE v2.id_rapport IS NULL";
            }
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRepartitionStatuts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la performance par catégorie
     *
     * @return array
     */
    public function getPerformanceCategories()
    {
        try {
            $query = "SELECT 
                        r.theme_rapport as categorie,
                        COUNT(DISTINCT v.id_rapport) as total,
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as valides,
                        ROUND((COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) / COUNT(DISTINCT v.id_rapport)) * 100, 1) as taux
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      GROUP BY r.theme_rapport
                      ORDER BY taux DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getPerformanceCategories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les activités récentes
     *
     * @return array
     */
    public function getActivitesRecentes()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $dateExpr = $this->rapportDateExpr('r');
            $query = "SELECT 
                        v.id_rapport,
                        $titleExpr as titre,
                        v.decision_validation as statut,
                        COALESCE($dateExpr, v.date_validation) as date_soumission,
                        v.date_validation,
                        e.nom_etu as nom_etudiant,
                        e.prenom_etu as prenom_etudiant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_carte_etud
                      LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                      ORDER BY v.date_validation DESC
                      LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getActivitesRecentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails des rapports pour le tableau
     *
     * @return array
     */
    public function getRapportsDetails()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $dateExpr = $this->rapportDateExpr('r');
            $query = "SELECT 
                        v.id_rapport,
                        $titleExpr as titre,
                        v.decision_validation as statut,
                        COALESCE($dateExpr, v.date_validation) as date_soumission,
                        v.date_validation,
                        e.nom_etu as nom_etudiant,
                        e.prenom_etu as prenom_etudiant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant,
                        " . ($dateExpr === 'NULL' ? 'NULL' : "DATEDIFF(v.date_validation, $dateExpr)") . " as temps_traitement
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_carte_etud
                      LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                      ORDER BY v.date_validation DESC
                      LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportsDetails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les évaluations de rapports avec détails enseignant
     *
     * @return array
     */
    public function getEvaluationsRapports()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $stmt = $this->db->query('
                SELECT e.*, 
                       ' . $titleExpr . ' as nom_rapport, 
                       ens.nom_enseignant, 
                       ens.prenom_enseignant
                FROM evaluations_rapports e
                LEFT JOIN rapport_etudiants r ON e.id_rapport = r.id_rapport
                LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant
                ORDER BY e.date_evaluation DESC
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getEvaluationsRapports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retourne les données par défaut du tableau de bord (en cas d'erreur)
     *
     * @return array
     */
    public function getDefaultDashboardData()
    {
        return [
            'annee_academique' => null,
            'total_rapports' => 0,
            'taux_validation' => 0,
            'temps_moyen' => 0,
            'en_attente' => 0,
            'rapports_rejetes' => 0,
            'evolution_mensuelle' => [],
            'repartition_statuts' => [],
            'performance_categories' => [],
            'activites_recentes' => [],
            'rapports_details' => [],
            'evaluations_rapports' => []
        ];
    }
}
