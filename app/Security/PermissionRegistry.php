<?php

declare(strict_types=1);

namespace CheckMaster\Security;

final class PermissionRegistry
{
    /** @var array<string,mixed>|null */
    private static ?array $registry = null;

    /**
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        if (self::$registry !== null) {
            return self::$registry;
        }

        $path = __DIR__ . '/../config/permission_registry.php';
        $data = require $path;
        if (!is_array($data)) {
            throw new \RuntimeException('permission_registry.php doit retourner un tableau.');
        }

        self::$registry = $data;
        return self::$registry;
    }

    /**
     * @return array<string,mixed>
     */
    public static function feature(string $slug): array
    {
        $features = self::features();
        return $features[$slug] ?? [];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function features(): array
    {
        $registry = self::all();
        $features = $registry['features'] ?? [];
        return is_array($features) ? $features : [];
    }

    /**
     * @return array<string,int>
     */
    public static function groups(): array
    {
        $registry = self::all();
        $groups = $registry['groups'] ?? [];
        return is_array($groups) ? $groups : [];
    }

    /**
     * @return array<string,string>
     */
    public static function slugAliases(): array
    {
        $registry = self::all();
        $aliases = $registry['slug_aliases'] ?? [];
        return is_array($aliases) ? $aliases : [];
    }

    /**
     * @return array<string,array<int,array<string,bool>>>
     */
    public static function categoryDefaults(): array
    {
        $registry = self::all();
        $defaults = $registry['category_defaults'] ?? [];
        return is_array($defaults) ? $defaults : [];
    }

    /**
     * @return array<int,array<string,string>>
     */
    public static function publicRoutes(): array
    {
        $registry = self::all();
        $routes = $registry['public_routes'] ?? [];
        return is_array($routes) ? $routes : [];
    }

    public static function canonicalSlug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        $aliases = self::slugAliases();
        $seen = [];
        $current = $slug;

        while (isset($aliases[$current]) && !isset($seen[$current])) {
            $seen[$current] = true;
            $current = (string) $aliases[$current];
        }

        return $current;
    }

    /**
     * @return array<string,mixed>
     */
    public static function findFeatureByIdentifier(string $identifier): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [];
        }

        $canonical = self::canonicalSlug($identifier);
        $feature = self::feature($canonical);
        if ($feature !== []) {
            return $feature;
        }

        foreach (self::features() as $candidate) {
            $menuUrl = (string) ($candidate['menu_url'] ?? '');
            if ($menuUrl === '?page=' . $identifier || $menuUrl === $identifier) {
                return $candidate;
            }

            foreach (($candidate['existing_codes'] ?? []) as $code) {
                if ((string) $code === $identifier) {
                    return $candidate;
                }
            }

            foreach (($candidate['routes'] ?? []) as $route) {
                $pattern = (string) ($route['pattern'] ?? '');
                if ($pattern === $identifier || $pattern === 'page=' . $identifier || str_starts_with($pattern, 'page=' . $identifier . '&')) {
                    return $candidate;
                }
            }
        }

        return [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allRoutes(): array
    {
        $routes = [];
        foreach (self::features() as $feature) {
            $slug = (string) ($feature['slug'] ?? '');
            foreach (($feature['routes'] ?? []) as $route) {
                if (!is_array($route)) {
                    continue;
                }
                $route['slug'] = $slug;
                $routes[] = $route;
            }
        }

        return $routes;
    }

    public static function isPublicRoute(string $pattern, string $method): bool
    {
        $method = strtoupper($method);
        foreach (self::publicRoutes() as $route) {
            if (($route['pattern'] ?? '') === $pattern && strtoupper((string) ($route['method'] ?? 'GET')) === $method) {
                return true;
            }
        }

        return false;
    }
}
