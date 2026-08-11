<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use CheckMaster\Core\Response;
use CheckMaster\Core\ResponseFactory;

/**
 * Garde d'authentification : vérifie que la session contient un utilisateur
 * connecté, sinon redirige vers la page de connexion (comportement legacy).
 */
final class AuthenticationGuard
{
    public function isAuthenticated(?array $session = null): bool
    {
        $session = $session ?? $_SESSION;
        return !empty($session['id_utilisateur']);
    }

    /**
     * @param array<string,mixed>|null $session
     * @param array<string,mixed>|null $server
     */
    public function requireAuthenticated(?array $session = null, ?array $server = null): ?Response
    {
        if ($this->isAuthenticated($session)) {
            return null;
        }
        return ResponseFactory::redirect('page_connexion.php');
    }
}
