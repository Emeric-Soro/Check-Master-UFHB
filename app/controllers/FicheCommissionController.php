<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/FicheCommissionService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

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
        if (!canView('commissions_archives') && !canView('fiche_commission')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            header('Location: layout.php?page=access_denied');
            exit;
        }
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
