<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Enums\Action;
use App\Services\PermissionService;
use App\Attributes\RequirePermission;
use ParagonIE\AntiCSRF\AntiCSRF;
use Valitron\Validator;
use PDO;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * Contrôleur de gestion des permissions
 * 
 * Ce contrôleur gère l'interface d'administration de la matrice des permissions
 * avec protection CSRF et validation des entrées.
 */
class PermissionController {
    /**
     * @param PDO $db Connexion à la base de données
     * @param PermissionService $permissionService Service de permissions
     * @param LoggerInterface $logger Logger pour l'audit
     * @param Environment $twig Environnement Twig
     * @param AntiCSRF $csrf Protection CSRF
     */
    public function __construct(
        private PDO $db,
        private PermissionService $permissionService,
        private LoggerInterface $logger,
        private Environment $twig,
        private AntiCSRF $csrf,
    ) {}

    /**
     * Affiche la matrice des permissions
     */
    #[RequirePermission('parametres_generaux', Action::Read)]
    public function index(): void {
        $groups = $this->db->query("
            SELECT * FROM groupe_utilisateur 
            ORDER BY niveau_hierarchique DESC, lib_GU ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $traitements = $this->db->query("
            SELECT * FROM traitement 
            ORDER BY ordre_traitement ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Charger les permissions existantes
        $permissions = $this->permissionService->getAllPermissions();
        
        echo $this->twig->render('admin/permissions_matrix.twig', [
            'groups' => $groups,
            'traitements' => $traitements,
            'permissions' => $permissions,
            'actions' => Action::cases(),
            'csrf_field' => $this->csrf->insertToken('', false),
        ]);
    }

    /**
     * Met à jour les permissions
     */
    #[RequirePermission('parametres_generaux', Action::Update)]
    public function update(): void {
        // Vérification CSRF
        if (!$this->csrf->validateRequest()) {
            $this->logger->warning('CSRF validation failed on permissions update', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_id' => $_SESSION['id_utilisateur'] ?? null,
            ]);
            header('Location: ?page=parametres_generaux&action=gestion_permissions&error=csrf');
            return;
        }
        
        // Validation des données avec Valitron
        $v = new Validator($_POST);
        $v->rule('array', 'perms');
        
        if (!$v->validate()) {
            $this->logger->warning('Invalid permissions data', ['errors' => $v->errors()]);
            header('Location: ?page=parametres_generaux&action=gestion_permissions&error=validation');
            return;
        }
        
        $perms = $_POST['perms'] ?? [];
        
        $this->db->beginTransaction();
        try {
            // Supprimer les anciennes permissions
            $this->db->exec("DELETE FROM permissions_actions");
            
            // Insérer les nouvelles
            $stmt = $this->db->prepare("
                INSERT INTO permissions_actions 
                (id_GU, id_traitement, peut_lire, peut_creer, peut_modifier, peut_supprimer, peut_exporter, peut_valider)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($perms as $groupId => $traitements) {
                foreach ($traitements as $traitementId => $actions) {
                    $stmt->execute([
                        (int) $groupId,
                        (int) $traitementId,
                        isset($actions['peut_lire']),
                        isset($actions['peut_creer']),
                        isset($actions['peut_modifier']),
                        isset($actions['peut_supprimer']),
                        isset($actions['peut_exporter']),
                        isset($actions['peut_valider']),
                    ]);
                }
            }
            
            $this->db->commit();
            $this->permissionService->invalidateCache(); // Invalider le cache global
            
            $this->logger->info('Permissions updated successfully', [
                'admin_id' => $_SESSION['id_utilisateur'] ?? null,
                'groups_modified' => count($perms),
            ]);
            
            header('Location: ?page=parametres_generaux&action=gestion_permissions&success=1');
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to update permissions', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['id_utilisateur'] ?? null,
            ]);
            header('Location: ?page=parametres_generaux&action=gestion_permissions&error=db');
        }
    }

    /**
     * Récupère les permissions d'un groupe spécifique (API JSON)
     */
    #[RequirePermission('parametres_generaux', Action::Read)]
    public function getGroupPermissions(): void {
        $groupId = (int) ($_GET['group_id'] ?? 0);
        
        if ($groupId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid group ID']);
            return;
        }
        
        $permissions = $this->permissionService->getAllForGroup($groupId);
        
        header('Content-Type: application/json');
        echo json_encode($permissions);
    }
}
