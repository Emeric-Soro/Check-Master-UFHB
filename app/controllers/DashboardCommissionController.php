<?php


require_once __DIR__ . '/../../app/utils/StyleManager.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/RapportEtudiant.php";
require_once __DIR__ . "/../models/EvaluationRapport.php";

/**
 * Contrôleur du tableau de bord de la commission
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord de la commission :
 * - Statistiques des rapports
 * - Taux de validation
 * - Temps moyen de traitement
 * - Rapports en attente
 * - Activités récentes
 * - Graphiques d'évolution
 */
class DashboardCommissionController
{
    /** @var RapportEtudiant */
    private $rapportEtudiant;

    /** @var EvaluationRapport */
    private $evaluationRapport;

    /** @var string */
    private $baseViewPath;

    /**
     * Constructeur du contrôleur
     * Initialise les modèles et les chemins nécessaires
     */
    public function __construct()
    {
        $this->rapportEtudiant = new RapportEtudiant(Database::getConnection());
        $this->evaluationRapport = new EvaluationRapport(Database::getConnection());
    }

    /**
     * Récupère les données pour le tableau de bord de la commission
     * @return array Les données du tableau de bord
     */
    public function getDashboardData()
    {
        $stats = [
            'total_rapports' => 0,
            'taux_validation' => 0,
            'temps_moyen' => 0,
            'en_attente' => 0,
            'evolution_mensuelle' => [],
            'repartition_statuts' => [],
            'performance_categories' => [],
            'activites_recentes' => [],
            'rapports_details' => [],
            'evaluations_rapports' => []
        ];

        try {
            $stats['total_rapports'] = $this->getTotalRapports();
            $stats['taux_validation'] = $this->getTauxValidation();
            $stats['temps_moyen'] = $this->getTempsMoyenTraitement();
            $stats['en_attente'] = $this->getRapportsEnAttente();
            $stats['evolution_mensuelle'] = $this->getEvolutionMensuelle();
            $stats['repartition_statuts'] = $this->getRepartitionStatuts();
            $stats['performance_categories'] = $this->getPerformanceCategories();
            $stats['activites_recentes'] = $this->getActivitesRecentes();
            $stats['rapports_details'] = $this->getRapportsDetails();
            $stmt = Database::getConnection()->query('
                SELECT e.*, 
                       r.nom_rapport, 
                       ens.nom_enseignant, 
                       ens.prenom_enseignant
                FROM evaluations_rapports e
                LEFT JOIN rapport_etudiants r ON e.id_rapport = r.id_rapport
                LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant
                ORDER BY e.date_evaluation DESC
            ');
            $stats['evaluations_rapports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur dans getDashboardData: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Récupère le nombre total de rapports
     * @return int
     */
    private function getTotalRapports()
    {
        try {
            // Compter les rapports qui ont été évalués par la commission
            $query = "SELECT COUNT(DISTINCT id_rapport) as total FROM valider";
            $stmt = Database::getConnection()->prepare($query);
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
     * @return float
     */
    private function getTauxValidation()
    {
        try {
            // Compter les rapports validés vs rejetés dans la table valider
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as valides,
                        COUNT(DISTINCT v.id_rapport) as total
                      FROM valider v";
            $stmt = Database::getConnection()->prepare($query);
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
     * @return float
     */
    private function getTempsMoyenTraitement()
    {
        try {
            $query = "SELECT AVG(DATEDIFF(v.date_validation, r.date_rapport)) as temps_moyen
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      WHERE v.date_validation IS NOT NULL 
                      AND r.date_rapport IS NOT NULL";
            $stmt = Database::getConnection()->prepare($query);
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
     * @return int
     */
    private function getRapportsEnAttente()
    {
        try {
            // Compter les rapports qui ont été approuvés mais pas encore validés/rejetés
            $query = "SELECT COUNT(DISTINCT a.id_rapport) as en_attente
                      FROM approuver a
                      LEFT JOIN valider v ON a.id_rapport = v.id_rapport
                      WHERE v.id_rapport IS NULL";
            $stmt = Database::getConnection()->prepare($query);
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
     * @return array
     */
    private function getEvolutionMensuelle()
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
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getEvolutionMensuelle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la répartition par statut
     * @return array
     */
    private function getRepartitionStatuts()
    {
        try {
            // Récupérer la répartition des décisions de validation/rejet
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
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRepartitionStatuts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la performance par catégorie
     * @return array
     */
    private function getPerformanceCategories()
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
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getPerformanceCategories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les activités récentes
     * @return array
     */
    private function getActivitesRecentes()
    {
        try {
            $query = "SELECT 
                        v.id_rapport,
                        r.nom_rapport as titre,
                        v.decision_validation as statut,
                        v.date_validation as date_soumission,
                        v.date_validation,
                        e.nom_etu as nom_etudiant,
                        e.prenom_etu as prenom_etudiant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                      LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                      ORDER BY v.date_validation DESC
                      LIMIT 10";
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getActivitesRecentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails des rapports pour le tableau
     * @return array
     */
    private function getRapportsDetails()
    {
        try {
            $query = "SELECT 
                        v.id_rapport,
                        r.nom_rapport as titre,
                        v.decision_validation as statut,
                        v.date_validation as date_soumission,
                        v.date_validation,
                        e.nom_etu as nom_etudiant,
                        e.prenom_etu as prenom_etudiant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant,
                        DATEDIFF(v.date_validation, r.date_rapport) as temps_traitement
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                      LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                      ORDER BY v.date_validation DESC
                      LIMIT 20";
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportsDetails: " . $e->getMessage());
            return [];
        }
    }

  

    /**
     * Affiche le tableau de bord
     */
    public function index()
    {
        try {
            $dashboardData = $this->getDashboardData();
            
            // Passer les données à la vue
            global $stats;
            $stats = $dashboardData;
            
        } catch (Exception $e) {
            error_log("Erreur dans index: " . $e->getMessage());
            // En cas d'erreur, utiliser des données par défaut
            global $stats;
            $stats = [
                'total_rapports' => 0,
                'taux_validation' => 0,
                'temps_moyen' => 0,
                'en_attente' => 0,
                'evolution_mensuelle' => [],
                'repartition_statuts' => [],
                'performance_categories' => [],
                'activites_recentes' => [],
                'rapports_details' => [],
                'evaluations_rapports' => []
            ];
        }
    }
} 