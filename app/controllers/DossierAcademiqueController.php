<?php

require_once __DIR__ . '/../models/DossierAcademique.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';   


require_once __DIR__ . '/../utils/CSRFProtection.php';
class DossierAcademiqueController {
    private $model;
    private $auditLog;
    private $db;
    public function __construct($pdo) {
        $this->model = new DossierAcademique($pdo);
        $this->db = Database::getConnection();
        $this->auditLog = new AuditLog($this->db);
    }

    public function index(){

        if(isset($_GET['action']) && $_GET['action'] === 'enregistrer_dossier'){
            $this->enregsitrer_dossier();
        }
        
        if(isset($_GET['action']) && $_GET['action'] === 'get_dossier'){
            $this->getDossier();
        }

    }


    public function enregsitrer_dossier() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Valider le jeton CSRF
                CSRFProtection::verifyRequest();
                
            $data = $_POST;
            $success = $this->model->saveOrUpdate($data);
            if($success){
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "dossiers_academiques", "Succès");
            }else{
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "dossiers_academiques", "Erreur");
            }
            // Redirection vers la page d'origine avec message
            $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
            $sep = (strpos($redirect, '?') === false) ? '?' : '&';
            header('Location: ' . $redirect . $sep . 'success=' . ($success ? '1' : '0'));
            exit;
        }
    }

    public function getDossier() {
        if (isset($_GET['num_etu'])) {
            $num_etu = $_GET['num_etu'];
            $dossier = $this->model->getByNumEtu($num_etu);
            
            header('Content-Type: application/json');
            echo json_encode($dossier ?: []);
            exit;
        }
    }
} 
