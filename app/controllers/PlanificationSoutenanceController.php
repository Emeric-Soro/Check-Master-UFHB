<?php

namespace App\Controllers;

use PDO;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use DateTime;
use Exception;

/**
 * PlanificationSoutenanceController - Logistique (Salles, Heures)
 * 
 * Ce contrôleur gère la planification logistique des soutenances :
 * - Attribution des salles
 * - Définition des dates et heures
 * - Gestion des conflits de créneaux
 * 
 * @package App\Controllers
 */
class PlanificationSoutenanceController
{
    private PDO $pdo;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
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
        
        if (!$this->security->can($idGroupe, 'plannificaiton_soutenance', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur plannificaiton_soutenance"
            );
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Accès refusé."]);
            } else {
                $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires.";
                if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                    http_response_code(403);
                    require __DIR__ . '/../../ressources/views/errors/403.php';
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Vérifier si un étudiant a déjà une planification complète
     */
    private function etudiantDejaPlannifie(string $numEtu, ?int $excludeId = null): bool
    {
        try {
            $sql = "
                SELECT COUNT(*) as count
                FROM programmer 
                WHERE num_etud = ?
                AND id_salle IS NOT NULL 
                AND date_soutenance IS NOT NULL 
                AND heure_soutenance IS NOT NULL
            ";

            $params = [$numEtu];

            if ($excludeId) {
                $sql .= " AND id_programmation != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        } catch (Exception $e) {
            $this->logger->error('Erreur etudiantDejaPlannifie: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Action : Récupérer tous les étudiants qui ont une attribution de jury (READ)
     */
    public function getEtudiantsAvecJuryForView(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            $sql = "
                SELECT DISTINCT
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_etu as matricule_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle,
                    CASE 
                        WHEN p.id_salle IS NOT NULL AND p.date_soutenance IS NOT NULL AND p.heure_soutenance IS NOT NULL THEN 'complete'
                        WHEN p.num_jury IS NOT NULL THEN 'partial'
                        ELSE 'none'
                    END as statut_planification
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.num_jury IS NOT NULL
                ORDER BY e.nom_etu ASC, e.prenom_etu ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Erreur getEtudiantsAvecJuryForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Action : Récupérer les étudiants disponibles pour nouvelle planification (READ)
     */
    public function getEtudiantsDisponiblesForView(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            $sql = "
                SELECT DISTINCT
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_etu as matricule_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle,
                    CASE 
                        WHEN p.id_salle IS NOT NULL AND p.date_soutenance IS NOT NULL AND p.heure_soutenance IS NOT NULL THEN 'complete'
                        WHEN p.num_jury IS NOT NULL THEN 'partial'
                        ELSE 'none'
                    END as statut_planification
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.num_jury IS NOT NULL
                AND (p.id_salle IS NULL OR p.date_soutenance IS NULL OR p.heure_soutenance IS NULL)
                ORDER BY e.nom_etu ASC, e.prenom_etu ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Erreur getEtudiantsDisponiblesForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Action : Récupérer toutes les salles disponibles (READ)
     */
    public function getSallesForView(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    id_salle,
                    lib_salle
                FROM salles
                ORDER BY lib_salle
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Erreur getSallesForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Action : Récupérer toutes les planifications pour affichage (READ)
     */
    public function getPlanificationsForView(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_etu as matricule_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.id_salle IS NOT NULL 
                AND p.date_soutenance IS NOT NULL 
                AND p.heure_soutenance IS NOT NULL
                ORDER BY p.date_soutenance ASC, p.heure_soutenance ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Erreur getPlanificationsForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Action : Planifier une soutenance (UPDATE)
     */
    public function planifierSoutenance(): array
    {
        if (!$this->checkPermission('update')) {
            return [
                'success' => false,
                'message' => 'Accès refusé'
            ];
        }

        try {
            $idProgrammation = $this->security->sanitizeInput($_POST['id_programmation'] ?? null);
            $idSalle = $this->security->sanitizeInput($_POST['id_salle'] ?? null);
            $dateSoutenance = $this->security->sanitizeInput($_POST['date_soutenance'] ?? null);
            $heureSoutenance = $this->security->sanitizeInput($_POST['heure_soutenance'] ?? null);
            $editId = $this->security->sanitizeInput($_POST['edit_id'] ?? null);

            // Validation des données requises
            if (empty($idProgrammation)) {
                throw new Exception('ID de programmation requis');
            }
            if (empty($idSalle)) {
                throw new Exception('Salle requise');
            }
            if (empty($dateSoutenance)) {
                throw new Exception('Date de soutenance requise');
            }
            if (empty($heureSoutenance)) {
                throw new Exception('Heure de soutenance requise');
            }

            // Validation de la date
            $selectedDateTime = new DateTime($dateSoutenance . ' ' . $heureSoutenance);
            $now = new DateTime();

            if ($selectedDateTime <= $now) {
                throw new Exception('La date et l\'heure de soutenance doivent être dans le futur');
            }

            $this->pdo->beginTransaction();

            $conflictCheckId = $editId ? $editId : $idProgrammation;

            // Récupérer le numéro d'étudiant
            $etudiantStmt = $this->pdo->prepare("SELECT num_etud FROM programmer WHERE id_programmation = ?");
            $etudiantStmt->execute([$conflictCheckId]);
            $etudiantData = $etudiantStmt->fetch(PDO::FETCH_ASSOC);

            if (!$etudiantData) {
                throw new Exception('Programmation non trouvée');
            }

            // Vérifier si l'étudiant n'a pas déjà une planification complète
            if ($this->etudiantDejaPlannifie($etudiantData['num_etud'], $editId ? (int)$editId : null)) {
                throw new Exception('Cet étudiant a déjà une soutenance complètement planifiée');
            }

            // Vérifier les conflits de salle
            $conflictStmt = $this->pdo->prepare("
                SELECT COUNT(*) as conflicts
                FROM programmer 
                WHERE id_salle = ? 
                AND date_soutenance = ? 
                AND heure_soutenance = ?
                AND id_programmation != ?
            ");
            $conflictStmt->execute([
                $idSalle,
                $dateSoutenance,
                $heureSoutenance,
                $conflictCheckId
            ]);

            if ($conflictStmt->fetch(PDO::FETCH_ASSOC)['conflicts'] > 0) {
                throw new Exception('Conflit : Cette salle est déjà occupée à cette date et heure');
            }

            $updateId = $editId ? $editId : $idProgrammation;

            // Mettre à jour la programmation
            $sql = "
                UPDATE programmer 
                SET id_salle = ?, 
                    date_soutenance = ?, 
                    heure_soutenance = ?
                WHERE id_programmation = ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                $idSalle,
                $dateSoutenance,
                $heureSoutenance,
                $updateId
            ]);

            if (!$success) {
                throw new Exception('Erreur lors de la mise à jour en base de données');
            }

            $this->pdo->commit();

            $message = $editId ? 'Planification modifiée avec succès' : 'Soutenance planifiée avec succès';
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'programmer', 'Succès');
            $this->logger->info("Planification soutenance {$updateId} effectuée");

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logger->error("Erreur planifierSoutenance: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la planification : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Action : Supprimer une planification (DELETE)
     */
    public function supprimerPlanification(): array
    {
        if (!$this->checkPermission('delete')) {
            return [
                'success' => false,
                'message' => 'Accès refusé'
            ];
        }

        try {
            $id = $this->security->sanitizeInput($_POST['id_programmation'] ?? null);

            if (empty($id)) {
                throw new Exception('ID de programmation requis');
            }

            // Remettre à NULL la salle, date et heure (garder l'attribution du jury)
            $sql = "
                UPDATE programmer 
                SET id_salle = NULL, 
                    date_soutenance = NULL, 
                    heure_soutenance = NULL
                WHERE id_programmation = ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$id]);

            if (!$success) {
                throw new Exception('Erreur lors de la suppression en base de données');
            }

            $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'programmer', 'Succès');
            $this->logger->info("Planification soutenance {$id} supprimée");

            return [
                'success' => true,
                'message' => 'Planification supprimée avec succès'
            ];

        } catch (Exception $e) {
            $this->logger->error("Erreur supprimerPlanification: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Action : Récupérer une planification par ID pour modification (READ - AJAX)
     */
    public function getPlanification(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $id = $this->security->sanitizeInput($_GET['id'] ?? null);

            if (!$id) {
                throw new Exception('ID requis');
            }

            $sql = "
                SELECT 
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                WHERE p.id_programmation = ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $planification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$planification) {
                throw new Exception('Planification non trouvée');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $planification
            ]);
        } catch (Exception $e) {
            $this->logger->error("Erreur getPlanification: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ]);
        }
    }
}
