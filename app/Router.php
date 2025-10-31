<?php

/**
 * Central Router for Check Master Application
 * Handles all routing logic and controller dispatching
 */
class Router
{
    private $routes = [];
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/config/database.php';
        $this->db = Database::getConnection();
        $this->defineRoutes();
    }

    /**
     * Define all application routes
     * Maps page names to controller classes and methods
     */
    private function defineRoutes()
    {
        // Dashboard routes
        $this->routes['dashboard'] = [
            'controller' => 'DashboardController',
            'method' => 'index',
            'view' => 'dashboard_content.php'
        ];

        $this->routes['dashboard_commission'] = [
            'controller' => 'DashboardCommissionController',
            'method' => 'index',
            'view' => 'dashboard_commission_content.php'
        ];

        $this->routes['dashboard_enseignant'] = [
            'controller' => 'DashboardEnseignantController',
            'method' => 'index',
            'view' => 'dashboard_enseignant_content.php'
        ];

        $this->routes['dashboard_secretaire'] = [
            'controller' => 'DashboardSecretaireController',
            'method' => 'index',
            'view' => 'dashboard_secretaire_content.php'
        ];

        // User management
        $this->routes['gestion_utilisateurs'] = [
            'controller' => 'GestionUtilisateurController',
            'method' => 'index',
            'view' => 'gestion_utilisateurs_content.php'
        ];

        $this->routes['profil'] = [
            'controller' => 'GestionUtilisateurController',
            'method' => 'index',
            'view' => 'profil_content.php'
        ];

        // Student management
        $this->routes['gestion_etudiants'] = [
            'controller' => 'GestionEtudiantController',
            'method' => 'index',
            'view' => 'gestion_etudiants_content.php'
        ];

        $this->routes['liste_etudiants'] = [
            'controller' => 'ListeEtudiantsController',
            'method' => 'index',
            'view' => 'liste_etudiants_content.php'
        ];

        // HR management
        $this->routes['gestion_rh'] = [
            'controller' => 'GestionRhController',
            'method' => 'index',
            'view' => 'gestion_rh_content.php'
        ];

        // Scolarité
        $this->routes['gestion_scolarite'] = [
            'controller' => 'GestionScolariteController',
            'method' => 'index',
            'view' => 'gestion_scolarite_content.php'
        ];

        // Notes et évaluations
        $this->routes['gestion_notes_evaluations'] = [
            'controller' => 'GestionNotesController',
            'method' => 'index',
            'view' => 'gestion_notes_evaluations_content.php'
        ];

        $this->routes['notes_resultats'] = [
            'controller' => 'NotesResultatsController',
            'method' => 'index',
            'view' => 'notes_resultats_content.php'
        ];

        // Candidatures
        $this->routes['gestion_candidatures'] = [
            'controller' => 'GestionCandidaturesController',
            'method' => 'index',
            'view' => 'gestion_candidatures_content.php'
        ];

        $this->routes['candidature_soutenance'] = [
            'controller' => 'CandidatureSoutenanceController',
            'method' => 'index',
            'view' => 'candidature_soutenance_content.php'
        ];

        $this->routes['gestion_dossiers_candidatures'] = [
            'controller' => 'GestionDossiersCandidaturesController',
            'method' => 'index',
            'view' => 'gestion_dossiers_candidatures_content.php'
        ];

        // Dossiers académiques
        $this->routes['dossiers_academiques'] = [
            'controller' => 'DossierAcademiqueController',
            'method' => 'index',
            'view' => 'dossiers_academiques_content.php'
        ];

        // Vérification rapports
        $this->routes['verification_rapports'] = [
            'controller' => 'VerificationRapportsController',
            'method' => 'index',
            'view' => 'verification_rapports_content.php'
        ];

        // Gestion des rapports
        $this->routes['gestion_rapports'] = [
            'controller' => 'GestionRapportsController',
            'method' => 'index',
            'view' => 'gestion_rapports_content.php'
        ];

        // Réclamations
        $this->routes['gestion_reclamations'] = [
            'controller' => 'GestionReclamationsController',
            'method' => 'index',
            'view' => 'gestion_reclamations_content.php'
        ];

        $this->routes['gestion_reclamations_scolarite'] = [
            'controller' => 'GestionReclamationsScolariteController',
            'method' => 'index',
            'view' => 'gestion_reclamations_scolarite_content.php'
        ];

        // Évaluations
        $this->routes['evaluations_dossiers_soutenance'] = [
            'controller' => 'EvaluationDossiersController',
            'method' => 'index',
            'view' => 'evaluations_dossiers_soutenance_content.php'
        ];

        $this->routes['evaluation_soutenance'] = [
            'controller' => 'EvaluationSoutenanceController',
            'method' => 'index',
            'view' => 'evaluation_soutenance_content.php'
        ];

        $this->routes['criteres_evaluation'] = [
            'controller' => 'CriteresEvaluationController',
            'method' => 'index',
            'view' => 'criteres_evaluation_content.php'
        ];

        // Programmation et planification
        $this->routes['programmation_soutenance'] = [
            'controller' => 'ProgrammationSoutenanceController',
            'method' => 'index',
            'view' => 'programmation_soutenance_content.php'
        ];

        $this->routes['plannification_soutenance'] = [
            'controller' => 'PlannificationSoutenanceController',
            'method' => 'index',
            'view' => 'plannification_soutenance_content.php'
        ];

        // Comptes rendus
        $this->routes['redaction_compte_rendu'] = [
            'controller' => 'RedactionCompteRenduController',
            'method' => 'index',
            'view' => 'redaction_compte_rendu/redaction_compte_rendu_content.php'
        ];

        $this->routes['archive_comptes_rendus'] = [
            'controller' => 'ArchivesCompteRenduController',
            'method' => 'index',
            'view' => 'redaction_compte_rendu/archives_compte_rendu_content.php'
        ];

        // Archives
        $this->routes['archives_dossiers_soutenance'] = [
            'controller' => 'ArchivesDossiersSoutenanceController',
            'method' => 'index',
            'view' => 'archives_dossiers_soutenance_content.php'
        ];

        // Processus validation
        $this->routes['processus_validation'] = [
            'controller' => 'ProcessusValidationController',
            'method' => 'index',
            'view' => 'processus_validation_content.php'
        ];

        // Sauvegarde et restauration
        $this->routes['sauvegarde_restauration'] = [
            'controller' => 'SauvegardeRestaurationController',
            'method' => 'index',
            'view' => 'sauvegarde_restauration_content.php'
        ];

        // Audit
        $this->routes['piste_audit'] = [
            'controller' => 'AuditController',
            'method' => 'index',
            'view' => 'piste_audit_content.php'
        ];

        // Paramètres généraux (special handling in dispatch method)
        $this->routes['parametres_generaux'] = [
            'controller' => 'ParametresGenerauxController',
            'method' => 'index',
            'view' => 'parametres_generaux_content.php'
        ];
    }

    /**
     * Dispatch the request to the appropriate controller
     * 
     * @param string $page The page name from $_GET['page']
     * @return string The HTML content to be displayed
     */
    public function dispatch($page)
    {
        // Default to dashboard if no page specified
        if (empty($page) && !empty($_SESSION['id_GU'])) {
            require_once __DIR__ . '/controllers/MenuController.php';
            $menuController = new MenuController();
            $traitements = $menuController->genererMenu($_SESSION['id_GU']);
            if (!empty($traitements)) {
                $page = $traitements[0]['lib_traitement'];
            } else {
                $page = 'dashboard';
            }
        }

        // Check if route exists
        if (!isset($this->routes[$page])) {
            return $this->renderError("Page non trouvée : " . htmlspecialchars($page));
        }

        $route = $this->routes[$page];
        
        try {
            // Load the controller
            $controllerFile = __DIR__ . '/controllers/' . $route['controller'] . '.php';
            if (!file_exists($controllerFile)) {
                return $this->renderError("Contrôleur non trouvé pour la page : " . htmlspecialchars($page));
            }

            require_once $controllerFile;
            
            // Instantiate controller
            $controllerClass = $route['controller'];
            if (!class_exists($controllerClass)) {
                return $this->renderError("Classe de contrôleur non trouvée : " . htmlspecialchars($controllerClass));
            }

            // Some controllers need database connection in constructor
            if (in_array($controllerClass, ['DashboardSecretaireController', 'DossierAcademiqueController', 'EvaluationDossiersController'])) {
                $controller = new $controllerClass($this->db);
            } else {
                $controller = new $controllerClass();
            }

            // Call the method
            $method = $route['method'];
            if (!method_exists($controller, $method)) {
                return $this->renderError("Méthode non trouvée : " . htmlspecialchars($method));
            }

            // Capture the output
            ob_start();
            $result = $controller->$method();
            $output = ob_get_clean();

            // If the controller returned data (like DashboardSecretaireController), 
            // we need to include the view with that data
            if (is_array($result) && !empty($result)) {
                // Extract data for the view
                extract($result);
                ob_start();
                $viewFile = __DIR__ . '/../ressources/views/' . $route['view'];
                if (file_exists($viewFile)) {
                    include $viewFile;
                }
                $output = ob_get_clean();
            } elseif (empty($output)) {
                // If no output, try to include the view file
                $viewFile = __DIR__ . '/../ressources/views/' . $route['view'];
                if (file_exists($viewFile)) {
                    ob_start();
                    include $viewFile;
                    $output = ob_get_clean();
                }
            }

            return $output;

        } catch (Exception $e) {
            error_log("Router error: " . $e->getMessage());
            return $this->renderError("Erreur lors du chargement de la page : " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Handle special actions for specific pages
     * Some pages have special logic for actions (like PDF generation)
     * 
     * @param string $page The page name
     * @return bool True if action was handled, false otherwise
     */
    public function handleSpecialActions($page)
    {
        // Handle PDF generation for gestion_etudiants
        if ($page === 'gestion_etudiants' && isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $id_inscription = $_GET['id_inscription'];
            ob_start();
            include __DIR__ . '/../ressources/views/gestion_etudiants/recu_inscription.php';
            $html = ob_get_clean();
            
            if (class_exists('\Dompdf\Options')) {
                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf\Dompdf($options);
            } else {
                $dompdf = new Dompdf\Dompdf();
            }
            
            $publicPath = realpath(__DIR__ . '/../public');
            if ($publicPath) {
                $dompdf->setBasePath($publicPath);
            }
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream("recu_paiement_" . $id_inscription . ".pdf", array("Attachment" => false));
            exit;
        }

        // Handle PDF generation for gestion_scolarite
        if ($page === 'gestion_scolarite' && isset($_GET['action']) && $_GET['action'] === 'imprimer_recu' && isset($_GET['id'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $id_versement = $_GET['id'];
            ob_start();
            include __DIR__ . '/../ressources/views/recu_versement.php';
            $html = ob_get_clean();
            
            if (class_exists('\Dompdf\Options')) {
                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf\Dompdf($options);
            } else {
                $dompdf = new Dompdf\Dompdf();
            }
            
            $publicPath = realpath(__DIR__ . '/../public');
            if ($publicPath) {
                $dompdf->setBasePath($publicPath);
            }
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream("recu_paiement_" . $id_versement . ".pdf", array("Attachment" => false));
            exit;
        }

        // Handle PDF generation for gestion_notes_evaluations
        if ($page === 'gestion_notes_evaluations' && isset($_GET['action']) && $_GET['action'] === 'imprimer_releve' && isset($_GET['student']) && isset($_GET['niveau'])) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $id_etudiant = $_GET['student'];
            $niveau = $_GET['niveau'];
            ob_start();
            include __DIR__ . '/../ressources/views/releve_notes.php';
            $html = ob_get_clean();
            
            $dompdf = new Dompdf\Dompdf();
            $dompdf->setBasePath(__DIR__ . '/../public/images/');
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("releve_notes_" . $id_etudiant . "_" . $niveau . ".pdf", array("Attachment" => false));
            exit;
        }

        return false;
    }

    /**
     * Render an error message
     * 
     * @param string $message The error message
     * @return string HTML error content
     */
    private function renderError($message)
    {
        return "<div class='card p-6'>
                    <div class='text-danger font-semibold mb-2'>Erreur de chargement</div>
                    <div>" . htmlspecialchars($message) . "</div>
                </div>";
    }
}
