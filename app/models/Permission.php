<?php

/**
 * Modèle Permission
 * Gère les permissions granulaires CRUD pour le système RBAC
 */
class Permission
{
    private $db;

    // Constants pour les actions
    const ACTION_CREATE = 1;  // Ajouter
    const ACTION_UPDATE = 3;  // Modifier
    const ACTION_DELETE = 6;  // Supprimer
    const ACTION_READ = 7;    // Consulter

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupère toutes les permissions d'un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @return array Tableau associatif [id_traitement => [id_action => true]]
     */
    public function getPermissionsByGroupe($id_GU)
    {
        try {
            $sql = "SELECT p.id_traitement, p.id_action, a.lib_action 
                    FROM permissions p
                    INNER JOIN action a ON p.id_action = a.id_action
                    WHERE p.id_GU = :id_GU
                    ORDER BY p.id_traitement, p.id_action";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_GU' => $id_GU]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organiser les permissions par traitement et action
            $permissions = [];
            foreach ($results as $row) {
                if (!isset($permissions[$row['id_traitement']])) {
                    $permissions[$row['id_traitement']] = [];
                }
                $permissions[$row['id_traitement']][$row['id_action']] = true;
            }

            return $permissions;
        } catch (PDOException $e) {
            error_log("Erreur dans getPermissionsByGroupe: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un groupe a une permission spécifique
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @param int $id_action ID de l'action
     * @return bool True si la permission existe
     */
    public function hasPermission($id_GU, $id_traitement, $id_action)
    {
        try {
            $sql = "SELECT COUNT(*) FROM permissions 
                    WHERE id_GU = :id_GU 
                    AND id_traitement = :id_traitement 
                    AND id_action = :id_action";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_GU' => $id_GU,
                ':id_traitement' => $id_traitement,
                ':id_action' => $id_action
            ]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur dans hasPermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute une permission
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @param int $id_action ID de l'action
     * @return bool Succès de l'opération
     */
    public function ajouterPermission($id_GU, $id_traitement, $id_action)
    {
        try {
            $sql = "INSERT INTO permissions (id_GU, id_traitement, id_action) 
                    VALUES (:id_GU, :id_traitement, :id_action)
                    ON DUPLICATE KEY UPDATE id_action = id_action";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id_GU' => $id_GU,
                ':id_traitement' => $id_traitement,
                ':id_action' => $id_action
            ]);
        } catch (PDOException $e) {
            error_log("Erreur dans ajouterPermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une permission spécifique
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @param int $id_action ID de l'action
     * @return bool Succès de l'opération
     */
    public function supprimerPermission($id_GU, $id_traitement, $id_action)
    {
        try {
            $sql = "DELETE FROM permissions 
                    WHERE id_GU = :id_GU 
                    AND id_traitement = :id_traitement 
                    AND id_action = :id_action";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id_GU' => $id_GU,
                ':id_traitement' => $id_traitement,
                ':id_action' => $id_action
            ]);
        } catch (PDOException $e) {
            error_log("Erreur dans supprimerPermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime toutes les permissions d'un groupe pour un traitement
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @return bool Succès de l'opération
     */
    public function supprimerPermissionsTraitement($id_GU, $id_traitement)
    {
        try {
            $sql = "DELETE FROM permissions 
                    WHERE id_GU = :id_GU 
                    AND id_traitement = :id_traitement";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id_GU' => $id_GU,
                ':id_traitement' => $id_traitement
            ]);
        } catch (PDOException $e) {
            error_log("Erreur dans supprimerPermissionsTraitement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour les permissions d'un groupe pour un traitement
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @param array $actions Tableau des id_action à assigner
     * @return bool Succès de l'opération
     */
    public function updatePermissionsTraitement($id_GU, $id_traitement, $actions)
    {
        try {
            // Commencer une transaction
            $this->db->beginTransaction();

            // Supprimer les permissions existantes
            $this->supprimerPermissionsTraitement($id_GU, $id_traitement);

            // Ajouter les nouvelles permissions
            if (!empty($actions)) {
                foreach ($actions as $id_action) {
                    $this->ajouterPermission($id_GU, $id_traitement, $id_action);
                }
            }

            // Valider la transaction
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            error_log("Erreur dans updatePermissionsTraitement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les permissions avec détails
     * @return array Liste complète des permissions
     */
    public function getAllPermissions()
    {
        try {
            $sql = "SELECT p.*, gu.lib_GU, t.label_traitement, a.lib_action
                    FROM permissions p
                    INNER JOIN groupe_utilisateur gu ON p.id_GU = gu.id_GU
                    INNER JOIN traitement t ON p.id_traitement = t.id_traitement
                    INNER JOIN action a ON p.id_action = a.id_action
                    ORDER BY gu.lib_GU, t.ordre_traitement, a.id_action";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur dans getAllPermissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la matrice complète des permissions pour un groupe
     * @param int $id_GU ID du groupe d'utilisateurs
     * @return array Matrice [id_traitement][id_action] = bool
     */
    public function getPermissionMatrix($id_GU)
    {
        $permissions = $this->getPermissionsByGroupe($id_GU);
        
        // Créer une matrice complète avec toutes les combinaisons possibles
        $matrix = [];
        $actions = [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE, self::ACTION_READ];
        
        // Récupérer tous les traitements
        $stmt = $this->db->prepare("SELECT id_traitement FROM traitement ORDER BY ordre_traitement");
        $stmt->execute();
        $traitements = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($traitements as $id_traitement) {
            $matrix[$id_traitement] = [];
            foreach ($actions as $id_action) {
                $matrix[$id_traitement][$id_action] = isset($permissions[$id_traitement][$id_action]);
            }
        }
        
        return $matrix;
    }
}
