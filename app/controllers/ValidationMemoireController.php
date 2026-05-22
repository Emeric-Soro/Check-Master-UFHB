<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ValidationMemoireService.php';

use CheckMaster\Services\ValidationMemoireService;

class ValidationMemoireController
{
    private ValidationMemoireService $service;

    public function __construct()
    {
        $this->service = new ValidationMemoireService(Database::getConnection());
    }

    public function index(): array
    {
        return $this->service->getIndexData($_SESSION);
    }

    public function enregistrerDecision(): array
    {
        return $this->service->enregistrerDecision($_POST, $_SESSION);
    }
}
