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
        $contentFile = '';
        $config = $this->config['parametres_generaux'];
        
        // Execute the controller action if specified
        if (isset($_GET['action']) && in_array($_GET['action'], $config['allowed_actions'])) {
            require_once __DIR__ . '/controllers/ParametreController.php';
            $controller = new ParametreController();
            
            // Call the appropriate method based on action
            $action = $_GET['action'];
            $methodMap = [
                'annees_academiques' => 'gestionAnnees',
                'grades' => 'gestionGrade',
                'fonction_utilisateur' => 'gestionFonctionUtilisateur',
                'specialites' => 'gestionSpecialite',
                'niveaux_etude' => 'gestionNiveauEtude',
                'ue' => 'gestionUe',
                'ecue' => 'gestionEcue',
                'statut_jury' => 'gestionStatutJury',
                'niveaux_approbation' => 'gestionNiveauApprobation',
                'semestres' => 'gestionSemestre',
                'niveaux_acces' => 'gestionNiveauAccesDonnees',
                'traitements' => 'gestionTraitement',
                'entreprises' => 'gestionEntreprise',
                'actions' => 'gestionAction',
                'fonctions' => 'gestionFonction',
                'fonctions_enseignants' => 'gestionFonction',
                'messages' => 'gestionMessagerie',
                'gestion_attribution' => 'gestionAttribution',
            ];
            
            if (isset($methodMap[$action]) && method_exists($controller, $methodMap[$action])) {
                $controller->{$methodMap[$action]}();
            }
            
            $currentAction = $action;
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
        require_once __DIR__ . '/controllers/GestionReclamationsController.php';
        $controller = new GestionReclamationsController();
        
        $config = $this->config['gestion_reclamations'];
        $contentFile = $this->partialsBasePath . 'gestion_reclamations_content.php';
        
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            
            // Handle AJAX actions
            if (in_array($action, $config['ajax_actions'])) {
                // Call controller method and exit
                $methodMap = [
                    'get_reclamation_details' => 'getReclamationDetailsAjax'
                ];
                
                if (isset($methodMap[$action]) && method_exists($controller, $methodMap[$action])) {
                    $controller->{$methodMap[$action]}();
                    exit;
                }
            }
            // Handle regular actions
            elseif (in_array($action, $config['allowed_actions'])) {
                $methodMap = [
                    'soumettre_reclamation' => 'soumettreReclamations',
                    'suivi_historique_reclamation' => 'suiviHistoriqueReclamations',
                    'traiter' => 'traiterReclamation',
                    'exporter_reclamations' => 'exporterReclamations'
                ];
                
                if (isset($methodMap[$action]) && method_exists($controller, $methodMap[$action])) {
                    $controller->{$methodMap[$action]}();
                }
                
                $currentAction = $action;
                $contentFile = $this->partialsBasePath . 'gestion_reclamations/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            }
        } else {
            // No action specified, call index
            $controller->index();
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
        require_once __DIR__ . '/controllers/GestionRapportController.php';
        $controller = new GestionRapportController();
        
        $config = $this->config['gestion_rapports'];
        $contentFile = $this->partialsBasePath . 'gestion_rapports_content.php';
        
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            
            // Handle AJAX actions
            if (in_array($action, $config['ajax_actions'])) {
                // AJAX actions should be handled by the controller's specific methods
                exit; // Exit to prevent rendering
            } 
            // Handle regular actions
            elseif (in_array($action, $config['allowed_actions'])) {
                $currentAction = $action;
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
        require_once __DIR__ . '/controllers/CandidatureSoutenanceController.php';
        $controller = new CandidatureSoutenanceController();
        
        $config = $this->config['candidature_soutenance'];
        $contentFile = $this->partialsBasePath . 'candidature_soutenance_content.php';
        
        if (isset($_GET['action']) && in_array($_GET['action'], $config['allowed_actions'])) {
            $currentAction = $_GET['action'];
            $contentFile = $this->partialsBasePath . 'candidature_soutenance/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            // Call controller index
            $controller->index();
        }
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle gestion_etudiants page with sub-actions
     */
    public function handleGestionEtudiants(&$currentPageLabel)
    {
        require_once __DIR__ . '/controllers/GestionEtudiantController.php';
        $controller = new GestionEtudiantController();
        
        $config = $this->config['gestion_etudiants'];
        $contentFile = $this->partialsBasePath . 'gestion_etudiants_content.php';
        
        if (isset($_GET['action']) && in_array($_GET['action'], $config['allowed_actions'])) {
            $currentAction = $_GET['action'];
            $contentFile = $this->partialsBasePath . 'gestion_etudiants/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            // Call controller index
            $controller->index();
        }
        
        return $this->loadContent($contentFile);
    }

    /**
     * Handle evaluation_soutenance page
     */
    public function handleEvaluationSoutenance($router, $currentMenuSlug)
    {
        // For evaluation_soutenance, we still need to use the route file as it has complex logic
        require_once __DIR__ . '/../ressources/routes/evaluationSoutenanceRoutes.php';
        return $router->dispatch($currentMenuSlug);
    }

    /**
     * Load content from file
     */
    private function loadContent($contentFile)
    {
        if (!empty($contentFile) && file_exists($contentFile)) {
            try {
                ob_start();
                include $contentFile;
                return ob_get_clean();
            } catch (Exception $e) {
                error_log("Error loading content from {$contentFile}: " . $e->getMessage());
                return "<div class='card p-6'>
                            <div class='text-danger font-semibold mb-2'>Erreur de chargement</div>
                            <div>Une erreur s'est produite lors du chargement de la page.</div>
                        </div>";
            }
        }
        
        error_log("Content file not found: {$contentFile}");
        return "<div class='card p-6'>
                    <div class='text-danger font-semibold mb-2'>Fichier introuvable</div>
                    <div>Le fichier de contenu demandé n'existe pas: " . htmlspecialchars(basename($contentFile)) . "</div>
                </div>";
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
