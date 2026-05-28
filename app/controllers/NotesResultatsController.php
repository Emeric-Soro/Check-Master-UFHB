<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/NotesResultatsService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\NotesResultatsService;

class NotesResultatsController {
    private $service;

    public function __construct() {
        $db = Database::getConnection();
        $this->service = new NotesResultatsService($db);
    }

    public function index() {
        if (!isset($_SESSION['num_etu']) || !canView('notes_resultats')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: layout.php?page=dashboard');
            exit;
        }
        $studentId = $_SESSION['num_etu'];
        $this->service->populateIndexGlobals($studentId);
    }

    public function exportPdf() {
        if (!isset($_SESSION['num_etu']) || !canView('notes_resultats')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: layout.php?page=dashboard');
            exit;
        }
        $studentId = $_SESSION['num_etu'];
        $this->service->generatePdf($studentId);
    }
}
