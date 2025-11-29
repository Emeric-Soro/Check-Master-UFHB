<?php

/**
 * Trait HasPermissions
 * Fournit des méthodes de vérification des permissions aux contrôleurs
 *
 * @author CheckMaster Team
 * @version 1. 0
 */
trait HasPermissions
{
    /** @var Permission|null Instance du model Permission */
    private $permissionModel = null;

    /**
     * Initialise le model Permission si nécessaire
     *
     * @return Permission
     */
    private function getPermissionModel(): Permission
    {
        if ($this->permissionModel === null) {
            require_once __DIR__ . '/../models/Permission. php';
            require_once __DIR__ . '/../config/database.php';

            $db = Database::getConnection();
            $this->permissionModel = new Permission($db);
        }
        return $this->permissionModel;
    }

    /**
     * Vérifie si l'utilisateur connecté a une permission
     *
     * @param string $traitement Libellé du traitement (page)
     * @param string $action Libellé de l'action
     * @param array $context Contexte optionnel pour conditions avancées
     * @return bool
     */
    protected function checkPermission(string $traitement, string $action, array $context = []): bool
    {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        return $this->getPermissionModel()->hasPermission(
            $_SESSION['id_GU'],
            $traitement,
            $action,
            $context
        );
    }

    /**
     * Exige une permission, redirige ou retourne erreur si non autorisé
     *
     * @param string $traitement Libellé du traitement
     * @param string $action Libellé de l'action
     * @param array $context Contexte optionnel
     * @throws Exception Si non autorisé en mode non-web
     */
    protected function requirePermission(string $traitement, string $action, array $context = []): void
    {
        if (!$this->checkPermission($traitement, $action, $context)) {
            $this->handleUnauthorized($traitement, $action);
        }
    }

    /**
     * Vérifie si l'utilisateur a accès à une page (via rattacher)
     *
     * @param string $traitement Libellé du traitement
     * @return bool
     */
    protected function checkAccess(string $traitement): bool
    {
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        return $this->getPermissionModel()->hasAccess($_SESSION['id_GU'], $traitement);
    }

    /**
     * Gère les accès non autorisés
     *
     * @param string $traitement Traitement concerné
     * @param string $action Action tentée
     */
    protected function handleUnauthorized(string $traitement, string $action): void
    {
        // Log de l'audit
        $this->logUnauthorizedAccess($traitement, $action);

        // Réponse selon le type de requête
        if ($this->isAjaxRequest()) {
            $this->sendJsonError(
                "Vous n'avez pas la permission d'effectuer cette action.",
                403,
                'PERMISSION_DENIED'
            );
        }

        // Redirection pour les requêtes normales
        $_SESSION['error'] = "Vous n'avez pas la permission d'accéder à cette fonctionnalité. ";

        // Déterminer la page de redirection
        $redirectPage = $this->getDefaultRedirectPage();
        header("Location: layout.php?page={$redirectPage}");
        exit;
    }

    /**
     * Log les tentatives d'accès non autorisées
     *
     * @param string $traitement Traitement concerné
     * @param string $action Action tentée
     */
    private function logUnauthorizedAccess(string $traitement, string $action): void
    {
        if (isset($_SESSION['id_utilisateur'])) {
            try {
                require_once __DIR__ . '/../models/AuditLog.php';
                require_once __DIR__ . '/../config/database.php';

                $db = Database::getConnection();
                $audit = new AuditLog($db);
                $audit->logAction(
                    $_SESSION['id_utilisateur'],
                    "Accès refusé: {$action} sur {$traitement}",
                    $traitement,
                    'Échec'
                );
            } catch (Exception $e) {
                error_log("Erreur log accès non autorisé: " . $e->getMessage());
            }
        }
    }

