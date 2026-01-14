<?php

namespace App\Controllers;

use PDO;
use App\Models\Reclamation;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * GestionReclamationsScolariteController - Traitement des réclamations par l'administration
 * 
 * Ce contrôleur gère les réclamations côté administration :
 * - Liste des réclamations en cours et traitées
 * - Changement de statut des réclamations
 * - Suivi administratif
 * 
 * @package App\Controllers
 */
class GestionReclamationsScolariteController
{
    private PDO $db;
    private Reclamation $reclamationModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $db,
        Reclamation $reclamationModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->reclamationModel = $reclamationModel;
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
        
        if (!$this->security->can($idGroupe, 'gestion_reclamations_scolarite', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur gestion_reclamations_scolarite"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour accéder à cette fonctionnalité.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action : Afficher la liste des réclamations (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Récupérer toutes les réclamations avec infos étudiant
            $allReclamations = $this->reclamationModel->getAllReclamationsWithEtudiant();

            // Séparer en cours/attente et traitées/clôturées
            $reclamationsEnCours = [];
            $reclamationsTraitees = [];
            
            foreach ($allReclamations as $rec) {
                if ($rec->statut_reclamation === 'En attente' || $rec->statut_reclamation === 'En cours') {
                    $reclamationsEnCours[] = $rec;
                } else {
                    $reclamationsTraitees[] = $rec;
                }
            }

            // Passer aux vues
            $GLOBALS['reclamationsEnCours'] = $reclamationsEnCours;
            $GLOBALS['reclamationsTraitees'] = $reclamationsTraitees;
            
            $this->logger->info("Consultation des réclamations par " . ($_SESSION['login_utilisateur'] ?? 'inconnu'));
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans GestionReclamationsScolariteController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des réclamations.";
        }
    }

    /**
     * Action : Changer le statut d'une réclamation (UPDATE)
     */
    public function changerStatut(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        try {
            if (isset($_GET['id']) && isset($_POST['nouveau_statut'])) {
                $id = (int) $_GET['id'];
                $nouveauStatut = $this->security->sanitizeInput($_POST['nouveau_statut']);
                
                // Valider le statut
                $statutsValides = ['En attente', 'En cours', 'Résolue', 'Rejetée'];
                if (!in_array($nouveauStatut, $statutsValides)) {
                    $this->logger->warning("Tentative de mise à jour avec statut invalide: " . $nouveauStatut);
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'reclamations', 'Erreur');
                    header('Location: ?page=gestion_reclamations_scolarite&error=statut_invalide');
                    exit;
                }
                
                if ($this->reclamationModel->updateStatut($id, $nouveauStatut)) {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'reclamations', 'Succès');
                    $this->logger->info("Statut de la réclamation {$id} changé en '{$nouveauStatut}'");
                    header('Location: ?page=gestion_reclamations_scolarite&success=1');
                } else {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'reclamations', 'Erreur');
                    $this->logger->error("Erreur lors de la mise à jour du statut de la réclamation {$id}");
                    header('Location: ?page=gestion_reclamations_scolarite&error=update_failed');
                }
            } else {
                header('Location: ?page=gestion_reclamations_scolarite');
            }
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur changerStatut: " . $e->getMessage());
            header('Location: ?page=gestion_reclamations_scolarite&error=exception');
            exit;
        }
    }
}
