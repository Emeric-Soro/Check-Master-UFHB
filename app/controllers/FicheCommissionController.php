<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/FicheCommissionService.php';

use CheckMaster\Services\FicheCommissionService;

/**
 * Controleur pour la Fiche Commission (P2.1)
 */
class FicheCommissionController
{
    private FicheCommissionService $service;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->service = new FicheCommissionService($pdo);
    }

    /**
     * Donnees completes pour la vue
     */
    public function index(): array
    {
        return $this->service->getDonneesPage();
    }

    public function getMembres(): array
    {
        return $this->service->getMembresCommission();
    }

    public function getRapportsEvalues(): array
    {
        return $this->service->getRapportsEvalues();
    }

    public function getRapportsEnAttente(): array
    {
        return $this->service->getRapportsEnAttente();
    }

    public function getStatsVote(): array
    {
        return $this->service->getStatsVote();
    }

    public function getDecisions(): array
    {
        return $this->service->getDecisionsRecentes();
    }

    public function getPlanning(): array
    {
        return $this->service->getPlanningSeances();
    }
}
