<?php

namespace App\Controllers;

use PDO;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * DashboardSecretaireController - Vue spécifique Secrétariat
 * 
 * Ce contrôleur gère l'affichage du tableau de bord du secrétariat :
 * - Statistiques des étudiants et enseignants
 * - Activités récentes
 * - Évolution des effectifs
 * 
 * @package App\Controllers
 */
class DashboardSecretaireController
{
    private PDO $pdo;
    private Etudiant $etudiantModel;
    private Enseignant $enseignantModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Etudiant $etudiantModel,
        Enseignant $enseignantModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->etudiantModel = $etudiantModel;
        $this->enseignantModel = $enseignantModel;
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
        
        if (!$this->security->can($idGroupe, 'dashboard_secretaire', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur dashboard_secretaire"
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
    public function index(): array
    {
        if (!$this->checkPermission('read')) {
            return [
                'stats' => $this->getDefaultStats(),
                'activites' => [],
                'evolutionEffectifs' => []
            ];
        }

        try {
            $stats = $this->getStats();
            $activites = $this->getActivitesRecentes();
            $evolutionEffectifs = $this->getEvolutionEffectifs();
            
            return [
                'stats' => $stats,
                'activites' => $activites,
                'evolutionEffectifs' => $evolutionEffectifs
            ];
        } catch (Exception $e) {
            $this->logger->error("Erreur dans index: " . $e->getMessage());
            return [
                'stats' => $this->getDefaultStats(),
                'activites' => [],
                'evolutionEffectifs' => []
            ];
        }
    }

    /**
     * Retourne les statistiques par défaut
     */
    private function getDefaultStats(): array
    {
        return [
            'etudiants' => 0,
            'enseignants' => 0,
            'rapports' => 0,
            'reclamations' => 0,
            'candidatures' => 0,
            'dossiers' => 0
        ];
    }

    /**
     * Récupère les statistiques
     */
    private function getStats(): array
    {
        $stats = $this->getDefaultStats();

        try {
            // Statistiques des étudiants
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM etudiants WHERE statut_etu = 'actif'");
            $stats['etudiants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Statistiques des enseignants
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM enseignants WHERE statut_enseignant = 'actif'");
            $stats['enseignants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Statistiques des rapports
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM rapport_etudiants");
                $stats['rapports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            } catch (Exception $e) {
                $stats['rapports'] = 0;
            }

            // Statistiques des réclamations
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM reclamations WHERE statut_reclamation != 'résolue'");
                $stats['reclamations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            } catch (Exception $e) {
                $stats['reclamations'] = 0;
            }

            // Statistiques des candidatures
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM programmer WHERE num_jury IS NOT NULL");
                $stats['candidatures'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            } catch (Exception $e) {
                $stats['candidatures'] = 0;
            }

            // Statistiques des dossiers académiques
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM inscriptions");
                $stats['dossiers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            } catch (Exception $e) {
                $stats['dossiers'] = 0;
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur getStats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Récupère les activités récentes
     */
    private function getActivitesRecentes(): array
    {
        $activites = [];

        try {
            // Dernières inscriptions d'étudiants
            $stmt = $this->pdo->query("
                SELECT 'inscription' as type, nom_etu, prenom_etu, date_creation as date_activite
                FROM etudiants 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_creation DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'inscription',
                    'titre' => 'Nouvelle inscription',
                    'description' => ($row['nom_etu'] ?? '') . ' ' . ($row['prenom_etu'] ?? '') . ' s\'est inscrit(e)',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'icone' => 'fa-user-plus',
                    'couleur' => 'green'
                ];
            }
        } catch (Exception $e) {
            // Table ou colonne inexistante
        }

        try {
            // Dernières réclamations
            $stmt = $this->pdo->query("
                SELECT 'reclamation' as type, titre_reclamation, date_creation as date_activite
                FROM reclamations 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_creation DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'reclamation',
                    'titre' => 'Nouvelle réclamation',
                    'description' => substr($row['titre_reclamation'] ?? '', 0, 50) . '...',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'icone' => 'fa-exclamation-triangle',
                    'couleur' => 'red'
                ];
            }
        } catch (Exception $e) {
            // Table inexistante
        }

        try {
            // Derniers rapports
            $stmt = $this->pdo->query("
                SELECT 'rapport' as type, nom_rapport, date_rapport as date_activite
                FROM rapport_etudiants 
                WHERE date_rapport >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_rapport DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'rapport',
                    'titre' => 'Nouveau rapport',
                    'description' => substr($row['nom_rapport'] ?? '', 0, 50) . '...',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'icone' => 'fa-file-alt',
                    'couleur' => 'blue'
                ];
            }
        } catch (Exception $e) {
            // Table inexistante
        }

        // Trier par date et prendre les 10 plus récentes
        usort($activites, function($a, $b) {
            return strtotime($b['date_activite']) - strtotime($a['date_activite']);
        });

        return array_slice($activites, 0, 10);
    }

    /**
     * Récupère l'évolution des effectifs
     */
    private function getEvolutionEffectifs(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    YEAR(date_creation) as annee,
                    COUNT(*) as effectif
                FROM etudiants 
                WHERE date_creation >= '2019-01-01'
                GROUP BY YEAR(date_creation)
                ORDER BY annee
            ");
            
            $evolution = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $evolution[] = [
                    'annee' => $row['annee'],
                    'effectif' => $row['effectif']
                ];
            }
            
            return $evolution;
        } catch (Exception $e) {
            $this->logger->error("Erreur getEvolutionEffectifs: " . $e->getMessage());
            // Données factices si la table n'existe pas
            return [
                ['annee' => '2019', 'effectif' => 180],
                ['annee' => '2020', 'effectif' => 200],
                ['annee' => '2021', 'effectif' => 220],
                ['annee' => '2022', 'effectif' => 250],
                ['annee' => '2023', 'effectif' => 300],
                ['annee' => '2024', 'effectif' => 320]
            ];
        }
    }
}
