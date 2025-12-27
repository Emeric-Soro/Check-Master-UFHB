<?php
declare(strict_types=1);

/**
 * Configuration de Twig pour CheckMaster
 * 
 * Ce fichier configure l'environnement Twig avec:
 * - Protection XSS automatique (autoescape)
 * - Cache pour les performances
 * - Fonctions personnalisées pour les permissions
 */

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use App\Services\PermissionService;
use App\Enums\Action;
use Psr\Container\ContainerInterface;

/**
 * Crée et configure l'environnement Twig
 * 
 * @param ContainerInterface $container Le conteneur PHP-DI
 * @return Environment L'environnement Twig configuré
 */
function createTwigEnvironment(ContainerInterface $container): Environment {
    $loader = new FilesystemLoader(__DIR__ . '/../templates');
    
    $twig = new Environment($loader, [
        'cache' => __DIR__ . '/../cache/twig',
        'auto_reload' => true,
        'autoescape' => 'html', // Protection XSS automatique
        'strict_variables' => false,
    ]);

    // Fonction can() - Vérifie si l'utilisateur a une permission
    $twig->addFunction(new TwigFunction('can', function (string $resource, string $action) use ($container): bool {
        try {
            $permService = $container->get(PermissionService::class);
            $actionEnum = Action::from($action);
            return $permService->has($resource, $actionEnum);
        } catch (\ValueError $e) {
            // Action invalide
            return false;
        }
    }));

    // Fonction csrf_field() - Génère un champ CSRF
    $twig->addFunction(new TwigFunction('csrf_field', function (): string {
        if (class_exists('\ParagonIE\AntiCSRF\AntiCSRF')) {
            $csrf = new \ParagonIE\AntiCSRF\AntiCSRF();
            return $csrf->insertToken('', false);
        }
        // Fallback si AntiCSRF n'est pas disponible
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }, ['is_safe' => ['html']]));

    // Fonction path() - Génère une URL
    $twig->addFunction(new TwigFunction('path', function (string $route, array $params = []): string {
        $query = http_build_query($params);
        $base = '?page=' . urlencode($route);
        return $query ? "{$base}&{$query}" : $base;
    }));

    // Fonction actions() - Retourne tous les types d'actions disponibles
    $twig->addFunction(new TwigFunction('actions', function (): array {
        return Action::cases();
    }));

    // Fonction action_label() - Retourne le libellé d'une action
    $twig->addFunction(new TwigFunction('action_label', function (string $actionValue): string {
        try {
            $action = Action::from($actionValue);
            return $action->label();
        } catch (\ValueError $e) {
            return $actionValue;
        }
    }));

    // Fonction action_initial() - Retourne l'initiale d'une action
    $twig->addFunction(new TwigFunction('action_initial', function (string $actionValue): string {
        try {
            $action = Action::from($actionValue);
            return $action->initial();
        } catch (\ValueError $e) {
            return substr($actionValue, 0, 1);
        }
    }));

    // Fonction action_icon() - Retourne l'icône d'une action
    $twig->addFunction(new TwigFunction('action_icon', function (string $actionValue): string {
        try {
            $action = Action::from($actionValue);
            return $action->icon();
        } catch (\ValueError $e) {
            return 'fa-question';
        }
    }));

    // Fonction is_admin() - Vérifie si l'utilisateur est administrateur
    $twig->addFunction(new TwigFunction('is_admin', function () use ($container): bool {
        $permService = $container->get(PermissionService::class);
        return $permService->getCurrentGroupId() === 5;
    }));

    // Fonction current_user_id() - Retourne l'ID de l'utilisateur courant
    $twig->addFunction(new TwigFunction('current_user_id', function () use ($container): ?int {
        $permService = $container->get(PermissionService::class);
        return $permService->getCurrentUserId();
    }));

    return $twig;
}

// Retourner la fonction de création pour permettre l'injection du container
return 'createTwigEnvironment';
