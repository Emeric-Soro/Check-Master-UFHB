<?php

/**
 * Classe de protection contre les attaques CSRF (Cross-Site Request Forgery)
 * 
 * Cette classe gère la génération et la validation de jetons CSRF pour protéger
 * les formulaires de l'application contre les attaques CSRF.
 */
class CSRFProtection
{
    /**
     * Nom de la clé de session pour stocker le jeton CSRF
     */
    private const TOKEN_NAME = 'csrf_token';

    /**
     * Génère un nouveau jeton CSRF et le stocke dans la session
     * 
     * @return string Le jeton CSRF généré
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::TOKEN_NAME] = $token;

        return $token;
    }

    /**
     * Récupère le jeton CSRF actuel de la session
     * Si aucun jeton n'existe, en génère un nouveau
     * 
     * @return string Le jeton CSRF
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return self::generateToken();
        }

        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Valide un jeton CSRF fourni par rapport au jeton stocké en session
     * 
     * @param string|null $token Le jeton à valider
     * @return bool True si le jeton est valide, False sinon
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::TOKEN_NAME]) || empty($token)) {
            return false;
        }

        // Utilise hash_equals pour éviter les attaques par timing
        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }

    /**
     * Génère un champ input HTML caché contenant le jeton CSRF
     * 
     * @return string Code HTML du champ input
     */
    public static function getTokenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Vérifie le jeton CSRF depuis une requête POST
     * Lance une exception si le jeton est invalide
     * 
     * @throws Exception Si le jeton CSRF est invalide ou manquant
     */
    public static function verifyRequest(): void
    {
        $token = $_POST['csrf_token'] ?? null;

        if (!self::validateToken($token)) {
            http_response_code(403);
            die('Erreur CSRF : Jeton de sécurité invalide ou manquant. Veuillez réessayer.');
        }
    }

    /**
     * Régénère le jeton CSRF
     * Utile après une connexion réussie pour éviter la fixation de session
     * 
     * @return string Le nouveau jeton CSRF
     */
    public static function regenerateToken(): string
    {
        return self::generateToken();
    }
}
