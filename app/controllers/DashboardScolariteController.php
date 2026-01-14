<?php

namespace App\Controllers;

use PDO;
use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Scolarite;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * DashboardScolariteController - Vue spécifique Scolarité
 * 
 * Ce contrôleur gère l'affichage du tableau de bord de la scolarité :
 * - Statistiques des étudiants
 * - Nouvelles inscriptions
 * - Notes à valider
 * - Paiements en attente
 * 
 * @package App\Controllers
 */
class DashboardScolariteController
{
    private PDO $pdo;
    private Etudiant $etudiant;
    private Scolarite $scolarite;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Etudiant $etudiant,
        Scolarite $scolarite,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->etudiant = $etudiant;
        $this->scolarite = $scolarite;
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
        
        if (!$this->security->can($idGroupe, 'dashboard_scolarite', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur dashboard_scolarite"
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
     * Récupère les données pour le tableau de bord de la scolarité (READ)
     */
    public function getDashboardData(): array
    {
        if (!$this->checkPermission('read')) {
            return ['stats' => $this->getDefaultStats(), 'inscriptionsParNiveau' => []];
        }

        $stats = $this->getDefaultStats();

        try {
            // Nombre total d'inscriptions actives
            $query = "SELECT COUNT(*) as total FROM inscriptions";
            $stmt = $this->pdo->query($query);
            $stats['etudiants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Nouvelles inscriptions (dernière semaine)
            $query = "SELECT COUNT(*) as total FROM inscriptions WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->pdo->query($query);
            $stats['nouvelles_inscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Statistiques des réclamations
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut_reclamation = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                        SUM(CASE WHEN statut_reclamation = 'résolue' OR statut_reclamation = 'traitée' THEN 1 ELSE 0 END) as resolues,
                        SUM(CASE WHEN statut_reclamation = 'rejeté' OR statut_reclamation = 'rejetée' THEN 1 ELSE 0 END) as rejetees
                      FROM reclamations";
            $stmt = $this->pdo->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['reclamations_total'] = $result['total'] ?? 0;
            $stats['reclamations_en_attente'] = $result['en_attente'] ?? 0;
            $stats['reclamations_resolues'] = $result['resolues'] ?? 0;
            $stats['reclamations_rejetees'] = $result['rejetees'] ?? 0;

            // Statistiques des paiements
            $etudiantsInscrits = $this->scolarite->getEtudiantsInscrits();
            $complete = 0;
            $partial = 0;
            $montantTotalPercu = 0;
            $montantEnAttente = 0;

            foreach ($etudiantsInscrits as $etudiant) {
                $reste_a_payer = isset($etudiant['reste_a_payer']) ? floatval($etudiant['reste_a_payer']) : 0;
                $montant_paye = isset($etudiant['montant_paye']) ? floatval($etudiant['montant_paye']) : 0;

                if ($reste_a_payer <= 0) {
                    $complete++;
                    $montantTotalPercu += $montant_paye;
                } else {
                    $partial++;
                    $montantEnAttente += $reste_a_payer;
                }
            }

            $stats['paiements_complets'] = $complete;
            $stats['paiements_partiels'] = $partial;
            $stats['montant_percu'] = $montantTotalPercu;
            $stats['montant_attente'] = $montantEnAttente;
            $stats['montant_total_paiements'] = $montantTotalPercu + $montantEnAttente;

            // Données pour le graphique des inscriptions par niveau d'étude
            $inscriptionsParNiveau = $this->getInscriptionsParNiveau();

            return [
                'stats' => $stats,
                'inscriptionsParNiveau' => $inscriptionsParNiveau
            ];

        } catch(Exception $e) {
            $this->logger->error("Erreur getDashboardData: " . $e->getMessage());
            return [
                'stats' => $stats,
                'inscriptionsParNiveau' => [],
                'error' => "Une erreur est survenue lors de la récupération des données"
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
            'nouvelles_inscriptions' => 0,
            'reclamations_en_attente' => 0,
            'reclamations_resolues' => 0,
            'paiements_complets' => 0,
            'paiements_partiels' => 0,
            'montant_total_paiements' => 0,
            'paiements_valides' => 0,
            'paiements_retard' => 0,
            'montant_percu' => 0,
            'montant_attente' => 0,
            'reclamations_total' => 0,
            'reclamations_rejetees' => 0
        ];
    }

    /**
     * Récupère les inscriptions par niveau
     */
    private function getInscriptionsParNiveau(): array
    {
        try {
            $query = "SELECT 
                        CASE 
                            WHEN n.lib_niv_etude LIKE '%Licence 1%' THEN 'Licence 1'
                            WHEN n.lib_niv_etude LIKE '%Licence 2%' THEN 'Licence 2'
                            WHEN n.lib_niv_etude LIKE '%Licence 3%' THEN 'Licence 3'
                            WHEN n.lib_niv_etude LIKE '%Master 1%' THEN 'Master 1'
                            WHEN n.lib_niv_etude LIKE '%Master 2%' THEN 'Master 2'
                            ELSE n.lib_niv_etude
                        END as niveau,
                        COUNT(i.id_inscription) as total
                      FROM inscriptions i
                      JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                      GROUP BY 
                        CASE 
                            WHEN n.lib_niv_etude LIKE '%Licence 1%' THEN 'Licence 1'
                            WHEN n.lib_niv_etude LIKE '%Licence 2%' THEN 'Licence 2'
                            WHEN n.lib_niv_etude LIKE '%Licence 3%' THEN 'Licence 3'
                            WHEN n.lib_niv_etude LIKE '%Master 1%' THEN 'Master 1'
                            WHEN n.lib_niv_etude LIKE '%Master 2%' THEN 'Master 2'
                            ELSE n.lib_niv_etude
                        END
                      ORDER BY 
                        CASE niveau
                            WHEN 'Licence 1' THEN 1
                            WHEN 'Licence 2' THEN 2
                            WHEN 'Licence 3' THEN 3
                            WHEN 'Master 1' THEN 4
                            WHEN 'Master 2' THEN 5
                            ELSE 6
                        END";
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getInscriptionsParNiveau: " . $e->getMessage());
            return [];
        }
    }
}
