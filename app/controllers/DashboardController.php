<?php


require_once __DIR__ . '/../../app/utils/StyleManager.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/PersAdmin.php";
require_once __DIR__ . "/../models/AuditLog.php";

/**
 * Contrôleur du tableau de bord
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord :
 * - Statistiques des utilisateurs
 * - Statistiques des étudiants
 * - Statistiques des enseignants
 * - Statistiques du personnel administratif
 * - Activités récentes
 * - Données d'évolution
 */
class DashboardController
{
    /** @var Utilisateur */
    private $utilisateur;

    /** @var Etudiant */
    private $etudiant;

    /** @var Enseignant */
    private $enseignant;

    /** @var PersAdmin */
    private $personnel;

    /** @var AuditLog */
    private $auditLog;

    /** @var string */
    private $baseViewPath;

    /**
     * Constructeur du contrôleur
     * Initialise les modèles et les chemins nécessaires
     */
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->utilisateur = new Utilisateur(Database::getConnection());
        $this->etudiant = new Etudiant(Database::getConnection());
        $this->enseignant = new Enseignant(Database::getConnection());
        $this->personnel = new PersAdmin(Database::getConnection());
        $this->auditLog = new AuditLog(Database::getConnection());
    }

    /**
     * Point d'entrée principal du contrôleur
     * Affiche le tableau de bord avec toutes les statistiques
     */
    public function index()
    {
        // Audit logging pour l'accès au tableau de bord
        $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Accès', 'tableau_de_bord', 'Succès');
        
        // Récupération des statistiques globales
        $this->getGlobalStats();
        
        // Récupération des statistiques détaillées
        $this->getDetailedStats();
        
        // Récupération des activités récentes
        $this->getRecentActivities();
        
        // Récupération des données pour le graphique
        $this->getChartData();
        
       
    }

    /**
     * Récupère les statistiques globales
     */
    private function getGlobalStats()
    {
        // Statistiques des étudiants
        $GLOBALS['total_etudiants'] = count($this->etudiant->getAllEtudiants() ) ;
        $GLOBALS['etudiants_actifs'] = count($this->utilisateur->getEtudiantActif() ) ?? 0;
        $GLOBALS['etudiants_inactifs'] = count($this->utilisateur->getEtudiantInactif() ) ?? 0;

        // Statistiques des enseignants
        $GLOBALS['total_enseignants'] = count( $this->enseignant->getAllEnseignants() ) ?? 0;
        $GLOBALS['enseignants_actifs'] = count($this->utilisateur->getEnseignantActif() ) ?? 0;
        $GLOBALS['enseignants_inactifs'] = count($this->utilisateur->getEnseignantInactif() ) ?? 0;
       

        // Statistiques du personnel administratif
        $GLOBALS['total_pers_admin'] = count( $this->personnel->getAllPersAdmin() ) ?? 0;
        $GLOBALS['pers_admin_actifs'] = count($this->utilisateur->getPersAdminActif() ) ?? 0;
        $GLOBALS['pers_admin_inactifs'] = count($this->utilisateur->getPersAdminInactif() ) ?? 0;
   

        // Statistiques des utilisateurs
        $GLOBALS['total_utilisateurs'] = count($this->utilisateur->getAllUtilisateurs() ) ?? 0;
        $GLOBALS['utilisateurs_actifs'] = count($this->utilisateur->getUtilisateurActif() ) ?? 0;
        $GLOBALS['utilisateurs_inactifs'] = count($this->utilisateur->getUtilisateurInactif() ) ?? 0;
        
    }

    /**
     * Récupère les statistiques détaillées
     */
    private function getDetailedStats()
    {
        // Statistiques détaillées des étudiants
        $GLOBALS['stats_etudiants'] = [
            'total' => $GLOBALS['total_etudiants'] ?? 0,
            'actifs' => $GLOBALS['etudiants_actifs'] ?? 0,
            'inactifs' => $GLOBALS['etudiants_inactifs'] ?? 0,
            'taux_activite' => ($GLOBALS['total_etudiants'] ?? 0) > 0 ? 
                round((($GLOBALS['etudiants_actifs'] ?? 0) / ($GLOBALS['total_etudiants'] ?? 1)) * 100, 1) : 0
        ];

        // Statistiques détaillées des enseignants
        $GLOBALS['stats_enseignants'] = [
            'total' => $GLOBALS['total_enseignants'] ?? 0,
            'actifs' => $GLOBALS['enseignants_actifs'] ?? 0,
            'inactifs' => $GLOBALS['enseignants_inactifs'] ?? 0,
            'taux_activite' => ($GLOBALS['total_enseignants'] ?? 0) > 0 ? 
                round((($GLOBALS['enseignants_actifs'] ?? 0) / ($GLOBALS['total_enseignants'] ?? 1)) * 100, 1) : 0
        ];

        // Statistiques détaillées du personnel
        $GLOBALS['stats_personnel'] = [
            'total' => $GLOBALS['total_pers_admin'] ?? 0,
            'actifs' => $GLOBALS['pers_admin_actifs'] ?? 0,
            'inactifs' => $GLOBALS['pers_admin_inactifs'] ?? 0,
            'taux_activite' => ($GLOBALS['total_pers_admin'] ?? 0) > 0 ? 
                round((($GLOBALS['pers_admin_actifs'] ?? 0) / ($GLOBALS['total_pers_admin'] ?? 1)) * 100, 1) : 0
        ];

        // Statistiques détaillées des utilisateurs
        $GLOBALS['stats_utilisateurs'] = [
            'total' => $GLOBALS['total_utilisateurs'] ?? 0,
            'actifs' => $GLOBALS['utilisateurs_actifs'] ?? 0,
            'inactifs' => $GLOBALS['utilisateurs_inactifs'] ?? 0,
            'taux_activite' => ($GLOBALS['total_utilisateurs'] ?? 0) > 0 ? 
                round((($GLOBALS['utilisateurs_actifs'] ?? 0) / ($GLOBALS['total_utilisateurs'] ?? 1)) * 100, 1) : 0
        ];
    }

    /**
     * Récupère les activités récentes
     */
    private function getRecentActivities()
    {
        // Récupération des dernières activités
        $GLOBALS['activites_recentes'] = [
            [
                'type' => 'utilisateur',
                'description' => 'Nouveaux utilisateurs ajoutés',
                'date' => date('d/m/Y H:i')
            ],
            [
                'type' => 'etudiant',
                'description' => 'Nouveaux étudiants inscrits',
                'date' => date('d/m/Y H:i')
            ]
        ];
    }

    /**
     * Récupère les données pour le graphique d'évolution
     */
    private function getChartData()
    {
        // Données des 6 derniers mois
        $GLOBALS['chart_data'] = [
            'labels' => $this->getLastSixMonths(),
            'etudiants' => [120, 150, 180, 200, 220, 250],
            'enseignants' => [20, 25, 30, 35, 40, 45],
            'personnel' => [15, 18, 20, 22, 25, 28],
            'utilisateurs' => [140, 175, 210, 235, 260, 295]
        ];
    }

    /**
     * Retourne les 6 derniers mois au format court
     */
    private function getLastSixMonths()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = date('M', strtotime("-$i months"));
        }
        return $months;
    }

    /**
     * Récupère les données pour le tableau de bord de la scolarité
     * @return array Les données du tableau de bord
     */
    public function getDashboardData()
    {
        $stats = [
            'etudiants' => 0,
            'nouvelles_inscriptions' => 0,
            'notes_a_valider' => 0,
            'paiements_en_attente' => 0,
            'montant_total_paiements' => 0
        ];

        try {
            // Nombre total d'étudiants
            $stats['etudiants'] = count($this->etudiant->getAllEtudiants());

            // Nouvelles inscriptions (dernière semaine)
            $query = "SELECT COUNT(*) as total FROM inscriptions WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = Database::getConnection()->query($query);
            $stats['nouvelles_inscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Notes à valider
            $query = "SELECT COUNT(*) as total FROM notes WHERE statut = 'en_attente'";
            $stmt = Database::getConnection()->query($query);
            $stats['notes_a_valider'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Paiements en attente
            $query = "SELECT COUNT(*) as total, SUM(montant) as montant_total FROM paiements WHERE statut = 'en_attente'";
            $stmt = Database::getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['paiements_en_attente'] = $result['total'];
            $stats['montant_total_paiements'] = $result['montant_total'] ?? 0;

            // Activités récentes
            $query = "SELECT * FROM activites ORDER BY date_activite DESC LIMIT 3";
            $stmt = Database::getConnection()->query($query);
            $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'stats' => $stats,
                'activites' => $activites
            ];

        } catch(PDOException $e) {
            error_log("Erreur de base de données : " . $e->getMessage());
            return [
                'stats' => $stats,
                'activites' => [],
                'error' => "Une erreur est survenue lors de la récupération des données"
            ];
        }
    }
}