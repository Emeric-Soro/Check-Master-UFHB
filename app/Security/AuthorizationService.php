<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use PDO;

final class AuthorizationService
{
    private PDO $pdo;

    /** @var array<string,array<string,mixed>> */
    private static array $resolveCache = [];

    /** @var array<string,object|null> */
    private static array $featureCache = [];

    /** @var array<string,object|null> */
    private static array $permissionCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   pattern:string,
     *   action:string,
     *   reason:string,
     *   is_public:bool,
     *   id_fonctionnalite:int|null,
     *   slug_permission:string,
     *   fonctionnalite:object|null
     * }
     */
    public function resolveLegacyRequest(array $get, array $post, string $method): array
    {
        $cacheKey = md5(json_encode([$get, $post, strtoupper($method)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if (isset(self::$resolveCache[$cacheKey])) {
            return self::$resolveCache[$cacheKey];
        }

        $resolved = RouteActionResolver::resolve($this->pdo, $get, $post, $method);

        $feature = null;
        $featureId = isset($resolved['id_fonctionnalite']) ? (int) $resolved['id_fonctionnalite'] : null;
        $slug = isset($resolved['slug_permission']) ? (string) $resolved['slug_permission'] : '';

        if ($featureId !== null && $featureId > 0) {
            $feature = $this->findFeatureById($featureId);
        }

        if ($feature === null && $slug !== '') {
            $feature = $this->findFeature($slug);
        }

        if ($feature === null) {
            $page = isset($get['page']) && is_string($get['page']) ? (string) $get['page'] : '';
            if ($page !== '') {
                $feature = $this->findFeature($page);
            }
        }

        if ($feature !== null && isset($feature->id_fonctionnalite)) {
            $featureId = (int) $feature->id_fonctionnalite;
        }

        if ($feature !== null && isset($feature->slug_permission) && is_string($feature->slug_permission) && $feature->slug_permission !== '') {
            $slug = (string) $feature->slug_permission;
        }

        $result = [
            'pattern' => (string) ($resolved['pattern'] ?? ''),
            'action' => (string) ($resolved['action'] ?? 'voir'),
            'reason' => (string) ($resolved['reason'] ?? 'unresolved'),
            'is_public' => (bool) ($resolved['is_public'] ?? false),
            'id_fonctionnalite' => $featureId !== null && $featureId > 0 ? $featureId : null,
            'slug_permission' => $slug,
            'fonctionnalite' => $feature,
        ];

        self::$resolveCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     */
    public function canAccessLegacyRequest(int $groupId, array $get, array $post, string $method): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $resolved = $this->resolveLegacyRequest($get, $post, $method);
        if ($resolved['is_public'] === true) {
            return true;
        }

        $featureId = $resolved['id_fonctionnalite'];
        if (!is_int($featureId) || $featureId <= 0) {
            return false;
        }

        return $this->checkPermissionByFeatureId($groupId, $featureId, (string) $resolved['action']);
    }

    public function canAccessLegacyPage(int $groupId, string $pageSlug, string $action): bool
    {
        return $this->checkFeaturePermission($groupId, $pageSlug, $action);
    }

    public function checkFeaturePermission(int $groupId, ?string $identifier, string $action = 'voir'): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $feature = null;

        if ($identifier !== null && trim($identifier) !== '') {
            $feature = $this->findFeature($identifier);
        } else {
            $resolved = $this->resolveLegacyRequest($_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');
            $feature = $resolved['fonctionnalite'];

            if ($feature === null) {
                $page = isset($_GET['page']) && is_string($_GET['page']) ? (string) $_GET['page'] : '';
                if ($page !== '') {
                    $feature = $this->findFeature($page);
                }
            }
        }

        if ($feature === null || !isset($feature->id_fonctionnalite)) {
            return false;
        }

        return $this->checkPermissionByFeatureId($groupId, (int) $feature->id_fonctionnalite, $action);
    }

    private function findFeature(string $identifier): ?object
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $canonical = PermissionRegistry::canonicalSlug($identifier);
        $cacheKey = strtolower($canonical);
        if (array_key_exists($cacheKey, self::$featureCache)) {
            return self::$featureCache[$cacheKey];
        }

        require_once __DIR__ . '/../models/Fonctionnalite.php';
        $model = new \Fonctionnalite($this->pdo);

        $feature = $model->getFonctionnaliteByIdentifier($canonical);
        if (!$feature && $canonical !== $identifier) {
            $feature = $model->getFonctionnaliteByIdentifier($identifier);
        }

        self::$featureCache[$cacheKey] = $feature ?: null;
        return self::$featureCache[$cacheKey];
    }

    private function findFeatureById(int $featureId): ?object
    {
        if ($featureId <= 0) {
            return null;
        }

        $cacheKey = 'id:' . $featureId;
        if (array_key_exists($cacheKey, self::$featureCache)) {
            return self::$featureCache[$cacheKey];
        }

        require_once __DIR__ . '/../models/Fonctionnalite.php';
        $model = new \Fonctionnalite($this->pdo);
        $feature = $model->getFonctionnaliteById($featureId);
        self::$featureCache[$cacheKey] = $feature ?: null;
        return self::$featureCache[$cacheKey];
    }

    private function checkPermissionByFeatureId(int $groupId, int $featureId, string $action): bool
    {
        $action = in_array($action, ['voir', 'creer', 'modifier', 'supprimer'], true) ? $action : 'voir';
        $cacheKey = $groupId . ':' . $featureId;

        if (!array_key_exists($cacheKey, self::$permissionCache)) {
            require_once __DIR__ . '/../models/Permission.php';
            $permissionModel = new \Permission($this->pdo);
            self::$permissionCache[$cacheKey] = $permissionModel->getPermissions($groupId, $featureId) ?: null;
        }

        $permission = self::$permissionCache[$cacheKey];
        if ($permission === null) {
            return false;
        }

        switch ($action) {
            case 'creer':
                return (bool) ($permission->peut_creer ?? false);
            case 'modifier':
                return (bool) ($permission->peut_modifier ?? false);
            case 'supprimer':
                return (bool) ($permission->peut_supprimer ?? false);
            case 'voir':
            default:
                return (bool) ($permission->peut_voir ?? false);
        }
    }
}
