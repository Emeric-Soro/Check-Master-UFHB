<?php

require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../Services/DashboardSecretaireService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\DashboardSecretaireService;

class DashboardSecretaireController {
    private $pdo;
    private $etudiantModel;
    private $enseignantModel;
    private $service;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->etudiantModel = new Etudiant($pdo);
        $this->enseignantModel = new Enseignant($pdo);
        $this->service = new DashboardSecretaireService($pdo);
    }

    public function index() {
        if (!canView('dashboard_scolarite')) {
            return ['error' => 'Accès non autorisé au tableau de bord.'];
        }
        return $this->service->getDashboardData();
    }
} 
