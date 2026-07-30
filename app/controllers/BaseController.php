<?php

declare(strict_types=1);

namespace CheckMaster\Controllers;

use PDO;
use CheckMaster\Core\Messages;
use CheckMaster\Core\AppConfig;

/**
 * Classe abstraite pour tous les controllers.
 * Fournit : injection de dépendances, rendu de vues, redirections,
 * flash messages, réponses JSON — tout le boilerplate commun.
 */
abstract class BaseController
{
    protected PDO $pdo;

    /** Chemin de base des vues pour ce controller */
    protected string $viewPath;

    /** Chemin de base des composants */
    protected string $componentPath;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->viewPath = AppConfig::viewPath() . DIRECTORY_SEPARATOR;
        $this->componentPath = AppConfig::componentPath() . DIRECTORY_SEPARATOR;
    }

    // ─── Rendu de vues ────────────────────────────────────

    /**
     * Rendre un template de vue avec des données.
     * Extrait les données dans la portée locale du template.
     */
    protected function render(string $viewFile, array $data = []): string
    {
        $fullPath = $this->resolveViewPath($viewFile);
        if (!is_file($fullPath)) {
            throw new \RuntimeException("Vue introuvable : $viewFile ($fullPath)");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $fullPath;
        return ob_get_clean();
    }

    /**
     * Inclure un composant réutilisable.
     */
    protected function component(string $componentFile, array $data = []): string
    {
        $fullPath = $this->componentPath . $componentFile;
        if (!is_file($fullPath)) {
            throw new \RuntimeException("Composant introuvable : $componentFile ($fullPath)");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $fullPath;
        return ob_get_clean();
    }

    /**
     * Résoudre le chemin complet d'une vue.
     */
    protected function resolveViewPath(string $viewFile): string
    {
        // Si le chemin est absolu, le retourner tel quel
        if ($viewFile[0] === '/' || strpos($viewFile, ':') === 1) {
            return $viewFile;
        }
        return $this->viewPath . $viewFile;
    }

    // ─── Redirections ─────────────────────────────────────

    /**
     * Rediriger vers une URL interne.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Rediriger vers une page avec un message flash.
     */
    protected function redirectWith(string $page, string $flashKey, string $message, string $type = 'success'): void
    {
        $_SESSION[$flashKey] = $message;
        $_SESSION['flash_type'] = $type;
        $this->redirect('layout.php?page=' . urlencode($page));
    }

    /**
     * Rediriger vers la page précédente ou une page par défaut.
     */
    protected function redirectBack(string $defaultPage = 'dashboard'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $host) {
            $this->redirect($referer);
        }

        $this->redirect('layout.php?page=' . urlencode($defaultPage));
    }

    // ─── Flash Messages ───────────────────────────────────

    /**
     * Définir un message flash.
     */
    protected function flash(string $key, string $message, string $type = 'success'): void
    {
        $_SESSION[$key] = $message;
        $_SESSION['flash_type'] = $type;
    }

    /**
     * Récupérer et consommer un message flash.
     */
    protected function consumeFlash(string $key): ?string
    {
        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $message;
    }

    /**
     * Récupérer un message depuis le catalogue Messages.
     */
    protected function msg(string $key, array $params = []): string
    {
        return Messages::get($key, $params);
    }

    // ─── Réponses JSON ────────────────────────────────────

    /**
     * Répondre avec un JSON.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Répondre avec un JSON de succès.
     */
    protected function jsonSuccess(mixed $data = null, string $message = ''): void
    {
        $response = ['success' => true];
        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message !== '') {
            $response['message'] = $message;
        }
        $this->json($response);
    }

    /**
     * Répondre avec un JSON d'erreur.
     */
    protected function jsonError(string $message, int $status = 400, mixed $errors = null): void
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        $this->json($response, $status);
    }

    // ─── Requête HTTP ─────────────────────────────────────

    /**
     * Vérifier si la requête est POST.
     */
    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Vérifier si la requête est AJAX.
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Récupérer un paramètre POST nettoyé.
     */
    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Récupérer un paramètre GET nettoyé.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Récupérer un paramètre POST comme string nettoyé.
     */
    protected function postString(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }

    /**
     * Récupérer un paramètre POST comme int.
     */
    protected function postInt(string $key, int $default = 0): int
    {
        return (int) ($_POST[$key] ?? $default);
    }

    /**
     * Récupérer un paramètre POST comme float.
     */
    protected function postFloat(string $key, float $default = 0.0): float
    {
        return (float) ($_POST[$key] ?? $default);
    }

    /**
     * Récupérer un paramètre POST comme bool.
     */
    protected function postBool(string $key, bool $default = false): bool
    {
        $value = $_POST[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    // ─── Session ──────────────────────────────────────────

    /**
     * Récupérer une valeur de session.
     */
    protected function session(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Définir une valeur de session.
     */
    protected function setSession(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    // ─── Utilitaires ──────────────────────────────────────

    /**
     * Récupérer le chemin racine du projet.
     */
    protected function projectRoot(): string
    {
        return AppConfig::projectRoot();
    }

    /**
     * Récupérer le chemin de stockage des documents.
     */
    protected function storagePath(): string
    {
        return AppConfig::storageDocumentsPath();
    }

    /**
     * Récupérer le chemin des uploads.
     */
    protected function uploadPath(): string
    {
        return AppConfig::uploadPath();
    }

    /**
     * Récupérer le chemin du logo.
     */
    protected function logoPath(): string
    {
        return AppConfig::logoPath();
    }

    /**
     * Valider le token CSRF depuis le formulaire.
     */
    protected function validateCsrf(): bool
    {
        return \CheckMaster\Core\Csrf::validate($_POST['csrf_token'] ?? null);
    }

    /**
     * Vérifier la permission de l'utilisateur courant.
     */
    protected function can(string $action, ?string $feature = null): bool
    {
        if (!function_exists('cm_can_' . $action)) {
            return false;
        }
        $func = 'cm_can_' . $action;
        return $feature ? $func($feature) : $func();
    }
}
