<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use PDO;

final class RoutePermissionService
{
    private AuthorizationService $authorizationService;

    public function __construct(PDO $pdo)
    {
        $this->authorizationService = new AuthorizationService($pdo);
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{fonctionnalite:object|null,action:'voir'|'creer'|'modifier'|'supprimer',pattern:string,reason:string,slug_permission:string,is_public:bool}
     */
    public function resolveLegacy(array $get, array $post, string $method): array
    {
        $resolved = $this->authorizationService->resolveLegacyRequest($get, $post, $method);
        return [
            'fonctionnalite' => $resolved['fonctionnalite'],
            'action' => $resolved['action'],
            'pattern' => $resolved['pattern'],
            'reason' => $resolved['reason'],
            'slug_permission' => $resolved['slug_permission'],
            'is_public' => $resolved['is_public'],
        ];
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     */
    public function canAccessLegacy(int $idGroupe, array $get, array $post, string $method): bool
    {
        return $this->authorizationService->canAccessLegacyRequest($idGroupe, $get, $post, $method);
    }
}
