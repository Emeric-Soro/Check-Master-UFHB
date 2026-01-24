<?php

namespace CheckMaster\Security;

use PDO;

/**
 * Service d’autorisation: encapsule l’accès à la table permissions/fonctionnalites.
 * Utilisable par le nouveau Router et par l’ancien layout.
 */
final class AuthorizationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifie une permission CRUD pour un groupe et une URL/page legacy (?page=...).
     * @param string $pageSlug ex: 'gestion_utilisateurs', 'dashboard'
     * @param string $action   'voir'|'creer'|'modifier'|'supprimer'
     */
    public function canAccessLegacyPage(int $idGroupe, string $pageSlug, string $action): bool
    {
        // On interroge fonctionnalites.url_fonctionnalite via la méthode déjà existante.
        require_once __DIR__ . '/../models/Fonctionnalite.php';
        require_once __DIR__ . '/../models/Permission.php';

        $fonctionnaliteModel = new \Fonctionnalite($this->pdo);
        $permissionModel = new \Permission($this->pdo);

        $fonctionnalite = $fonctionnaliteModel->getFonctionnaliteByUrl($pageSlug);
        if (!$fonctionnalite) {
            return false;
        }
        $perm = $permissionModel->getPermissions($idGroupe, (int) $fonctionnalite->id_fonctionnalite);
        if (!$perm) {
            return false;
        }

        switch ($action) {
            case 'voir':
                return (bool) $perm->peut_voir;
            case 'creer':
                return (bool) $perm->peut_creer;
            case 'modifier':
                return (bool) $perm->peut_modifier;
            case 'supprimer':
                return (bool) $perm->peut_supprimer;
            default:
                return false;
        }
    }
}

