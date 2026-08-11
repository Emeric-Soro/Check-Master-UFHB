<?php

declare(strict_types=1);

namespace CheckMaster\Security;

/**
 * Contexte normalisé d'une requête HTTP legacy (page/action).
 *
 * Centralise les lectures de $_GET/$_POST/$_SERVER utilisées par les guards
 * et le layout, avec les mêmes règles que le code historique.
 */
final class RequestContext
{
    public string $method;
    public string $page;
    public string $action;
    public bool $isAjax;
    public bool $isJson;
    public array $get;
    public array $post;
    public array $server;

    public function __construct(?array $get = null, ?array $post = null, ?array $server = null)
    {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        $this->page = (string) ($this->get['page'] ?? '');
        $this->action = (string) ($this->get['action'] ?? '');
        $this->isAjax = !empty($this->server['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $this->isJson = str_contains((string) ($this->server['CONTENT_TYPE'] ?? ''), 'application/json');
    }

    public static function fromGlobals(): self
    {
        return new self();
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Récupère le jeton CSRF depuis POST ou depuis le corps JSON décodé
     * (stocké dans $GLOBALS['decoded_json_input'] par le layout).
     */
    public function csrfToken(): ?string
    {
        $token = $this->post['csrf_token'] ?? null;
        if (($token === null || $token === '') && $this->isJson) {
            $json = $GLOBALS['decoded_json_input'] ?? null;
            if (is_array($json)) {
                $token = $json['csrf_token'] ?? null;
            }
        }
        return is_string($token) ? $token : null;
    }

    /**
     * Détermine si le POST courant est un "update de profil" (exempté du
     * contrôle de permission legacy, comportement historique préservé).
     */
    public function isOwnProfileUpdate(): bool
    {
        return $this->page === 'profil'
            && $this->isPost()
            && (
                isset($this->post['update_password'])
                || isset($this->post['update_email'])
                || $this->post['action'] === 'update_password'
                || $this->post['action'] === 'update_email'
            );
    }

    /**
     * URL de repli pour les redirections CSRF (comportement historique).
     */
    public function fallbackUrl(): string
    {
        return 'layout.php?page=' . urlencode($this->page !== '' ? $this->page : 'dashboard');
    }

    /**
     * URL de redirection après échec CSRF : referer même hôte, sinon repli.
     */
    public function csrfRedirectUrl(): string
    {
        $referer = (string) ($this->server['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $currentHost = (string) ($this->server['HTTP_HOST'] ?? '');
            if ($refererHost !== false && $refererHost === $currentHost) {
                return $referer;
            }
        }
        return $this->fallbackUrl();
    }
}
