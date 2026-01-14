<?php

namespace App\Controllers;

use PDO;
use App\Models\RapportEtudiant;
use App\Models\EvaluationRapport;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * DashboardCommissionController - Vue spécifique Commission Validation
 * 
 * Ce contrôleur gère l'affichage du tableau de bord de la commission :
 * - Statistiques des rapports
 * - Taux de validation
 * - Temps moyen de traitement
 * - Rapports en attente
 * - Activités récentes
 * 
 * @package App\Controllers
 */
class DashboardCommissionController
{
    private PDO $pdo;
    private RapportEtudiant $rapportEtudiant;
    private EvaluationRapport $evaluationRapport;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        RapportEtudiant $rapportEtudiant,
        EvaluationRapport $evaluationRapport,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->rapportEtudiant = $rapportEtudiant;
        $this->evaluationRapport = $evaluationRapport;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'dashboard_commission', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur dashboard_commission"
            );
            
            $GLOBALS['error'] = "Vous n'avez pas les droits nécessaires.";
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            return false;
        }
        return true;
    }

    /**
     * Action : Affiche le tableau de bord (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $dashboardData = $this->getDashboardData();
            
            global $stats;
            $stats = $dashboardData;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans index: " . $e->getMessage());
            global $stats;
            $stats = $this->getDefaultStats();
        }
    }

    /**
     * Récupère les données pour le tableau de bord de la commission
     */
    public function getDashboardData(): array
    {
        $stats = $this->getDefaultStats();

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
            $stats['evaluations_rapports'] = $this->getEvaluationsRapports();
        } catch (Exception $e) {
            $this->logger->error("Erreur dans getDashboardData: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Retourne les statistiques par défaut
     */
    private function getDefaultStats(): array
    {
        return [
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

    /**
     * Récupère le nombre total de rapports
     */
    private function getTotalRapports(): int
    {
        try {
            $query = "SELECT COUNT(DISTINCT id_rapport) as total FROM valider";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            $this->logger->error("Erreur getTotalRapports: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le taux de validation des rapports
     */
    private function getTauxValidation(): float
    {
        try {
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as valides,
                        COUNT(DISTINCT v.id_rapport) as total
                      FROM valider v";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                return round(($result['valides'] / $result['total']) * 100, 1);
            }
            return 0;
        } catch (Exception $e) {
            $this->logger->error("Erreur getTauxValidation: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le temps moyen de traitement
     */
    private function getTempsMoyenTraitement(): float
    {
        try {
            $query = "SELECT AVG(DATEDIFF(v.date_validation, r.date_rapport)) as temps_moyen
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      WHERE v.date_validation IS NOT NULL 
                      AND r.date_rapport IS NOT NULL";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['temps_moyen'] ?? 0, 1);
        } catch (Exception $e) {
            $this->logger->error("Erreur getTempsMoyenTraitement: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le nombre de rapports en attente
     */
    private function getRapportsEnAttente(): int
    {
        try {
            $query = "SELECT COUNT(DISTINCT a.id_rapport) as en_attente
                      FROM approuver a
                      LEFT JOIN valider v ON a.id_rapport = v.id_rapport
                      WHERE v.id_rapport IS NULL";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['en_attente'] ?? 0;
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportsEnAttente: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère l'évolution mensuelle des rapports
     */
    private function getEvolutionMensuelle(): array
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
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getEvolutionMensuelle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la répartition par statut
     */
    private function getRepartitionStatuts(): array
    {
        try {
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
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getRepartitionStatuts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la performance par catégorie
     */
    private function getPerformanceCategories(): array
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
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getPerformanceCategories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les activités récentes
     */
    private function getActivitesRecentes(): array
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
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getActivitesRecentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails des rapports pour le tableau
     */
    private function getRapportsDetails(): array
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
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportsDetails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les évaluations des rapports
     */
    private function getEvaluationsRapports(): array
    {
        try {
            $stmt = $this->pdo->query('
                SELECT e.*, 
                       r.nom_rapport, 
                       ens.nom_enseignant, 
                       ens.prenom_enseignant
                FROM evaluations_rapports e
                LEFT JOIN rapport_etudiants r ON e.id_rapport = r.id_rapport
                LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant
                ORDER BY e.date_evaluation DESC
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getEvaluationsRapports: " . $e->getMessage());
            return [];
        }
    }
}
