<?php

/**
 * Service de Permissions (Singleton)
 * Point d'accès centralisé pour les vérifications de permissions
 *
 * @author CheckMaster Team
 * @version 1.0
 */
class PermissionService
{
    private static $instance = null;
    private $permissionModel;
    private $db;

    /**
     * Constructeur privé (pattern Singleton)
     */
    private function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../models/Permission.php';

        $this->db = Database::getConnection();
        $this->permissionModel = new Permission($this->db);
    }

    /**
     * Récupère l'instance unique du service
     *
     * @return PermissionService
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Vérifie une permission
     *
     * @param int|null $idGU ID du groupe (utilise la session si null)
     * @param string $traitement Traitement
     * @param string $action Action
     * @param array $context Contexte optionnel
     * @return bool
     */
    public function check(?int $idGU, string $traitement, string $action, array $context = []): bool
    {
        $idGU = $idGU ?? ($_SESSION['id_GU'] ?? null);

        if ($idGU === null) {
            return false;
        }

        return $this->permissionModel->hasPermission($idGU, $traitement, $action, $context);
    }

    /**
     * Vérifie une permission pour l'utilisateur courant
     *
     * @param string $traitement Traitement
     * @param string $action Action
     * @param array $context Contexte optionnel
     * @return bool
     */
    public function can(string $traitement, string $action, array $context = []): bool
    {
        return $this->check(null, $traitement, $action, $context);
    }

    /**
     * Récupère les actions autorisées pour l'utilisateur courant
     *
     * @param string $traitement Traitement
     * @return array
     */
    public function getActions(string $traitement): array
    {
        $idGU = $_SESSION['id_GU'] ?? null;

        if ($idGU === null) {
            return [];
        }

        return $this->permissionModel->getActionsAutorisees($idGU, $traitement);
    }

    /**
     * Récupère le model Permission
     *
     * @return Permission
     */
    public function getModel(): Permission
    {
        return $this->permissionModel;
    }

    /**
     * Vide le cache des permissions
     *
     * @param int|null $idGU ID du groupe
     */
    public function clearCache(?int $idGU = null): void
    {
        $this->permissionModel->clearCache($idGU);
    }

    /**
     * Empêche le clonage (pattern Singleton)
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation (pattern Singleton)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}