<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/FichePersAdminService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\FichePersAdminService;

/**
 * Controleur pour la Fiche Personnel Administratif (P2.2)
 */
class FichePersAdminController
{
    private FichePersAdminService $service;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->service = new FichePersAdminService($pdo);
    }

    /**
     * Afficher la fiche complete d'un personnel admin
     */
    public function index(?int $id = null): array
    {
        if (!canView('outils_direction')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $id = $id ?? (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            return [];
        }
        return $this->service->getFicheComplete($id);
    }

    public function getIdentite(int $id): ?array
    {
        return $this->service->getIdentite($id);
    }

    public function getCompte(int $id): ?array
    {
        return $this->service->getCompteUtilisateur($id);
    }

    public function getCandidatures(int $id): array
    {
        return $this->service->getCandidaturesTraitees($id);
    }

    public function getHistorique(int $id): array
    {
        return $this->service->getHistoriqueActions($id);
    }

    public function getStats(int $id): array
    {
        return $this->service->getStatsActions($id);
    }
}
