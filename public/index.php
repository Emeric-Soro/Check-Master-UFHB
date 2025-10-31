<?php
/**
 * Central Entry Point - Check Master Application
 * 
 * This file serves as the single entry point for all application requests.
 * It handles authentication, routing, permissions, and delegates to controllers.
 */

// Start session
session_start();

// HTTP Security Headers
// Note: CSP includes 'unsafe-inline' and 'unsafe-eval' for compatibility with existing inline scripts
// TODO: Refactor inline scripts to external files and use CSP nonces for better XSS protection
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Referrer-Policy: no-referrer-when-downgrade");
header("X-XSS-Protection: 1; mode=block");

// Include necessary files
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/MenuController.php';
require_once __DIR__ . '/../app/utils/permissions.php';
require_once __DIR__ . '/../app/Router.php';

// Check authentication
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
}

// Initialize router
$router = new Router();

// Get requested page
$page = $_GET['page'] ?? '';

// Handle special actions before routing (PDF generation, etc.)
if ($router->handleSpecialActions($page)) {
    // Special action was handled and script was terminated
    exit;
}

// Generate menu
$menuController = new MenuController();
$traitements = $menuController->genererMenu($_SESSION['id_GU']);

// Determine current page and label
$currentMenuSlug = '';
$currentPageLabel = '';

if (!empty($page)) {
    // Find the page in available treatments
    foreach ($traitements as $traitement) {
        if ($traitement['lib_traitement'] === $page) {
            $currentMenuSlug = $traitement['lib_traitement'];
            $currentPageLabel = $traitement['label_traitement'];
            break;
        }
    }
    
    // Handle special pages not in menu
    if (empty($currentMenuSlug)) {
        $specialPages = ['archive_comptes_rendus', 'redaction_compte_rendu'];
        if (in_array($page, $specialPages)) {
            $currentMenuSlug = $page;
            $currentPageLabel = ($page === 'archive_comptes_rendus') 
                ? 'Archives des comptes rendus' 
                : 'Rédaction de compte rendu';
        }
    }
}

// Default to first available menu item if no page specified
if (empty($currentMenuSlug) && !empty($traitements)) {
    $currentMenuSlug = $traitements[0]['lib_traitement'];
    $currentPageLabel = $traitements[0]['label_traitement'];
    header('Location: index.php?page=' . urlencode($currentMenuSlug));
    exit;
}

// Check permissions (except for dashboard which is always accessible)
if (!empty($currentMenuSlug) && $currentMenuSlug !== 'dashboard') {
    if (!hasPermission($currentMenuSlug, 'READ')) {
        $_SESSION['error_message'] = "Accès refusé: vous n'avez pas les permissions nécessaires pour accéder à cette page.";
        header('Location: index.php?page=dashboard');
        exit;
    }
}

// Generate menu HTML
require_once __DIR__ . '/menu.php';
$menuView = new MenuView();
$menuHTML = $menuView->afficherMenu($traitements, $currentMenuSlug);

