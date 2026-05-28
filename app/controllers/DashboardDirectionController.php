<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/DashboardDirectionService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\DashboardDirectionService;

class DashboardDirectionController
{
    private DashboardDirectionService $service;

    public function __construct()
    {
        $this->service = new DashboardDirectionService(Database::getConnection());
    }

    /**
     * Page principale du dashboard direction.
     */
    public function index(): void
    {
        // Permission (slug 'dashboard_direction' ou fallback 'dashboard')
        if (!canView('dashboard') && !canView('dashboard_direction')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: layout.php?page=dashboard');
            exit;
        }

        $kpis = $this->service->getKPIs();

        $GLOBALS['kpis'] = $kpis;
    }
}
