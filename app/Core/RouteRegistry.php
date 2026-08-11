<?php

declare(strict_types=1);

namespace CheckMaster\Core;

/**
 * Registre centralisé des routes legacy (page/action) de l'application.
 *
 * Remplace progressivement le chargement dispersé des fichiers de routes :
 * chaque route est déclarée avec un nom unique, la page, l'action autorisée,
 * la méthode HTTP et le fichier de routes (ou contrôleur) associé.
 *
 * Les anciennes URLs (?page=...&action=...) restent compatibles via resolve().
 */
final class RouteRegistry
{
    /**
     * @var array<string, array{
     *   name:string, page:string, action:string, method:string,
     *   file?:string, controller?:string, method_handler?:string, permission?:string
     * }>
     */
    private array $routes = [];

    /**
     * Enregistre une route legacy.
     *
     * @param array{name:string,page:string,action?:string,method?:string,file?:string,controller?:string,method_handler?:string,permission?:string} $route
     */
    public function register(array $route): self
    {
        $name = $route['name'];
        $page = $route['page'];
        $action = $route['action'] ?? '';
        $method = strtoupper($route['method'] ?? 'GET');

        // Clé unique : page + action (action vide = toute action)
        $key = $page . ($action !== '' ? ':' . $action : '');

        $this->routes[$key] = [
            'name' => $name,
            'page' => $page,
            'action' => $action,
            'method' => $method,
            'file' => $route['file'] ?? null,
            'controller' => $route['controller'] ?? null,
            'method_handler' => $route['method_handler'] ?? null,
            'permission' => $route['permission'] ?? null,
        ];

        return $this;
    }

    /**
     * Résout une requête legacy (page + action) vers une route enregistrée.
     *
     * @return array{name:string,page:string,action:string,method:string,file:?string,controller:?string,method_handler:?string,permission:?string}|null
     */
    public function resolve(string $page, string $action = '', string $method = 'GET'): ?array
    {
        $method = strtoupper($method);

        // 1. Correspondance exacte page:action
        $exact = $this->routes[$page . ':' . $action] ?? null;
        if ($exact !== null && $this->methodMatches($exact['method'], $method)) {
            return $exact;
        }

        // 2. Correspondance page seule (action quelconque)
        $pageOnly = $this->routes[$page] ?? null;
        if ($pageOnly !== null && $this->methodMatches($pageOnly['method'], $method)) {
            return $pageOnly;
        }

        // 3. Correspondance page:action avec méthode différente (on retourne quand même)
        if ($exact !== null) {
            return $exact;
        }
        if ($pageOnly !== null) {
            return $pageOnly;
        }

        return null;
    }

    /**
     * Vérifie si une page/action est déclarée (sans tenir compte de la méthode).
     */
    public function has(string $page, string $action = ''): bool
    {
        return isset($this->routes[$page . ':' . $action]) || isset($this->routes[$page]);
    }

    /**
     * Liste toutes les routes enregistrées (pour inventaire/tests).
     *
     * @return array<int,array{name:string,page:string,action:string,method:string}>
     */
    public function all(): array
    {
        $out = [];
        foreach ($this->routes as $key => $r) {
            $out[] = [
                'name' => $r['name'],
                'page' => $r['page'],
                'action' => $r['action'],
                'method' => $r['method'],
            ];
        }
        return $out;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Charge les fichiers de routes legacy par domaine (comportement historique).
     *
     * @param string $routesDir Répertoire contenant les fichiers de routes.
     * @param list<string> $files Liste des fichiers à charger (chemin relatif).
     */
    public function loadLegacyFiles(string $routesDir, array $files): void
    {
        foreach ($files as $file) {
            $path = rtrim($routesDir, '/\\') . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                require_once $path;
            }
        }
    }

    private function methodMatches(string $allowed, string $method): bool
    {
        return $allowed === 'ANY' || $allowed === $method;
    }
}