// Handle parametres_generaux special case with sub-actions
if ($currentMenuSlug === 'parametres_generaux') {
    require_once __DIR__ . '/../ressources/routes/parametreGenerauxRouteur.php';
    
    $partialsBasePath = __DIR__ . '/../ressources/views/';
    $contentFile = '';
    
    if (isset($_GET['action'])) {
        $allowedActions = [
            'annees_academiques', 'grades', 'fonctions', 'fonction_utilisateur',
            'specialites', 'niveaux_etude', 'ue', 'ecue', 'statut_jury',
            'niveaux_approbation', 'semestres', 'niveaux_acces', 'traitements',
            'entreprises', 'actions', 'fonctions_enseignants', 'messages',
            'gestion_attribution',
        ];
        
        if (in_array($_GET['action'], $allowedActions)) {
            $currentAction = $_GET['action'];
            $contentFile = $partialsBasePath . 'parametres_generaux/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        }
    } else {
        $contentFile = $partialsBasePath . 'parametres_generaux_content.php';
        $currentPageLabel = 'Paramètres Généraux';
    }
    
    // Include card data for parametres_generaux
    $cardPGeneraux = [
        ['title' => 'Années Académiques', 'description' => 'Gérer les années académiques, les dates de début et de fin.', 'link' => '?page=parametres_generaux&action=annees_academiques', 'icon' => './images/date-du-calendrier.png'],
        ['title' => 'Gestion des Grades', 'description' => 'Définir et administrer les différents grades académiques.', 'link' => '?page=parametres_generaux&action=grades', 'icon' => './images/diplome.png'],
        ['title' => 'Fonctions Utilisateurs', 'description' => 'Configurer les rôles et fonctions des utilisateurs du système.', 'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes', 'icon' => './images/equipe.png'],
        ['title' => 'Spécialités des enseignants', 'description' => 'Administrer les spécialités et filières proposées.', 'link' => '?page=parametres_generaux&action=specialites', 'icon' => './images/marche-de-niche.png'],
        ['title' => 'Niveaux d\'Étude', 'description' => 'Gérer les différents niveaux d\'étude (Licence, Master, etc.).', 'link' => '?page=parametres_generaux&action=niveaux_etude', 'icon' => './images/livre.png'],
        ['title' => 'Unités d\'Enseignement (UE)', 'description' => 'Définir les unités d\'enseignement et leurs crédits.', 'link' => '?page=parametres_generaux&action=ue', 'icon' => './images/livre-ouvert.png'],
        ['title' => 'Éléments Constitutifs (ECUE)', 'description' => 'Gérer les éléments constitutifs des unités d\'enseignement.', 'link' => '?page=parametres_generaux&action=ecue', 'icon' => './images/piece-de-puzzle.png'],
        ['title' => 'Statuts du Jury', 'description' => 'Configurer les différents statuts possibles pour les membres du jury.', 'link' => '?page=parametres_generaux&action=statut_jury', 'icon' => './images/droit.png'],
        ['title' => 'Niveaux d\'Approbation', 'description' => 'Définir les circuits et niveaux d\'approbation pour les documents.', 'link' => '?page=parametres_generaux&action=niveaux_approbation', 'icon' => './images/check.png'],
        ['title' => 'Semestres', 'description' => 'Définir les différents semestres et UE associées.', 'link' => '?page=parametres_generaux&action=semestres', 'icon' => './images/diplome.png'],
        ['title' => 'Niveaux d\'Accès', 'description' => 'Définir les différents niveaux d\'accès pour les utilisateurs', 'link' => '?page=parametres_generaux&action=niveaux_acces', 'icon' => './images/check.png'],
        ['title' => 'Traitements', 'description' => 'Définir les traitements à affecter aux différents utilisateurs.', 'link' => '?page=parametres_generaux&action=traitements', 'icon' => './images/bd.png'],
        ['title' => 'Entreprises', 'description' => 'Gérer les entreprises partenaires et leurs informations.', 'link' => '?page=parametres_generaux&action=entreprises', 'icon' => './images/valise.png'],
        ['title' => 'Actions', 'description' => 'Définir les actions possibles pour les utilisateurs dans le système.', 'link' => '?page=parametres_generaux&action=actions', 'icon' => './images/cible.png'],
        ['title' => 'Fonctions', 'description' => 'Définir les fonctions exercées par les enseignants dans le système.', 'link' => '?page=parametres_generaux&action=fonctions', 'icon' => './images/valise.png'],
        ['title' => 'Messagerie', 'description' => 'Définition des messages d\'erreur à afficher dans le système.', 'link' => '?page=parametres_generaux&action=messages', 'icon' => './images/enveloppe.png'],
        ['title' => 'Gestion des Attributions', 'description' => 'Gérer les attributions de traitement pour chacun des groupes utilisateurs dans le système.', 'link' => '?page=parametres_generaux&action=gestion_attribution', 'icon' => './images/attribution.png'],
    ];
    
    if (!empty($contentFile) && file_exists($contentFile)) {
        ob_start();
        include $contentFile;
        $content = ob_get_clean();
    } else {
        $content = "<div class='card p-6'><div class='text-danger'>Fichier de contenu introuvable</div></div>";
    }
}
// Handle gestion_reclamations special case with sub-actions
elseif ($currentMenuSlug === 'gestion_reclamations') {
    require_once __DIR__ . '/../ressources/routes/gestionReclamationsRouteur.php';
    
    $partialsBasePath = __DIR__ . '/../ressources/views/';
    $allowedActions = ['soumettre_reclamation', 'suivi_historique_reclamation'];
    $ajaxActions = ['get_reclamation_details'];
    
    if (isset($_GET['action'])) {
        if (in_array($_GET['action'], $ajaxActions)) {
            exit;
        } elseif (in_array($_GET['action'], $allowedActions)) {
            $currentAction = $_GET['action'];
            $contentFile = $partialsBasePath . 'gestion_reclamations/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            $contentFile = $partialsBasePath . 'gestion_reclamations_content.php';
        }
    } else {
        $contentFile = $partialsBasePath . 'gestion_reclamations_content.php';
    }
    
    $cardReclamation = [
        ['title' => 'Soumettre une Réclamation', 'description' => 'Déposez une nouvelle réclamation en remplissant le formulaire dédié.', 'link' => '?page=gestion_reclamations&action=soumettre_reclamation', 'icon' => 'fa-solid fa-circle-exclamation', 'title_link' => 'Soumettre', 'bg_color' => 'bg-accent-lighter', 'text_color' => 'text-accent'],
        ['title' => 'Suivi et historique des réclamations', 'description' => 'Consultez l\'état actuel de vos réclamations en cours et accédez à l\'historique complet de vos réclamations passées.', 'link' => '?page=gestion_reclamations&action=suivi_historique_reclamation', 'icon' => 'fa-solid fa-eye', 'title_link' => 'Suivi et historique', 'bg_color' => 'bg-warning/20', 'text_color' => 'text-warning']
    ];
    
    if (!empty($contentFile) && file_exists($contentFile)) {
        ob_start();
        include $contentFile;
        $content = ob_get_clean();
    } else {
        $content = "<div class='card p-6'><div class='text-danger'>Fichier de contenu introuvable</div></div>";
    }
}
// Handle gestion_rapports special case with sub-actions
elseif ($currentMenuSlug === 'gestion_rapports') {
    require_once __DIR__ . '/../ressources/routes/gestionRapportsRoutes.php';
    
    $partialsBasePath = __DIR__ . '/../ressources/views/';
    $allowedActions = ['creer_rapport', 'suivi_rapport', 'commentaire_rapport'];
    $ajaxActions = ['get_commentaires', 'get_rapport'];
    
    if (isset($_GET['action'])) {
        if (in_array($_GET['action'], $ajaxActions)) {
            exit;
        } elseif (in_array($_GET['action'], $allowedActions)) {
            $currentAction = $_GET['action'];
            $contentFile = $partialsBasePath . 'gestion_rapports/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            $contentFile = $partialsBasePath . 'gestion_rapports_content.php';
        }
    } else {
        $contentFile = $partialsBasePath . 'gestion_rapports_content.php';
    }
    
    if (!empty($contentFile) && file_exists($contentFile)) {
        ob_start();
        include $contentFile;
        $content = ob_get_clean();
    } else {
        $content = "<div class='card p-6'><div class='text-danger'>Fichier de contenu introuvable</div></div>";
    }
}
// Handle candidature_soutenance special case with sub-actions
elseif ($currentMenuSlug === 'candidature_soutenance') {
    require_once __DIR__ . '/../ressources/routes/candidatureSoutenanceRoutes.php';
    
    $partialsBasePath = __DIR__ . '/../ressources/views/';
    $allowedActions = ['compte_rendu_etudiant'];
    
    if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
        $currentAction = $_GET['action'];
        $contentFile = $partialsBasePath . 'candidature_soutenance/' . $currentAction . '.php';
        $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
    } else {
        $contentFile = $partialsBasePath . 'candidature_soutenance_content.php';
    }
    
    if (!empty($contentFile) && file_exists($contentFile)) {
        ob_start();
        include $contentFile;
        $content = ob_get_clean();
    } else {
        $content = "<div class='card p-6'><div class='text-danger'>Fichier de contenu introuvable</div></div>";
    }
}
// Handle gestion_etudiants special case with sub-actions
elseif ($currentMenuSlug === 'gestion_etudiants') {
    require_once __DIR__ . '/../ressources/routes/gestionEtudiantRoutes.php';
    
    $partialsBasePath = __DIR__ . '/../ressources/views/';
    $allowedActions = ['ajouter_des_etudiants', 'inscrire_des_etudiants'];
    
    if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
        $currentAction = $_GET['action'];
        $contentFile = $partialsBasePath . 'gestion_etudiants/' . $currentAction . '.php';
        $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
    } else {
        $contentFile = $partialsBasePath . 'gestion_etudiants_content.php';
    }
    
    if (!empty($contentFile) && file_exists($contentFile)) {
        ob_start();
        include $contentFile;
        $content = ob_get_clean();
    } else {
        $content = "<div class='card p-6'><div class='text-danger'>Fichier de contenu introuvable</div></div>";
    }
}
// Handle evaluation_soutenance special case
elseif ($currentMenuSlug === 'evaluation_soutenance') {
    require_once __DIR__ . '/../ressources/routes/evaluationSoutenanceRoutes.php';
    $content = $router->dispatch($currentMenuSlug);
}
// Default routing through Router
else {
    $content = $router->dispatch($currentMenuSlug);
}

// Include the layout template
include __DIR__ . '/layout.php';
