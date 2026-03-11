<?php
if (!isset($GLOBALS['total_etudiants']) || !isset($GLOBALS['mes_cours'])) {
    require_once __DIR__ . '/../../app/controllers/DashboardEnseignantController.php';
    try {
        $dashboardTeacherController = new DashboardEnseignantController();
        $dashboardTeacherController->index();
    } catch (Throwable $e) {
        error_log('PRD5 dashboard enseignant fallback error: ' . $e->getMessage());
    }
}
require_once __DIR__ . '/v2/enseignant_dashboard.php';
