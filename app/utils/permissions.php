<?php

/**
 * Utilitaire de gestion des permissions
 * Fournit des fonctions globales pour vérifier les permissions utilisateur
 */

/**
 * Vérifie si l'utilisateur connecté a une permission spécifique
 * 
 * @param string $lib_traitement Libellé du traitement (ex: 'gestion_etudiants')
 * @param string $action Type d'action: 'CREATE', 'READ', 'UPDATE', 'DELETE'
 * @return bool True si l'utilisateur a la permission
 */
function hasPermission($lib_traitement, $action = 'READ')
{
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['permissions']) || !isset($_SESSION['id_GU'])) {
        return false;
    }

    // Mapper les actions vers leurs IDs
    $actionMap = [
        'CREATE' => 1,  // Ajouter
        'UPDATE' => 3,  // Modifier
        'DELETE' => 6,  // Supprimer
        'READ' => 7     // Consulter
    ];

    // Vérifier que l'action est valide
    if (!isset($actionMap[$action])) {
        error_log("Action invalide: $action");
        return false;
    }

    $id_action = $actionMap[$action];

    // Vérifier si les permissions contiennent le traitement et l'action
    if (isset($_SESSION['permissions'][$lib_traitement])) {
        return in_array($id_action, $_SESSION['permissions'][$lib_traitement]);
    }

    return false;
}

/**
 * Vérifie si l'utilisateur a au moins une des permissions spécifiées
 * 
 * @param string $lib_traitement Libellé du traitement
 * @param array $actions Tableau d'actions ('CREATE', 'READ', 'UPDATE', 'DELETE')
 * @return bool True si l'utilisateur a au moins une des permissions
 */
function hasAnyPermission($lib_traitement, $actions = [])
{
    foreach ($actions as $action) {
        if (hasPermission($lib_traitement, $action)) {
            return true;
        }
    }
    return false;
}

/**
 * Vérifie si l'utilisateur a toutes les permissions spécifiées
 * 
 * @param string $lib_traitement Libellé du traitement
 * @param array $actions Tableau d'actions ('CREATE', 'READ', 'UPDATE', 'DELETE')
 * @return bool True si l'utilisateur a toutes les permissions
 */
function hasAllPermissions($lib_traitement, $actions = [])
{
    foreach ($actions as $action) {
        if (!hasPermission($lib_traitement, $action)) {
            return false;
        }
    }
    return true;
}

/**
 * Charge les permissions de l'utilisateur dans la session
 * Cette fonction doit être appelée lors de la connexion
 * 
 * @param PDO $db Connexion à la base de données
 * @param int $id_GU ID du groupe d'utilisateurs
 * @return bool True si le chargement a réussi
 */
function loadUserPermissions($db, $id_GU)
{
    try {
        // Récupérer toutes les permissions du groupe avec les libellés de traitement
        $sql = "SELECT t.lib_traitement, p.id_action
                FROM permissions p
                INNER JOIN traitement t ON p.id_traitement = t.id_traitement
                WHERE p.id_GU = :id_GU
                ORDER BY t.lib_traitement, p.id_action";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':id_GU' => $id_GU]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les permissions par traitement
        $permissions = [];
        foreach ($results as $row) {
            $lib_traitement = $row['lib_traitement'];
            if (!isset($permissions[$lib_traitement])) {
                $permissions[$lib_traitement] = [];
            }
            $permissions[$lib_traitement][] = $row['id_action'];
        }

        // Stocker dans la session
        $_SESSION['permissions'] = $permissions;
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors du chargement des permissions: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie l'accès à un traitement et redirige si non autorisé
 * 
 * @param string $lib_traitement Libellé du traitement
 * @param string $action Type d'action requis (défaut: 'READ')
 * @param string $redirectUrl URL de redirection en cas d'accès refusé
 */
function requirePermission($lib_traitement, $action = 'READ', $redirectUrl = null)
{
    if (!hasPermission($lib_traitement, $action)) {
        // Enregistrer la tentative d'accès non autorisé
        error_log("Accès refusé: utilisateur " . ($_SESSION['login_utilisateur'] ?? 'inconnu') . 
                  " a tenté d'accéder à $lib_traitement avec l'action $action");
        
        // Définir un message d'erreur
        $_SESSION['error_message'] = "Accès refusé: vous n'avez pas les permissions nécessaires pour cette action.";
        
        // Rediriger
        if ($redirectUrl === null) {
            // Redirection par défaut vers le tableau de bord
            $redirectUrl = 'index.php?page=dashboard';
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Retourne un tableau des permissions de l'utilisateur pour un traitement
 * 
 * @param string $lib_traitement Libellé du traitement
 * @return array Tableau associatif ['CREATE' => bool, 'READ' => bool, 'UPDATE' => bool, 'DELETE' => bool]
 */
function getUserPermissions($lib_traitement)
{
    return [
        'CREATE' => hasPermission($lib_traitement, 'CREATE'),
        'READ' => hasPermission($lib_traitement, 'READ'),
        'UPDATE' => hasPermission($lib_traitement, 'UPDATE'),
        'DELETE' => hasPermission($lib_traitement, 'DELETE')
    ];
}

/**
 * Affiche un bouton conditionnel basé sur les permissions
 * 
 * @param string $lib_traitement Libellé du traitement
 * @param string $action Type d'action ('CREATE', 'UPDATE', 'DELETE')
 * @param string $html Code HTML du bouton à afficher
 * @return string HTML du bouton si autorisé, chaîne vide sinon
 */
function renderIfHasPermission($lib_traitement, $action, $html)
{
    return hasPermission($lib_traitement, $action) ? $html : '';
}

/**
 * Efface les permissions de la session
 * Utilisé lors de la déconnexion
 */
function clearPermissions()
{
    unset($_SESSION['permissions']);
}
