<?php

namespace CheckMaster\Security;

use PDO;

/**
 * Service unifié pour permissions legacy:
 * - résout la fonctionnalité à partir de (?page=... [&action=...])
 * - résout l'action CRUD requise (DB route_actions -> fallback heuristique)
 * - vérifie la table permissions
 */
final class RoutePermissionService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{fonctionnalite:object|null,action:'voir'|'creer'|'modifier'|'supprimer',pattern:string,reason:string}
     */
    public function resolveLegacy(array $get, array $post, string $method): array
    {
        $page = isset($get['page']) && is_string($get['page']) ? $get['page'] : '';
        if ($page === '') {
            return ['fonctionnalite' => null, 'action' => 'voir', 'pattern' => '', 'reason' => 'no_page'];
        }

        require_once __DIR__ . '/../models/Fonctionnalite.php';

        $fonctionnaliteModel = new \Fonctionnalite($this->pdo);

        // 1) tentative la plus précise: page + action (si présent)
        $candidates = [];
        $candidates[] = '?page=' . $page;
        if (isset($get['action']) && is_string($get['action']) && $get['action'] !== '') {
            $candidates[] = '?page=' . $page . '&action=' . $get['action'];
        }

        $fonctionnalite = null;
        foreach (array_reverse($candidates) as $candidate) { // on teste d'abord le plus précis
            $fonctionnalite = $fonctionnaliteModel->getFonctionnaliteByUrl($candidate);
            if ($fonctionnalite) {
                break;
            }
        }

        $resolved = RouteActionResolver::resolve(
            $this->pdo,
            $page,
            $get,
            $post,
            $method
        );

        return [
            'fonctionnalite' => $fonctionnalite ?: null,
            'action' => $resolved['action'],
            'pattern' => $resolved['pattern'],
            'reason' => $resolved['reason'],
        ];
    }

    public function canAccessLegacy(int $idGroupe, array $get, array $post, string $method): bool
    {
        if ($idGroupe <= 0) {
            return false;
        }

        $resolved = $this->resolveLegacy($get, $post, $method);
        $fonctionnalite = $resolved['fonctionnalite'];
        if (!$fonctionnalite || empty($fonctionnalite->id_fonctionnalite)) {
            return false;
        }

        require_once __DIR__ . '/../models/Permission.php';

        $permissionModel = new \Permission($this->pdo);
        $perm = $permissionModel->getPermissions($idGroupe, (int) $fonctionnalite->id_fonctionnalite);
        if (!$perm) {
            return false;
        }

        switch ($resolved['action']) {
            case 'voir':
                return (bool) ($perm->peut_voir ?? false);
            case 'creer':
                return (bool) ($perm->peut_creer ?? false);
            case 'modifier':
                return (bool) ($perm->peut_modifier ?? false);
            case 'supprimer':
                return (bool) ($perm->peut_supprimer ?? false);
        }

        return false;
    }
}

