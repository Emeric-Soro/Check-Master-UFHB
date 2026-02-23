<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/EvaluationDossiersService.php';

use CheckMaster\Services\EvaluationDossiersService;

class EvaluationDossiersController {
    private $service;

    public function __construct($pdo) {
        $db = Database::getConnection();
        $this->service = new EvaluationDossiersService($db);
    }

    public function index() {
        return $this->service->getIndexData();
    }
    
    public function traiterAction() {
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                // Ne pas exposer les détails techniques à l'utilisateur
                error_log('Erreur fatale PHP: ' . ($error['message'] ?? '') . ' dans ' . ($error['file'] ?? '') . ' ligne ' . ($error['line'] ?? ''));
                echo json_encode([
                    'success' => false, 
                    'message' => 'Une erreur interne est survenue. Veuillez réessayer.'
                ]);
            }
        });
        
        try {
            if (isset($_GET['debug'])) {
                error_log("DEBUG: traiterAction appelée");
                error_log("DEBUG: GET params: " . print_r($_GET, true));
                error_log("DEBUG: POST params: " . print_r($_POST, true));
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? $_GET['action'] ?? '';
                if (isset($_GET['debug'])) {
                    error_log("DEBUG: Action récupérée: '$action'");
                }
                
                switch ($action) {
                    case 'valider_dossier':
                        $this->validerDossier($_POST['id_rapport']);
                        break;
                    case 'rejeter_dossier':
                        $this->rejeterDossier($_POST['id_rapport'], $_POST['commentaire']);
                        break;
                    case 'traiter_decision':
                        // Correction : chaque membre enregistre son avis, sans rendre la décision finale
                        $decision = $_POST['decision'] ?? '';
                        $id_rapport = $_POST['id_rapport'] ?? '';
                        $commentaire = $_POST['commentaire'] ?? '';
                        $this->traiterDecisionCommission($id_rapport, $decision, $commentaire);
                        break;
                    case 'finaliser_decision':
                        $this->finaliserDecisionCommission($_POST['id_rapport']);
                        break;
                    case 'get_statistiques':
                        echo json_encode($this->service->getStatistiques());
                        break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'Action non reconnue: "' . $action . '"']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Exception: ' . $e->getMessage() . ' dans ' . $e->getFile() . ' ligne ' . $e->getLine()
            ]);
        } catch (Error $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur PHP: ' . $e->getMessage() . ' dans ' . $e->getFile() . ' ligne ' . $e->getLine()
            ]);
        }
    }
    
    private function validerDossier($id_rapport) {
        $id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
        if (!$id_utilisateur) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non identifié']);
            return;
        }
        
        error_log("DEBUG: Variables de session: " . print_r($_SESSION, true));
        
        $result = $this->service->validerDossier($id_rapport, $id_utilisateur);
        echo json_encode($result);
    }
    
    private function rejeterDossier($id_rapport, $commentaire) {
        $id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
        if (!$id_utilisateur) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non identifié']);
            return;
        }
        
        $result = $this->service->rejeterDossier($id_rapport, $commentaire, $id_utilisateur);
        echo json_encode($result);
    }
    
    private function traiterDecisionCommission($id_rapport, $decision, $commentaire = '') {
        $id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
        if (!$id_utilisateur) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non identifié']);
            return;
        }
        
        $result = $this->service->traiterDecisionCommission($id_rapport, $decision, $commentaire, $id_utilisateur);
        echo json_encode($result);
    }
    
    private function finaliserDecisionCommission($id_rapport) {
        $id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
        if (!$id_utilisateur) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non identifié']);
            return;
        }
        
        $result = $this->service->finaliserDecisionCommission($id_rapport, $id_utilisateur);
        echo json_encode($result);
    }
    
    public function detail($id_rapport) {
        return $this->service->getDetail($id_rapport);
    }
} 
