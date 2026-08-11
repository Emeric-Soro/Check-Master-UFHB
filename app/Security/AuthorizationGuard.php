<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use CheckMaster\Core\Response;
use CheckMaster\Core\ResponseFactory;
use PDO;

/**
 * Garde d'autorisation : contrôle l'accès aux pages legacy par groupe
 * (via RoutePermissionService), en préservant les exemptions historiques :
 *  - mise à jour du profil (self-service) ;
 *  - routes avec sécurité interne (ex: docviewer).
 */
final class AuthorizationGuard
{
    private RoutePermissionService $routePermissionService;

    public function __construct(PDO $pdo)
    {
        $this->routePermissionService = new RoutePermissionService($pdo);
    }

    public function routePermissionService(): RoutePermissionService
    {
        return $this->routePermissionService;
    }

    /**
     * Vérifie l'accès à la page legacy.
     *
     * @return array{allowed:bool,is_public:bool,reason:string}
     */
    public function canAccess(RequestContext $context, int $idGroupe, array $exemptions = []): array
    {
        $page = $context->page;
        if ($page === '') {
            return ['allowed' => true, 'is_public' => true, 'reason' => 'no_page'];
        }
        if (in_array($page, $exemptions, true)) {
            return ['allowed' => true, 'is_public' => true, 'reason' => 'exempted'];
        }
        if ($context->isOwnProfileUpdate()) {
            return ['allowed' => true, 'is_public' => false, 'reason' => 'own_profile'];
        }

        $allowed = $this->routePermissionService->canAccessLegacy($idGroupe, $context->get, $context->post, $context->method);
        return ['allowed' => $allowed, 'is_public' => false, 'reason' => $allowed ? 'allowed' : 'denied'];
    }

    /**
     * Produit la réponse de refus (AJAX 403 JSON ou redirection access_denied),
     * identique au comportement legacy.
     */
    public function deniedResponse(RequestContext $context, ?array $session = null): Response
    {
        $session = $session ?? $_SESSION;
        if ($context->isAjax) {
            return ResponseFactory::json(
                ['success' => false, 'message' => 'Accès refusé. Permissions insuffisantes.'],
                403
            );
        }

        $session['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
        $session['error_type'] = 'permission_denied';
        $_SESSION = $session;

        return ResponseFactory::redirect('layout.php?page=access_denied');
    }
}
