<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use CheckMaster\Core\Csrf;
use CheckMaster\Core\Messages;
use CheckMaster\Core\Response;
use CheckMaster\Core\ResponseFactory;

/**
 * Garde CSRF globale pour toutes les requêtes POST du legacy.
 *
 * Comportement identique au code historique :
 *  - lecture du jeton depuis POST ou corps JSON ;
 *  - AJAX → 403 JSON ;
 *  - HTML → redirection (referer même hôte sinon repli) + message session.
 */
final class CsrfGuard
{
    /**
     * Vérifie le jeton CSRF d'une requête POST.
     *
     * @return Response|null null si la requête est acceptée, sinon la réponse d'échec.
     */
    public function check(RequestContext $context, ?array $session = null): ?Response
    {
        if (!$context->isPost()) {
            return null;
        }

        $token = $context->csrfToken();
        if (Csrf::validate($token)) {
            return null;
        }

        $session = $session ?? $_SESSION;
        $session['error'] = Messages::get('auth.session_expired');
        $session['error_type'] = 'csrf';
        $_SESSION = $session;

        if ($context->isAjax) {
            return ResponseFactory::json(
                ['success' => false, 'message' => Messages::get('auth.session_expired')],
                403
            );
        }

        return ResponseFactory::redirect($context->csrfRedirectUrl());
    }
}
