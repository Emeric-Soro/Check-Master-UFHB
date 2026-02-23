<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/PersAdmin.php";
require_once __DIR__ . "/../models/AuditLog.php";

use Utilisateur;
use Etudiant;
use Enseignant;
use PersAdmin;
use AuditLog;
use PDO;
use PDOException;
use Exception;

/**
 * Service métier du tableau de bord principal
 *
 * Contient toute la logique métier et les requêtes de données pour le tableau de bord :
 * - Statistiques globales (étudiants, enseignants, personnel, utilisateurs)
 * - Statistiques détaillées avec taux d'activité
 * - Activités récentes
 * - Données d'évolution pour les graphiques
 * - Données du tableau de bord scolarité
 */
class DashboardService
{
    /** @var PDO */
    private $db;

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

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->utilisateur = new Utilisateur($db);
        $this->etudiant = new Etudiant($db);
        $this->enseignant = new Enseignant($db);
        $this->personnel = new PersAdmin($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Enregistre l'accès au tableau de bord dans l'audit log
     *
     * @param int $userId Identifiant de l'utilisateur
     */
    public function logAccess($userId)
    {
        $this->auditLog->logAction($userId, 'Accès', 'tableau_de_bord', 'Succès');
    }

    /**
     * Récupère les statistiques globales
     *
     * @return array Tableau associatif des statistiques globales
     */
    public function getGlobalStats()
    {
        $stats = [];

        // Statistiques des étudiants
        $stats['total_etudiants'] = count($this->etudiant->getAllEtudiants());
        $stats['etudiants_actifs'] = count($this->utilisateur->getEtudiantActif()) ?? 0;
        $stats['etudiants_inactifs'] = count($this->utilisateur->getEtudiantInactif()) ?? 0;

        // Statistiques des enseignants
        $stats['total_enseignants'] = count($this->enseignant->getAllEnseignants()) ?? 0;
        $stats['enseignants_actifs'] = count($this->utilisateur->getEnseignantActif()) ?? 0;
        $stats['enseignants_inactifs'] = count($this->utilisateur->getEnseignantInactif()) ?? 0;

        // Statistiques du personnel administratif
        $stats['total_pers_admin'] = count($this->personnel->getAllPersAdmin()) ?? 0;
        $stats['pers_admin_actifs'] = count($this->utilisateur->getPersAdminActif()) ?? 0;
        $stats['pers_admin_inactifs'] = count($this->utilisateur->getPersAdminInactif()) ?? 0;

        // Statistiques des utilisateurs
        $stats['total_utilisateurs'] = count($this->utilisateur->getAllUtilisateurs()) ?? 0;
        $stats['utilisateurs_actifs'] = count($this->utilisateur->getUtilisateurActif()) ?? 0;
        $stats['utilisateurs_inactifs'] = count($this->utilisateur->getUtilisateurInactif()) ?? 0;

        return $stats;
    }

    /**
     * Récupère les statistiques détaillées avec taux d'activité
     *
     * @param array $globalStats Les statistiques globales (résultat de getGlobalStats)
     * @return array Tableau associatif des statistiques détaillées
     */
    public function getDetailedStats(array $globalStats)
    {
        $stats = [];

        // Statistiques détaillées des étudiants
        $stats['stats_etudiants'] = [
            'total' => $globalStats['total_etudiants'] ?? 0,
            'actifs' => $globalStats['etudiants_actifs'] ?? 0,
            'inactifs' => $globalStats['etudiants_inactifs'] ?? 0,
            'taux_activite' => ($globalStats['total_etudiants'] ?? 0) > 0 ?
                round((($globalStats['etudiants_actifs'] ?? 0) / ($globalStats['total_etudiants'] ?? 1)) * 100, 1) : 0
        ];

        // Statistiques détaillées des enseignants
        $stats['stats_enseignants'] = [
            'total' => $globalStats['total_enseignants'] ?? 0,
            'actifs' => $globalStats['enseignants_actifs'] ?? 0,
            'inactifs' => $globalStats['enseignants_inactifs'] ?? 0,
            'taux_activite' => ($globalStats['total_enseignants'] ?? 0) > 0 ?
                round((($globalStats['enseignants_actifs'] ?? 0) / ($globalStats['total_enseignants'] ?? 1)) * 100, 1) : 0
        ];

        // Statistiques détaillées du personnel
        $stats['stats_personnel'] = [
            'total' => $globalStats['total_pers_admin'] ?? 0,
            'actifs' => $globalStats['pers_admin_actifs'] ?? 0,
            'inactifs' => $globalStats['pers_admin_inactifs'] ?? 0,
            'taux_activite' => ($globalStats['total_pers_admin'] ?? 0) > 0 ?
                round((($globalStats['pers_admin_actifs'] ?? 0) / ($globalStats['total_pers_admin'] ?? 1)) * 100, 1) : 0
        ];

        // Statistiques détaillées des utilisateurs
        $stats['stats_utilisateurs'] = [
            'total' => $globalStats['total_utilisateurs'] ?? 0,
            'actifs' => $globalStats['utilisateurs_actifs'] ?? 0,
            'inactifs' => $globalStats['utilisateurs_inactifs'] ?? 0,
            'taux_activite' => ($globalStats['total_utilisateurs'] ?? 0) > 0 ?
                round((($globalStats['utilisateurs_actifs'] ?? 0) / ($globalStats['total_utilisateurs'] ?? 1)) * 100, 1) : 0
        ];

        return $stats;
    }

    /**
     * Récupère les activités récentes
     *
     * @return array Liste des activités récentes
     */
    public function getRecentActivities()
    {
        return [
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
     *
     * @return array Données du graphique avec labels et séries
     */
    public function getChartData()
    {
        return [
            'labels' => $this->getLastSixMonths(),
            'etudiants' => [120, 150, 180, 200, 220, 250],
            'enseignants' => [20, 25, 30, 35, 40, 45],
            'personnel' => [15, 18, 20, 22, 25, 28],
            'utilisateurs' => [140, 175, 210, 235, 260, 295]
        ];
    }

    /**
     * Retourne les 6 derniers mois au format court
     *
     * @return array Liste des noms de mois
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
     *
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
            $stmt = $this->db->query($query);
            $stats['nouvelles_inscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Notes à valider
            $query = "SELECT COUNT(*) as total FROM notes WHERE statut = 'en_attente'";
            $stmt = $this->db->query($query);
            $stats['notes_a_valider'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Paiements en attente
            $query = "SELECT COUNT(*) as total, SUM(montant) as montant_total FROM paiements WHERE statut = 'en_attente'";
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['paiements_en_attente'] = $result['total'];
            $stats['montant_total_paiements'] = $result['montant_total'] ?? 0;

            // Activités récentes
            $query = "SELECT * FROM activites ORDER BY date_activite DESC LIMIT 3";
            $stmt = $this->db->query($query);
            $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'stats' => $stats,
                'activites' => $activites
            ];

        } catch (PDOException $e) {
            error_log("Erreur de base de données : " . $e->getMessage());
            return [
                'stats' => $stats,
                'activites' => [],
                'error' => "Une erreur est survenue lors de la récupération des données"
            ];
        }
    }

    /**
     * Récupère toutes les données du tableau de bord principal et les assigne aux $GLOBALS
     *
     * Méthode de convenance qui orchestre l'appel de toutes les statistiques
     * et les injecte dans $GLOBALS pour la compatibilité avec les vues existantes.
     */
    public function populateDashboardGlobals()
    {
        // Récupération des statistiques globales
        $globalStats = $this->getGlobalStats();
        foreach ($globalStats as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        // Récupération des statistiques détaillées
        $detailedStats = $this->getDetailedStats($globalStats);
        foreach ($detailedStats as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        // Récupération des activités récentes
        $GLOBALS['activites_recentes'] = $this->getRecentActivities();

        // Récupération des données pour le graphique
        $GLOBALS['chart_data'] = $this->getChartData();
    }
}