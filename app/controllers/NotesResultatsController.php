<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/NotesResultatsService.php';

use CheckMaster\Services\NotesResultatsService;

class NotesResultatsController {
    private $service;

    public function __construct() {
        $db = Database::getConnection();
        $this->service = new NotesResultatsService($db);
    }

    public function index() {
        $studentId = $_SESSION['num_etu'];
        $this->service->populateIndexGlobals($studentId);
    }

    public function exportPdf() {
        $studentId = $_SESSION['num_etu'];
        $this->service->generatePdf($studentId);
    }
}
