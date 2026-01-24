<?php
/**
 * Helper functions pour vérifier les permissions dans les vues
 * Facilite l'affichage conditionnel des boutons et actions selon les droits CRUD
 */

/**
 * Vérifie si l'utilisateur peut voir la page/élément
 * @param string $codeFonctionnalite
 * @return bool
 */
function canView($codeFonctionnalite = null)
{
    if (!isset($_SESSION['id_GU'])) {
        return false;
    }

    // Admin (règle données via libellé)
    if (isAdmin()) {
        return true;
    }

    if ($codeFonctionnalite === null) {
        $codeFonctionnalite = $_GET['page'] ?? '';
        if ($codeFonctionnalite !== '' && isset($_GET['action']) && is_string($_GET['action']) && $_GET['action'] !== '') {
            $codeFonctionnalite .= '&action=' . $_GET['action'];
        }
    }

    global $permissionMiddleware;
    if (!isset($permissionMiddleware)) {
        require_once __DIR__ . '/../middlewares/PermissionMiddleware.php';
        $permissionMiddleware = new PermissionMiddleware();
    }

    return $permissionMiddleware->checkPageAccess($codeFonctionnalite, $_SESSION['id_GU'], 'voir');
}

/**
 * Vérifie si l'utilisateur peut créer des éléments
 * @param string $codeFonctionnalite
 * @return bool
 */
function canCreate($codeFonctionnalite = null)
{
    if (!isset($_SESSION['id_GU'])) {
        return false;
    }

    // Admin (règle données via libellé)
    if (isAdmin()) {
        return true;
    }

    if ($codeFonctionnalite === null) {
        $codeFonctionnalite = $_GET['page'] ?? '';
        if ($codeFonctionnalite !== '' && isset($_GET['action']) && is_string($_GET['action']) && $_GET['action'] !== '') {
            $codeFonctionnalite .= '&action=' . $_GET['action'];
        }
    }

    global $permissionMiddleware;
    if (!isset($permissionMiddleware)) {
        require_once __DIR__ . '/../middlewares/PermissionMiddleware.php';
        $permissionMiddleware = new PermissionMiddleware();
    }

    return $permissionMiddleware->checkPageAccess($codeFonctionnalite, $_SESSION['id_GU'], 'creer');
}

/**
 * Vérifie si l'utilisateur peut modifier des éléments
 * @param string $codeFonctionnalite
 * @return bool
 */
function canEdit($codeFonctionnalite = null)
{
    if (!isset($_SESSION['id_GU'])) {
        return false;
    }

    // Admin (règle données via libellé)
    if (isAdmin()) {
        return true;
    }

    if ($codeFonctionnalite === null) {
        $codeFonctionnalite = $_GET['page'] ?? '';
        if ($codeFonctionnalite !== '' && isset($_GET['action']) && is_string($_GET['action']) && $_GET['action'] !== '') {
            $codeFonctionnalite .= '&action=' . $_GET['action'];
        }
    }

    global $permissionMiddleware;
    if (!isset($permissionMiddleware)) {
        require_once __DIR__ . '/../middlewares/PermissionMiddleware.php';
        $permissionMiddleware = new PermissionMiddleware();
    }

    return $permissionMiddleware->checkPageAccess($codeFonctionnalite, $_SESSION['id_GU'], 'modifier');
}

/**
 * Vérifie si l'utilisateur peut supprimer des éléments
 * @param string $codeFonctionnalite
 * @return bool
 */
function canDelete($codeFonctionnalite = null)
{
    if (!isset($_SESSION['id_GU'])) {
        return false;
    }

    // Admin (règle données via libellé)
    if (isAdmin()) {
        return true;
    }

    if ($codeFonctionnalite === null) {
        $codeFonctionnalite = $_GET['page'] ?? '';
        if ($codeFonctionnalite !== '' && isset($_GET['action']) && is_string($_GET['action']) && $_GET['action'] !== '') {
            $codeFonctionnalite .= '&action=' . $_GET['action'];
        }
    }

    global $permissionMiddleware;
    if (!isset($permissionMiddleware)) {
        require_once __DIR__ . '/../middlewares/PermissionMiddleware.php';
        $permissionMiddleware = new PermissionMiddleware();
    }

    return $permissionMiddleware->checkPageAccess($codeFonctionnalite, $_SESSION['id_GU'], 'supprimer');
}

/**
 * Affiche un bouton seulement si l'utilisateur a la permission
 * @param string $action - 'creer', 'modifier', 'supprimer'
 * @param string $buttonHtml - Le HTML du bouton
 * @param string $codeFonctionnalite - Code de la fonctionnalité (optionnel)
 * @return string
 */
function showIfCan($action, $buttonHtml, $codeFonctionnalite = null)
{
    $canPerformAction = false;

    switch ($action) {
        case 'creer':
        case 'create':
            $canPerformAction = canCreate($codeFonctionnalite);
            break;
        case 'modifier':
        case 'edit':
            $canPerformAction = canEdit($codeFonctionnalite);
            break;
        case 'supprimer':
        case 'delete':
            $canPerformAction = canDelete($codeFonctionnalite);
            break;
        case 'voir':
        case 'view':
            $canPerformAction = canView($codeFonctionnalite);
            break;
    }

    return $canPerformAction ? $buttonHtml : '';
}

/**
 * Affiche un message si l'utilisateur n'a pas la permission
 * @param string $action
 * @param string $codeFonctionnalite
 * @return string
 */
function showNoPermissionMessage($action = 'voir', $codeFonctionnalite = null)
{
    $messages = [
        'voir' => 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.',
        'creer' => 'Vous n\'avez pas l\'autorisation de créer des éléments.',
        'modifier' => 'Vous n\'avez pas l\'autorisation de modifier des éléments.',
        'supprimer' => 'Vous n\'avez pas l\'autorisation de supprimer des éléments.'
    ];

    $message = isset($messages[$action]) ? $messages[$action] : $messages['voir'];

    return '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">' . htmlspecialchars($message) . '</p>
                    </div>
                </div>
            </div>';
}

/**
 * Récupère toutes les permissions de l'utilisateur pour la page courante
 * @param string $codeFonctionnalite
 * @return array ['peut_voir', 'peut_creer', 'peut_modifier', 'peut_supprimer']
 */
function getCurrentPermissions($codeFonctionnalite = null)
{
    if ($codeFonctionnalite === null) {
        $codeFonctionnalite = $_GET['page'] ?? '';
        if ($codeFonctionnalite !== '' && isset($_GET['action']) && is_string($_GET['action']) && $_GET['action'] !== '') {
            $codeFonctionnalite .= '&action=' . $_GET['action'];
        }
    }

    return [
        'peut_voir' => canView($codeFonctionnalite),
        'peut_creer' => canCreate($codeFonctionnalite),
        'peut_modifier' => canEdit($codeFonctionnalite),
        'peut_supprimer' => canDelete($codeFonctionnalite)
    ];
}

/**
 * Vérifie si l'utilisateur est administrateur
 * @return bool
 */
function isAdmin()
{
    if (!isset($_SESSION['lib_GU']) || !is_string($_SESSION['lib_GU'])) {
        return false;
    }
    $lib = strtolower(trim($_SESSION['lib_GU']));
    return $lib === 'administrateur' || $lib === 'admin';
}
