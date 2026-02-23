<?php

require_once __DIR__ . '/../Services/DossierAcademiqueService.php';
require_once __DIR__ . '/../../app/config/database.php';
use CheckMaster\Services\DossierAcademiqueService;


class DossierAcademiqueController {
    private $service;
    public function __construct($pdo) {
        $db = Database::getConnection();
        $this->service = new DossierAcademiqueService($db);
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
            $data = $_POST;
            $success = $this->service->saveOrUpdate($data, (int) $_SESSION['id_utilisateur']);
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
            $dossier = $this->service->getByNumEtu($num_etu);
            header('Content-Type: application/json');
            echo json_encode($dossier ?: []);
            exit;
        }
    }
} 