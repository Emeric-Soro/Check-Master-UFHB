<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/AuditService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\AuditService;

class AuditController {
    private $service;

    public function __construct() {
        $db = Database::getConnection();
        $this->service = new AuditService($db);
    }

    public function index() {
        try {
            // Récupérer les actions pour le filtre
            $actions = $this->service->getAllActions();
            $GLOBALS['actions'] = $actions;

            // Paramètres de pagination
            $page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
            $perPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            if (!in_array($perPage, [5, 10, 25, 50, 100], true)) {
                $perPage = 10;
            }
            $offset = ($page - 1) * $perPage;

            // Paramètres de filtrage
            $filters = $this->service->extractFilters($_GET);
            
            // Récupérer les logs avec filtres et pagination
            $auditLog = $this->service->getFilteredAuditLog($filters, $offset, $perPage);
            $totalLogs = $this->service->getTotalFilteredLogs($filters);
            
            // Calculer la pagination
            $totalPages = ceil($totalLogs / $perPage);
            
            // Gérer les actions spéciales
            $this->handleSpecialActions();
            
            // Passer les données à la vue
            $GLOBALS['auditLog'] = $auditLog;
            $GLOBALS['page'] = $page;
            $GLOBALS['perPage'] = $perPage;
            $GLOBALS['totalPages'] = $totalPages;
            $GLOBALS['totalLogs'] = $totalLogs;
        } catch (Exception $e) {
            error_log("Erreur dans AuditController::index(): " . $e->getMessage());
            $GLOBALS['auditLog'] = [];
            $GLOBALS['page'] = 1;
            $GLOBALS['perPage'] = 10;
            $GLOBALS['totalPages'] = 1;
            $GLOBALS['totalLogs'] = 0;
            $GLOBALS['error'] = "Une erreur s'est produite lors du chargement des logs d'audit.";
        }
    }

    private function handleSpecialActions() {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'export':
                    $this->exportAuditLog();
                    break;
                case 'cleanup':
                    $this->cleanupAuditLog();
                    break;
                case 'delete_log':
                    $this->deleteSingleLog();
                    break;
            }
        }
    }

    public function exportAuditLog() {
        if (!canView('piste_audit')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Accès non autorisé."]);
            exit;
        }
        $filters = $this->service->extractFilters($_GET);

        // Définir les en-têtes pour le téléchargement
        $filename = 'audit_log_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Créer le fichier CSV via le service
        $output = fopen('php://output', 'w');
        $this->service->exportToStream($output, $filters);
        fclose($output);
        exit;
    }

    public function cleanupAuditLog() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=piste_audit&error=invalid_method');
            exit;
        }
        if (!canDelete('piste_audit')) {
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            header('Location: ?page=piste_audit&error=permission_denied');
            exit;
        }
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;

        try {
            $result = $this->service->cleanupLogs($days, $_SESSION['id_utilisateur']);

            if (!$result['success']) {
                header('Location: ?page=piste_audit&error=' . $result['message']);
                exit;
            }

            header('Location: ?page=piste_audit&success=cleanup&deleted=' . $result['deleted']);
            exit;
        } catch (Exception $e) {
            error_log("Erreur lors du nettoyage des logs: " . $e->getMessage());
            header('Location: ?page=piste_audit&error=cleanup_failed');
            exit;
        }
    }

    public function deleteSingleLog() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=piste_audit&error=invalid_method');
            exit;
        }
        if (!canDelete('piste_audit')) {
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            header('Location: ?page=piste_audit&error=permission_denied');
            exit;
        }
        $logId = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;

        try {
            $result = $this->service->deleteSingleLog($logId, $_SESSION['id_utilisateur']);

            if ($result['success']) {
                header('Location: ?page=piste_audit&success=' . $result['message']);
            } else {
                header('Location: ?page=piste_audit&error=' . $result['message']);
            }
            exit;
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression du log: " . $e->getMessage());
            header('Location: ?page=piste_audit&error=delete_failed');
            exit;
        }
    }

    public function getTablesList() {
        return $this->service->getTablesList();
    }

    public function getStatutsList() {
        return $this->service->getStatutsList();
    }
} 
