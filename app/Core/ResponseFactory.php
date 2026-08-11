<?php

declare(strict_types=1);

namespace CheckMaster\Core;

/**
 * Fabrique centralisée des réponses HTTP.
 *
 * Remplace les constructions répétées de http_response_code(), header() et
 * json_encode() dispersées dans les contrôleurs et le layout legacy.
 *
 * Chaque méthode retourne une Response prête à être envoyée (->send()).
 */
final class ResponseFactory
{
    // ─── Réponses simples ───────────────────────────────────

    public static function html(string $body, int $status = 200, array $extraHeaders = []): Response
    {
        return new Response($body, $status, array_merge(
            ['Content-Type' => 'text/html; charset=UTF-8'],
            $extraHeaders
        ));
    }

    public static function json(array $data, int $status = 200, array $extraHeaders = []): Response
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new Response($body !== false ? $body : '{}', $status, array_merge(
            ['Content-Type' => 'application/json; charset=UTF-8'],
            $extraHeaders
        ));
    }

    public static function redirect(string $location, int $status = 302): Response
    {
        return Response::redirect($location, $status);
    }

    public static function text(string $body, int $status = 200): Response
    {
        return new Response($body, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    // ─── Réponses AJAX normalisées ──────────────────────────

    /**
     * Succès AJAX : { success: true, message?, redirect?, ...$extra }
     */
    public static function ajaxSuccess(string $message = '', array $extra = []): Response
    {
        return self::json(array_merge(['success' => true, 'message' => $message], $extra));
    }

    /**
     * Échec AJAX : { success: false, message }
     */
    public static function ajaxError(string $message, int $status = 422, array $extra = []): Response
    {
        return self::json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    // ─── Réponses d'erreur standard ─────────────────────────

    public static function badRequest(string $message = 'Requête invalide.'): Response
    {
        return self::html($message, 400);
    }

    public static function unauthorized(string $message = 'Authentification requise.'): Response
    {
        return self::html($message, 401);
    }

    public static function forbidden(string $message = 'Accès refusé.'): Response
    {
        return self::html($message, 403);
    }

    public static function notFound(string $message = 'Ressource introuvable.'): Response
    {
        return self::html($message, 404);
    }

    public static function unprocessable(string $message = 'Données invalides.'): Response
    {
        return self::html($message, 422);
    }

    public static function tooManyRequests(string $message = 'Trop de requêtes. Veuillez réessayer plus tard.'): Response
    {
        return self::html($message, 429);
    }

    public static function serverError(string $message = 'Erreur interne du serveur.'): Response
    {
        return self::html($message, 500);
    }

    // ─── Envoi d'un fichier (téléchargement / diffusion) ────

    /**
     * Diffuse un fichier avec les bons en-têtes.
     *
     * @param string $path     Chemin absolu du fichier.
     * @param string $mime     Type MIME (ex: application/pdf).
     * @param string $filename Nom de fichier proposé au téléchargement (null = inline).
     */
    public static function file(string $path, string $mime, ?string $filename = null, bool $download = false): Response
    {
        if (!is_file($path) || !is_readable($path)) {
            return self::notFound();
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($path),
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($download || $filename !== null) {
            $disposition = ($download ? 'attachment' : 'inline')
                . '; filename="' . str_replace(['"', "\r", "\n"], '', (string) $filename) . '"';
            $headers['Content-Disposition'] = $disposition;
        }

        return new Response((string) file_get_contents($path), 200, $headers);
    }

    /**
     * Détermine si la requête courante est une requête AJAX.
     */
    public static function isAjax(?array $server = null): bool
    {
        $server = $server ?? $_SERVER;
        return !empty($server['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
