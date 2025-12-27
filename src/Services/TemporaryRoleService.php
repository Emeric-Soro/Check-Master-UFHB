<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Psr\Log\LoggerInterface;
use DateTime;

/**
 * Service de gestion des rôles temporaires
 * 
 * Ce service permet de créer et gérer des rôles temporaires pour des situations
 * spécifiques comme le Président de Jury lors d'une soutenance.
 */
class TemporaryRoleService {
    /**
     * @param PDO $db Connexion à la base de données
     * @param LoggerInterface $logger Logger pour l'audit
     * @param PermissionService $permissionService Service de permissions
     */
    public function __construct(
        private PDO $db,
        private LoggerInterface $logger,
        private PermissionService $permissionService,
    ) {}

    /**
     * Crée un rôle temporaire de Président de Jury pour une soutenance
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $soutenanceId ID de la soutenance
     * @param DateTime $date Date de la soutenance
     * @return string Code d'accès généré (en clair, à envoyer par email)
     */
    public function createPresidentJuryRole(int $userId, int $soutenanceId, DateTime $date): string {
        // Générer un code sécurisé (8 caractères, sans confusion 0/O, 1/I)
        $code = $this->generateSecureCode(8);
        $codeHash = password_hash($code, PASSWORD_BCRYPT);
        
        // Permissions du Président du Jury
        $permissions = json_encode([
            'evaluation_soutenance' => [
                'Read' => true,
                'Update' => true,
                'Validate' => true,
            ],
        ], JSON_THROW_ON_ERROR);
        
        $validFrom = $date->format('Y-m-d 06:00:00');
        $validTo = $date->format('Y-m-d 23:59:59');
        
        $currentUserId = $_SESSION['id_utilisateur'] ?? null;
        
        $this->db->beginTransaction();
        try {
            // Créer le rôle temporaire
            $stmt = $this->db->prepare("
                INSERT INTO roles_temporaires 
                (id_utilisateur, role_code, contexte_type, contexte_id, permissions_json, valide_de, valide_jusqu_a, cree_par)
                VALUES (?, 'president_jury', 'soutenance', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $soutenanceId, $permissions, $validFrom, $validTo, $currentUserId]);
            
            // Créer le code d'accès
            $stmt = $this->db->prepare("
                INSERT INTO codes_acces_temporaires 
                (id_utilisateur, id_soutenance, code_hash, valide_de, valide_jusqu_a)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $soutenanceId, $codeHash, $validFrom, $validTo]);
            
            $this->db->commit();
            
            $this->logger->info('President jury role created', [
                'user_id' => $userId,
                'soutenance_id' => $soutenanceId,
                'date' => $date->format('Y-m-d'),
                'created_by' => $currentUserId,
            ]);
            
            return $code; // Retourne le code en clair pour l'envoi par email
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to create president jury role', [
                'user_id' => $userId,
                'soutenance_id' => $soutenanceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Valide un code d'accès temporaire
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $code Code d'accès à valider
     * @return bool True si le code est valide
     */
    public function validateAccessCode(int $userId, string $code): bool {
        $stmt = $this->db->prepare("
            SELECT id_code, code_hash FROM codes_acces_temporaires 
            WHERE id_utilisateur = ? AND utilise = 0 
            AND NOW() BETWEEN valide_de AND valide_jusqu_a
            ORDER BY id_code DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || !password_verify($code, $row['code_hash'])) {
            $this->logger->warning('Invalid access code attempt', [
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            return false;
        }
        
        // Marquer comme utilisé
        $this->db->prepare("
            UPDATE codes_acces_temporaires 
            SET utilise = 1, utilise_le = NOW(), ip_utilisation = ?
            WHERE id_code = ?
        ")->execute([$_SERVER['REMOTE_ADDR'] ?? '', $row['id_code']]);
        
        $this->permissionService->invalidateCache($userId);
        
        $this->logger->info('Access code validated', [
            'user_id' => $userId,
            'code_id' => $row['id_code'],
        ]);
        
        return true;
    }
    
    /**
     * Désactive un rôle temporaire
     * 
     * @param int $roleId ID du rôle temporaire
     * @return bool True si la désactivation a réussi
     */
    public function deactivateRole(int $roleId): bool {
        $stmt = $this->db->prepare("
            UPDATE roles_temporaires SET actif = 0 WHERE id_role_temp = ?
        ");
        $result = $stmt->execute([$roleId]);
        
        if ($result) {
            $this->logger->info('Temporary role deactivated', ['role_id' => $roleId]);
        }
        
        return $result;
    }
    
    /**
     * Récupère les rôles temporaires actifs d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array<array<string, mixed>> Liste des rôles temporaires actifs
     */
    public function getActiveRoles(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM roles_temporaires 
            WHERE id_utilisateur = ? AND actif = 1 
            AND NOW() BETWEEN valide_de AND valide_jusqu_a
            ORDER BY valide_de DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère tous les rôles temporaires (pour l'administration)
     * 
     * @param bool $activeOnly Ne retourner que les rôles actifs
     * @return array<array<string, mixed>> Liste des rôles temporaires
     */
    public function getAllTemporaryRoles(bool $activeOnly = false): array {
        $sql = "
            SELECT rt.*, u.nom_utilisateur, u.prenom_utilisateur
            FROM roles_temporaires rt
            JOIN utilisateur u ON rt.id_utilisateur = u.id_utilisateur
        ";
        
        if ($activeOnly) {
            $sql .= " WHERE rt.actif = 1 AND NOW() BETWEEN rt.valide_de AND rt.valide_jusqu_a";
        }
        
        $sql .= " ORDER BY rt.cree_le DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Génère un code sécurisé sans caractères ambigus
     * 
     * @param int $length Longueur du code
     * @return string Code généré
     */
    private function generateSecureCode(int $length): string {
        // Caractères sans ambiguïté (pas de 0/O, 1/I/l)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        $charsLength = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $charsLength - 1)];
        }
        
        return $code;
    }
}
