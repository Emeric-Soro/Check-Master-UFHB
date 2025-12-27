<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Attributes\RequirePermission;
use App\Services\PermissionService;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Middleware de vérification des permissions
 * 
 * Ce middleware analyse les attributs RequirePermission sur les méthodes
 * de contrôleur et vérifie que l'utilisateur a les permissions nécessaires.
 */
class PermissionMiddleware {
    /**
     * @param PermissionService $permissionService Service de gestion des permissions
     * @param LoggerInterface $logger Logger pour l'audit des accès refusés
     */
    public function __construct(
        private PermissionService $permissionService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Vérifie les permissions requises pour une méthode de contrôleur
     * 
     * @param object $controller Instance du contrôleur
     * @param string $method Nom de la méthode à vérifier
     * @return bool True si toutes les permissions sont accordées
     */
    public function check(object $controller, string $method): bool {
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(RequirePermission::class);

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (!$this->permissionService->has($instance->resource, $instance->action)) {
                $this->logger->warning('Access denied', [
                    'controller' => $controller::class,
                    'method' => $method,
                    'resource' => $instance->resource,
                    'action' => $instance->action->name,
                    'user_id' => $this->permissionService->getCurrentUserId(),
                    'group_id' => $this->permissionService->getCurrentGroupId(),
                ]);
                return false;
            }
        }
        return true;
    }

    /**
     * Exécute une méthode si les permissions sont accordées
     * 
     * @param object $controller Instance du contrôleur
     * @param string $method Nom de la méthode
     * @param array<mixed> $args Arguments à passer à la méthode
     * @return mixed Résultat de la méthode ou null si accès refusé
     */
    public function executeIfAllowed(object $controller, string $method, array $args = []): mixed {
        if (!$this->check($controller, $method)) {
            return null;
        }
        
        return $controller->$method(...$args);
    }

    /**
     * Récupère la liste des permissions requises pour une méthode
     * 
     * @param object|string $controller Instance ou nom de classe du contrôleur
     * @param string $method Nom de la méthode
     * @return array<array{resource: string, action: string}> Liste des permissions requises
     */
    public function getRequiredPermissions(object|string $controller, string $method): array {
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(RequirePermission::class);
        
        $permissions = [];
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $permissions[] = [
                'resource' => $instance->resource,
                'action' => $instance->action->name,
            ];
        }
        
        return $permissions;
    }
}
