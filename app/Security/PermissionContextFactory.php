<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use PDO;

final class PermissionContextFactory
{
    private AuthorizationService $authorizationService;

    public function __construct(PDO $pdo)
    {
        $this->authorizationService = new AuthorizationService($pdo);
    }

    /**
     * @return array{view:bool,create:bool,edit:bool,delete:bool,slug:string}
     */
    public function forFeature(int $groupId, ?string $identifier = null): array
    {
        $slug = $identifier !== null ? PermissionRegistry::canonicalSlug($identifier) : '';

        return [
            'view' => $this->authorizationService->checkFeaturePermission($groupId, $identifier, 'voir'),
            'create' => $this->authorizationService->checkFeaturePermission($groupId, $identifier, 'creer'),
            'edit' => $this->authorizationService->checkFeaturePermission($groupId, $identifier, 'modifier'),
            'delete' => $this->authorizationService->checkFeaturePermission($groupId, $identifier, 'supprimer'),
            'slug' => $slug,
        ];
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{view:bool,create:bool,edit:bool,delete:bool,slug:string}
     */
    public function forCurrentRequest(int $groupId, array $get, array $post, string $method): array
    {
        $resolved = $this->authorizationService->resolveLegacyRequest($get, $post, $method);
        $slug = (string) ($resolved['slug_permission'] ?? '');

        return [
            'view' => $slug !== '' ? $this->authorizationService->checkFeaturePermission($groupId, $slug, 'voir') : false,
            'create' => $slug !== '' ? $this->authorizationService->checkFeaturePermission($groupId, $slug, 'creer') : false,
            'edit' => $slug !== '' ? $this->authorizationService->checkFeaturePermission($groupId, $slug, 'modifier') : false,
            'delete' => $slug !== '' ? $this->authorizationService->checkFeaturePermission($groupId, $slug, 'supprimer') : false,
            'slug' => $slug,
        ];
    }
}
