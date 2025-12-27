<?php
declare(strict_types=1);

namespace App\Services;

use App\Enums\Action;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des permissions
 * 
 * Ce service gère la vérification des permissions utilisateur avec:
 * - Cache local statique pour les performances
 * - Support des rôles temporaires
 * - Logging des actions critiques via Monolog
 */
class PermissionService {
    /** @var array<string, bool> Cache local statique des permissions */
    private static array $localCache = [];
    
    /**
     * @param PDO $db Connexion à la base de données
     * @param LoggerInterface $logger Logger Monolog pour l'audit
     * @param int|null $userId ID de l'utilisateur courant
     * @param int|null $groupId ID du groupe de l'utilisateur courant
     */
    public function __construct(
        private PDO $db,
        private LoggerInterface $logger,
        private ?int $userId = null,
        private ?int $groupId = null,
    ) {}

    /**
     * Vérifie si l'utilisateur courant a la permission spécifiée
     * 
     * @param string $resource Nom de la ressource (lib_traitement)
     * @param Action $action Action à vérifier
     * @return bool True si l'utilisateur a la permission
     */
    public function has(string $resource, Action $action): bool {
        if (!$this->userId) {
            $this->logger->warning('Permission check without user', ['resource' => $resource]);
            return false;
        }
        
        // SuperAdmin (groupe 5) a tous les droits
        if ($this->groupId === 5) {
            return true;
        }

        $cacheKey = "{$this->userId}_{$resource}_{$action->value}";
        
        if (!isset(self::$localCache[$cacheKey])) {
            self::$localCache[$cacheKey] = $this->checkDatabase($resource, $action);
            
            // Log des vérifications de permissions critiques
            if (in_array($action, [Action::Delete, Action::Validate], true)) {
                $this->logger->info('Permission check', [
                    'user_id' => $this->userId,
                    'resource' => $resource,
                    'action' => $action->name,
                    'granted' => self::$localCache[$cacheKey],
                ]);
            }
        }
        
        return self::$localCache[$cacheKey];
    }

    /**
     * Vérifie les permissions dans la base de données
     * 
     * @param string $resource Nom de la ressource
     * @param Action $action Action à vérifier
     * @return bool True si la permission est accordée
     */
    private function checkDatabase(string $resource, Action $action): bool {
        // 1. Vérifier les rôles temporaires actifs
        $sqlTemp = "SELECT permissions_json FROM roles_temporaires 
                    WHERE id_utilisateur = ? AND actif = 1 
                    AND NOW() BETWEEN valide_de AND valide_jusqu_a";
        $stmt = $this->db->prepare($sqlTemp);
        $stmt->execute([$this->userId]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $perms = json_decode($row['permissions_json'], true);
            if (isset($perms[$resource][$action->name]) && $perms[$resource][$action->name]) {
                return true;
            }
        }

        // 2. Vérifier permissions_actions
        $sql = "SELECT {$action->value} 
                FROM permissions_actions pa
                JOIN traitement t ON pa.id_traitement = t.id_traitement
                WHERE pa.id_GU = ? AND t.lib_traitement = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->groupId, $resource]);
        
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Invalide le cache des permissions
     * 
     * @param int|null $userId Si spécifié, invalide uniquement pour cet utilisateur
     */
    public function invalidateCache(?int $userId = null): void {
        if ($userId) {
            self::$localCache = array_filter(
                self::$localCache,
                fn($key) => !str_starts_with($key, "{$userId}_"),
                ARRAY_FILTER_USE_KEY
            );
            $this->logger->info('Cache invalidated for user', ['user_id' => $userId]);
        } else {
            self::$localCache = [];
            $this->logger->info('Full permission cache invalidated');
        }
    }
    
    /**
     * Récupère toutes les permissions d'un groupe
     * 
     * @param int $groupId ID du groupe
     * @return array<int, array<string, mixed>> Liste des permissions avec détails du traitement
     */
    public function getAllForGroup(int $groupId): array {
        $sql = "SELECT t.lib_traitement, t.label_traitement, t.icone_traitement, t.ordre_traitement, pa.*
                FROM permissions_actions pa
                JOIN traitement t ON pa.id_traitement = t.id_traitement
                WHERE pa.id_GU = ?
                ORDER BY t.ordre_traitement";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les permissions organisées par groupe et traitement
     * 
     * @return array<int, array<int, array<string, mixed>>> Matrice des permissions
     */
    public function getAllPermissions(): array {
        $sql = "SELECT pa.*, t.lib_traitement, t.label_traitement 
                FROM permissions_actions pa
                JOIN traitement t ON pa.id_traitement = t.id_traitement
                ORDER BY pa.id_GU, t.ordre_traitement";
        $stmt = $this->db->query($sql);
        
        $permissions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[$row['id_GU']][$row['id_traitement']] = $row;
        }
        return $permissions;
    }

    /**
     * Met à jour les permissions d'un groupe pour un traitement
     * 
     * @param int $groupId ID du groupe
     * @param int $traitementId ID du traitement
     * @param array<string, bool> $permissions Tableau des permissions à appliquer
     * @return bool True si la mise à jour a réussi
     */
    public function updatePermission(int $groupId, int $traitementId, array $permissions): bool {
        $sql = "INSERT INTO permissions_actions 
                (id_GU, id_traitement, peut_lire, peut_creer, peut_modifier, peut_supprimer, peut_exporter, peut_valider)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    peut_lire = VALUES(peut_lire),
                    peut_creer = VALUES(peut_creer),
                    peut_modifier = VALUES(peut_modifier),
                    peut_supprimer = VALUES(peut_supprimer),
                    peut_exporter = VALUES(peut_exporter),
                    peut_valider = VALUES(peut_valider)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $groupId,
            $traitementId,
            $permissions['peut_lire'] ?? false,
            $permissions['peut_creer'] ?? false,
            $permissions['peut_modifier'] ?? false,
            $permissions['peut_supprimer'] ?? false,
            $permissions['peut_exporter'] ?? false,
            $permissions['peut_valider'] ?? false,
        ]);

        if ($result) {
            $this->logger->info('Permission updated', [
                'group_id' => $groupId,
                'traitement_id' => $traitementId,
                'permissions' => $permissions,
            ]);
        }

        return $result;
    }

    /**
     * Définit l'utilisateur courant pour le service
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $groupId ID du groupe de l'utilisateur
     */
    public function setCurrentUser(int $userId, int $groupId): void {
        $this->userId = $userId;
        $this->groupId = $groupId;
    }

    /**
     * Récupère l'ID de l'utilisateur courant
     */
    public function getCurrentUserId(): ?int {
        return $this->userId;
    }

    /**
     * Récupère l'ID du groupe de l'utilisateur courant
     */
    public function getCurrentGroupId(): ?int {
        return $this->groupId;
    }
}