    /**
     * Vérifie si c'est une requête AJAX
     *
     * @return bool
     */
    protected function isAjaxRequest(): bool
    {
        return ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Envoie une réponse JSON d'erreur
     *
     * @param string $message Message d'erreur
     * @param int $httpCode Code HTTP
     * @param string $errorCode Code d'erreur interne
     */
    protected function sendJsonError(string $message, int $httpCode = 400, string $errorCode = 'ERROR'): void
    {
        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode
        ]);
        exit;
    }

    /**
     * Envoie une réponse JSON de succès
     *
     * @param string $message Message de succès
     * @param array $data Données additionnelles
     */
    protected function sendJsonSuccess(string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => true,
            'message' => $message
        ], $data));
        exit;
    }

    /**
     * Récupère les actions autorisées pour l'affichage UI
     *
     * @param string $traitement Libellé du traitement
     * @return array Liste des actions autorisées
     */
    protected function getAuthorizedActions(string $traitement): array
    {
        if (!isset($_SESSION['id_GU'])) {
            return [];
        }

        return $this->getPermissionModel()->getActionsAutorisees(
            $_SESSION['id_GU'],
            $traitement
        );
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action spécifique
     * Raccourci utilisant le traitement courant
     *
     * @param string $action Libellé de l'action
     * @param array $context Contexte optionnel
     * @return bool
     */
    protected function canDo(string $action, array $context = []): bool
    {
        $traitement = $this->getCurrentTraitement();
        return $this->checkPermission($traitement, $action, $context);
    }

    /**
     * Récupère le traitement courant depuis l'URL
     *
     * @return string
     */
    protected function getCurrentTraitement(): string
    {
        return $_GET['page'] ?? 'dashboard';
    }

    /**
     * Récupère la page de redirection par défaut selon le groupe
     *
     * @return string
     */
    protected function getDefaultRedirectPage(): string
    {
        // Essayer de récupérer le premier traitement accessible
        if (isset($_SESSION['id_GU'])) {
            try {
                require_once __DIR__ . '/../models/Attribution.php';
                require_once __DIR__ . '/../config/database.php';

                $db = Database::getConnection();
                $attribution = new Attribution($db);
                $traitements = $attribution->getTraitementsByGroupe($_SESSION['id_GU']);

                if (! empty($traitements)) {
                    return $traitements[0]->lib_traitement ??  'dashboard';
                }
            } catch (Exception $e) {
                error_log("Erreur getDefaultRedirectPage: " . $e->getMessage());
            }
        }

        return 'dashboard';
    }

    /**
     * Vérifie plusieurs permissions à la fois
     *
     * @param string $traitement Traitement concerné
     * @param array $actions Liste des actions à vérifier
     * @return array Actions autorisées
     */
    protected function checkMultiplePermissions(string $traitement, array $actions): array
    {
        $authorized = [];
        foreach ($actions as $action) {
            if ($this->checkPermission($traitement, $action)) {
                $authorized[] = $action;
            }
        }
        return $authorized;
    }

    /**
     * Vérifie si au moins une des permissions est accordée
     *
     * @param string $traitement Traitement concerné
     * @param array $actions Liste des actions
     * @return bool
     */
    protected function hasAnyPermission(string $traitement, array $actions): bool
    {
        foreach ($actions as $action) {
            if ($this->checkPermission($traitement, $action)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si toutes les permissions sont accordées
     *
     * @param string $traitement Traitement concerné
     * @param array $actions Liste des actions
     * @return bool
     */
    protected function hasAllPermissions(string $traitement, array $actions): bool
    {
        foreach ($actions as $action) {
            if (!$this->checkPermission($traitement, $action)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Prépare les données d'autorisation pour la vue
     *
     * @param string $traitement Traitement concerné
     */
    protected function prepareViewAuthorizations(string $traitement): void
    {
        $GLOBALS['authorizedActions'] = $this->getAuthorizedActions($traitement);
        $GLOBALS['canAdd'] = in_array('Ajouter', $GLOBALS['authorizedActions']);
        $GLOBALS['canEdit'] = in_array('Modifier', $GLOBALS['authorizedActions']);
        $GLOBALS['canDelete'] = in_array('Supprimer', $GLOBALS['authorizedActions']);
        $GLOBALS['canView'] = in_array('Consulter', $GLOBALS['authorizedActions']);
        $GLOBALS['canExport'] = in_array('Exporter', $GLOBALS['authorizedActions']);
        $GLOBALS['canPrint'] = in_array('Imprimer', $GLOBALS['authorizedActions']);
        $GLOBALS['canValidate'] = in_array('Valider', $GLOBALS['authorizedActions']);
        $GLOBALS['canReject'] = in_array('Rejeter', $GLOBALS['authorizedActions']);
    }
}