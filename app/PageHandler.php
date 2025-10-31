<?php

/**
 * Page Handler
 * Handles special page logic and content generation for pages with sub-actions
 */
class PageHandler
{
    private $config;
    private $partialsBasePath;

    public function __construct()
    {
        $this->config = require __DIR__ . '/config/pages.php';
        $this->partialsBasePath = __DIR__ . '/../ressources/views/';
    }

    /**
     * Handle parametres_generaux page with sub-actions
     */
    public function handleParametresGeneraux(&$currentPageLabel)
    {
        require_once __DIR__ . '/../ressources/routes/parametreGenerauxRouteur.php';
        
        $contentFile = '';
        $config = $this->config['parametres_generaux'];
        
        if (isset($_GET['action']) && in_array($_GET['action'], $config['allowed_actions'])) {
            $currentAction = $_GET['action'];
            $contentFile = $this->partialsBasePath . 'parametres_generaux/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            $contentFile = $this->partialsBasePath . 'parametres_generaux_content.php';
            $currentPageLabel = 'Paramètres Généraux';
        }
        
        // Make card data available to the view
        $GLOBALS['cardPGeneraux'] = $config['cards'];
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle gestion_reclamations page with sub-actions
     */
    public function handleGestionReclamations(&$currentPageLabel)
    {
        require_once __DIR__ . '/../ressources/routes/gestionReclamationsRouteur.php';
        
        $config = $this->config['gestion_reclamations'];
        $contentFile = $this->partialsBasePath . 'gestion_reclamations_content.php';
        
        if (isset($_GET['action'])) {
            if (in_array($_GET['action'], $config['ajax_actions'])) {
                exit; // Let the route file handle AJAX
            } elseif (in_array($_GET['action'], $config['allowed_actions'])) {
                $currentAction = $_GET['action'];
                $contentFile = $this->partialsBasePath . 'gestion_reclamations/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            }
        }
        
        // Make card data available to the view
        $GLOBALS['cardReclamation'] = $config['cards'];
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle gestion_rapports page with sub-actions
     */
    public function handleGestionRapports(&$currentPageLabel)
    {
        require_once __DIR__ . '/../ressources/routes/gestionRapportsRoutes.php';
        
        $config = $this->config['gestion_rapports'];
        $contentFile = $this->partialsBasePath . 'gestion_rapports_content.php';
        
        if (isset($_GET['action'])) {
            if (in_array($_GET['action'], $config['ajax_actions'])) {
                exit; // Let the route file handle AJAX
            } elseif (in_array($_GET['action'], $config['allowed_actions'])) {
                $currentAction = $_GET['action'];
                $contentFile = $this->partialsBasePath . 'gestion_rapports/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            }
        }
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle candidature_soutenance page with sub-actions
     */
    public function handleCandidatureSoutenance(&$currentPageLabel)
    {
        require_once __DIR__ . '/../ressources/routes/candidatureSoutenanceRoutes.php';
        
        $config = $this->config['candidature_soutenance'];
        $contentFile = $this->partialsBasePath . 'candidature_soutenance_content.php';
        
        if (isset($_GET['action']) && in_array($_GET['action'], $config['allowed_actions'])) {
            $currentAction = $_GET['action'];
            $contentFile = $this->partialsBasePath . 'candidature_soutenance/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        }
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle gestion_etudiants page with sub-actions
     */
    public function handleGestionEtudiants(&$currentPageLabel)
    {
        require_once __DIR__ . '/../ressources/routes/gestionEtudiantRoutes.php';
        
        $config = $this->config['gestion_etudiants'];
        $contentFile = $this->partialsBasePath . 'gestion_etudiants_content.php';
        
        if (isset($_GET['action']) && in_array($_GET['action'], $config['allowed_actions'])) {
            $currentAction = $_GET['action'];
            $contentFile = $this->partialsBasePath . 'gestion_etudiants/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        }
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle evaluation_soutenance page
     */
    public function handleEvaluationSoutenance($router, $currentMenuSlug)
    {
        require_once __DIR__ . '/../ressources/routes/evaluationSoutenanceRoutes.php';
        return $router->dispatch($currentMenuSlug);
    }

    /**
     * Load content from file
     */
    private function loadContent($contentFile)
    {
        if (!empty($contentFile) && file_exists($contentFile)) {
            ob_start();
            include $contentFile;
            return ob_get_clean();
        }
        
        return "<div class='card p-6'><div class='text-danger'>Fichier de contenu introuvable</div></div>";
    }

    /**
     * Check if a page has special handling
     */
    public function hasSpecialHandling($page)
    {
        return in_array($page, [
            'parametres_generaux',
            'gestion_reclamations',
            'gestion_rapports',
            'candidature_soutenance',
            'gestion_etudiants',
            'evaluation_soutenance'
        ]);
    }

    /**
     * Handle special page routing
     */
    public function handleSpecialPage($page, &$currentPageLabel, $router = null, $currentMenuSlug = null)
    {
        switch ($page) {
            case 'parametres_generaux':
                return $this->handleParametresGeneraux($currentPageLabel);
            
            case 'gestion_reclamations':
                return $this->handleGestionReclamations($currentPageLabel);
            
            case 'gestion_rapports':
                return $this->handleGestionRapports($currentPageLabel);
            
            case 'candidature_soutenance':
                return $this->handleCandidatureSoutenance($currentPageLabel);
            
            case 'gestion_etudiants':
                return $this->handleGestionEtudiants($currentPageLabel);
            
            case 'evaluation_soutenance':
                return $this->handleEvaluationSoutenance($router, $currentMenuSlug);
            
            default:
                return null;
        }
    }
}
