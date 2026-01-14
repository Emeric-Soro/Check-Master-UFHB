<?php

namespace App\Controllers;

use PDO;
use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\PersAdmin;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;

/**
 * DashboardController - Tableau de bord administrateur
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord :
 * - Statistiques des utilisateurs
 * - Statistiques des étudiants
 * - Statistiques des enseignants
 * - Statistiques du personnel administratif
 * - Activités récentes
 * - Données d'évolution
 * 
 * @package App\Controllers
 */
class DashboardController
{
    private PDO $pdo;
    private Utilisateur $utilisateur;
    private Etudiant $etudiant;
    private Enseignant $enseignant;
    private PersAdmin $personnel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;
    private string $baseViewPath;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Utilisateur $utilisateur,
        Etudiant $etudiant,
        Enseignant $enseignant,
        PersAdmin $personnel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->utilisateur = $utilisateur;
        $this->etudiant = $etudiant;
        $this->enseignant = $enseignant;
        $this->personnel = $personnel;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'dashboard', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur dashboard"
            );
            
            $GLOBALS['error'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
            http_response_code(403);
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Point d'entrée principal du contrôleur
     * Affiche le tableau de bord avec toutes les statistiques
     */
    public function index(): void
    {
        // Vérification des permissions de lecture
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Audit logging pour l'accès au tableau de bord
            $this->auditLog->logAction(
                $_SESSION['id_utilisateur'] ?? null,
                'Accès',
                'tableau_de_bord',
                'Succès'
            );
            
            // Récupération des statistiques globales
            $this->getGlobalStats();
            
            // Récupération des statistiques détaillées
            $this->getDetailedStats();
            
            // Récupération des activités récentes
            $this->getRecentActivities();
            
            // Récupération des données pour le graphique
            $this->getChartData();
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur dans DashboardController::index(): " . $e->getMessage());
            $GLOBALS['error'] = "Une erreur est survenue lors du chargement du tableau de bord.";
        }
    }

    /**
     * Récupère les statistiques globales
     */
    private function getGlobalStats(): void
    {
        try {
            // Statistiques des étudiants
            $allEtudiants = $this->etudiant->getAllEtudiants();
            $GLOBALS['total_etudiants'] = count($allEtudiants);
            $GLOBALS['etudiants_actifs'] = count($this->utilisateur->getEtudiantActif());
            $GLOBALS['etudiants_inactifs'] = count($this->utilisateur->getEtudiantInactif());

            // Statistiques des enseignants
            $GLOBALS['total_enseignants'] = count($this->enseignant->getAllEnseignants());
            $GLOBALS['enseignants_actifs'] = count($this->utilisateur->getEnseignantActif());
            $GLOBALS['enseignants_inactifs'] = count($this->utilisateur->getEnseignantInactif());

            // Statistiques du personnel administratif
            $GLOBALS['total_pers_admin'] = count($this->personnel->getAllPersAdmin());
            $GLOBALS['pers_admin_actifs'] = count($this->utilisateur->getPersAdminActif());
            $GLOBALS['pers_admin_inactifs'] = count($this->utilisateur->getPersAdminInactif());

            // Statistiques des utilisateurs
            $GLOBALS['total_utilisateurs'] = count($this->utilisateur->getAllUtilisateurs());
            $GLOBALS['utilisateurs_actifs'] = count($this->utilisateur->getUtilisateurActif());
            $GLOBALS['utilisateurs_inactifs'] = count($this->utilisateur->getUtilisateurInactif());
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur getGlobalStats: " . $e->getMessage());
            // Valeurs par défaut en cas d'erreur
            $GLOBALS['total_etudiants'] = 0;
            $GLOBALS['total_enseignants'] = 0;
            $GLOBALS['total_pers_admin'] = 0;
            $GLOBALS['total_utilisateurs'] = 0;
        }
    }

    /**
     * Récupère les statistiques détaillées avec taux d'activité
     */
    private function getDetailedStats(): void
    {
        // Statistiques détaillées des étudiants
        $totalEtudiants = $GLOBALS['total_etudiants'] ?? 0;
        $etudiantsActifs = $GLOBALS['etudiants_actifs'] ?? 0;
        
        $GLOBALS['stats_etudiants'] = [
            'total' => $totalEtudiants,
            'actifs' => $etudiantsActifs,
            'inactifs' => $GLOBALS['etudiants_inactifs'] ?? 0,
            'taux_activite' => $totalEtudiants > 0 ? 
                round(($etudiantsActifs / $totalEtudiants) * 100, 1) : 0
        ];

        // Statistiques détaillées des enseignants
        $totalEnseignants = $GLOBALS['total_enseignants'] ?? 0;
        $enseignantsActifs = $GLOBALS['enseignants_actifs'] ?? 0;
        
        $GLOBALS['stats_enseignants'] = [
            'total' => $totalEnseignants,
            'actifs' => $enseignantsActifs,
            'inactifs' => $GLOBALS['enseignants_inactifs'] ?? 0,
            'taux_activite' => $totalEnseignants > 0 ? 
                round(($enseignantsActifs / $totalEnseignants) * 100, 1) : 0
        ];

        // Statistiques détaillées du personnel
        $totalPersonnel = $GLOBALS['total_pers_admin'] ?? 0;
        $personnelActifs = $GLOBALS['pers_admin_actifs'] ?? 0;
        
        $GLOBALS['stats_personnel'] = [
            'total' => $totalPersonnel,
            'actifs' => $personnelActifs,
            'inactifs' => $GLOBALS['pers_admin_inactifs'] ?? 0,
            'taux_activite' => $totalPersonnel > 0 ? 
                round(($personnelActifs / $totalPersonnel) * 100, 1) : 0
        ];

        // Statistiques détaillées des utilisateurs
        $totalUtilisateurs = $GLOBALS['total_utilisateurs'] ?? 0;
        $utilisateursActifs = $GLOBALS['utilisateurs_actifs'] ?? 0;
        
        $GLOBALS['stats_utilisateurs'] = [
            'total' => $totalUtilisateurs,
            'actifs' => $utilisateursActifs,
            'inactifs' => $GLOBALS['utilisateurs_inactifs'] ?? 0,
            'taux_activite' => $totalUtilisateurs > 0 ? 
                round(($utilisateursActifs / $totalUtilisateurs) * 100, 1) : 0
        ];
    }

    /**
     * Récupère les activités récentes depuis la base de données
     */
    private function getRecentActivities(): void
    {
        try {
            // Récupérer les dernières activités depuis les logs d'audit
            $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur 
                    FROM pister p 
                    LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur 
                    ORDER BY p.date_creation DESC 
                    LIMIT 10";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les activités pour l'affichage
            $GLOBALS['activites_recentes'] = array_map(function($activite) {
                return [
                    'type' => strtolower($activite['nom_table'] ?? 'system'),
                    'action' => $activite['action'] ?? '',
                    'description' => $this->formatActivityDescription($activite),
                    'utilisateur' => $activite['nom_utilisateur'] ?? $activite['login_utilisateur'] ?? 'Système',
                    'date' => isset($activite['date_creation']) ? 
                        date('d/m/Y H:i', strtotime($activite['date_creation'])) : 
                        date('d/m/Y H:i'),
                    'statut' => $activite['statut_action'] ?? 'Succès'
                ];
            }, $activites);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur getRecentActivities: " . $e->getMessage());
            $GLOBALS['activites_recentes'] = [];
        }
    }

    /**
     * Formate la description d'une activité pour l'affichage
     */
    private function formatActivityDescription(array $activite): string
    {
        $action = $activite['action'] ?? 'Action';
        $table = $activite['nom_table'] ?? 'élément';
        
        $tableLabels = [
            'utilisateur' => 'utilisateur',
            'etudiants' => 'étudiant',
            'enseignants' => 'enseignant',
            'pers_admin' => 'personnel',
            'tableau_de_bord' => 'tableau de bord',
            'notes' => 'note',
            'inscriptions' => 'inscription'
        ];
        
        $tableLabel = $tableLabels[$table] ?? $table;
        
        return "{$action} sur {$tableLabel}";
    }

    /**
     * Récupère les données pour le graphique d'évolution
     */
    private function getChartData(): void
    {
        try {
            $labels = $this->getLastSixMonths();
            
            // Récupérer les vraies données d'évolution si disponibles
            $chartData = [
                'labels' => $labels,
                'etudiants' => $this->getEvolutionData('etudiants'),
                'enseignants' => $this->getEvolutionData('enseignants'),
                'personnel' => $this->getEvolutionData('pers_admin'),
                'utilisateurs' => $this->getEvolutionData('utilisateur')
            ];
            
            $GLOBALS['chart_data'] = $chartData;
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur getChartData: " . $e->getMessage());
            // Données par défaut
            $GLOBALS['chart_data'] = [
                'labels' => $this->getLastSixMonths(),
                'etudiants' => [0, 0, 0, 0, 0, 0],
                'enseignants' => [0, 0, 0, 0, 0, 0],
                'personnel' => [0, 0, 0, 0, 0, 0],
                'utilisateurs' => [0, 0, 0, 0, 0, 0]
            ];
        }
    }

    /**
     * Récupère les données d'évolution pour une table donnée
     * 
     * @param string $table Nom de la table
     * @return array Données sur 6 mois
     */
    private function getEvolutionData(string $table): array
    {
        $data = [];
        $dateColumn = 'date_creation';
        
        // Adapter le nom de colonne selon la table
        $dateColumns = [
            'etudiants' => 'date_creation',
            'enseignants' => 'date_creation',
            'pers_admin' => 'date_creation',
            'utilisateur' => 'date_creation'
        ];
        
        $dateColumn = $dateColumns[$table] ?? 'date_creation';
        
        for ($i = 5; $i >= 0; $i--) {
            $startDate = date('Y-m-01', strtotime("-$i months"));
            $endDate = date('Y-m-t', strtotime("-$i months"));
            
            try {
                $sql = "SELECT COUNT(*) as total FROM {$table} 
                        WHERE {$dateColumn} BETWEEN :start AND :end";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':start' => $startDate, ':end' => $endDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $data[] = (int) ($result['total'] ?? 0);
            } catch (\Exception $e) {
                // Si la colonne n'existe pas ou autre erreur, mettre 0
                $data[] = 0;
            }
        }
        
        return $data;
    }

    /**
     * Retourne les 6 derniers mois au format court
     */
    private function getLastSixMonths(): array
    {
        $months = [];
        $monthNames = [
            'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
            'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $monthIndex = (int) date('n', strtotime("-$i months")) - 1;
            $months[] = $monthNames[$monthIndex];
        }
        
        return $months;
    }

    /**
     * Récupère les données pour le tableau de bord de la scolarité
     * 
     * @return array Les données du tableau de bord
     */
    public function getDashboardData(): array
    {
        // Vérification des permissions
        if (!$this->checkPermission('read')) {
            return ['stats' => [], 'activites' => [], 'error' => 'Accès refusé'];
        }

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
            $sql = "SELECT COUNT(*) as total FROM inscriptions 
                    WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['nouvelles_inscriptions'] = (int) ($result['total'] ?? 0);

            // Notes à valider
            $sql = "SELECT COUNT(*) as total FROM notes WHERE statut = 'en_attente'";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['notes_a_valider'] = (int) ($result['total'] ?? 0);

            // Paiements en attente
            $sql = "SELECT COUNT(*) as total, COALESCE(SUM(montant), 0) as montant_total 
                    FROM paiements WHERE statut = 'en_attente'";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['paiements_en_attente'] = (int) ($result['total'] ?? 0);
            $stats['montant_total_paiements'] = (float) ($result['montant_total'] ?? 0);

            // Activités récentes
            $sql = "SELECT * FROM pister ORDER BY date_creation DESC LIMIT 5";
            $stmt = $this->pdo->query($sql);
            $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'stats' => $stats,
                'activites' => $activites
            ];

        } catch (\PDOException $e) {
            $this->logger->error("Erreur getDashboardData: " . $e->getMessage());
            return [
                'stats' => $stats,
                'activites' => [],
                'error' => "Une erreur est survenue lors de la récupération des données"
            ];
        }
    }
}