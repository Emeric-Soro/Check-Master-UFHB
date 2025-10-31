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
require_once __DIR__ . '/../app/PageHandler.php';

// Check authentication
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
}

// Initialize router and page handler
$router = new Router();
$pageHandler = new PageHandler();

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

// Generate page content
// Check if page has special handling (sub-actions, card configurations, etc.)
if ($pageHandler->hasSpecialHandling($currentMenuSlug)) {
    $content = $pageHandler->handleSpecialPage($currentMenuSlug, $currentPageLabel, $router, $currentMenuSlug);
} else {
    // Use default routing through Router
    $content = $router->dispatch($currentMenuSlug);
}

// Include the layout template
include __DIR__ . '/layout.php';
