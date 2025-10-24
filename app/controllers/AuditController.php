<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Action.php';
require_once __DIR__ . '/../utils/permissions.php';

class AuditController {
    private $db;
    private $auditLog;
    private $action;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->auditLog = new AuditLog($this->db);
        $this->action = new Action($this->db);
    }

    public function index() {
        try {
            // Récupérer les actions pour le filtre
            $actions = $this->action->getAllAction();
            $GLOBALS['actions'] = $actions;

            // Paramètres de pagination
            $page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
            $perPage = 50;
            $offset = ($page - 1) * $perPage;

            // Paramètres de filtrage
            $filters = $this->getFilters();
            
            // Récupérer les logs avec filtres et pagination
            $auditLog = $this->getFilteredAuditLog($filters, $offset, $perPage);
            $totalLogs = $this->getTotalFilteredLogs($filters);
            
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
            $GLOBALS['perPage'] = 50;
            $GLOBALS['totalPages'] = 1;
            $GLOBALS['totalLogs'] = 0;
            $GLOBALS['error'] = "Une erreur s'est produite lors du chargement des logs d'audit.";
        }
    }

    private function getFilters() {
        return [
            'date_debut' => $_GET['date_debut'] ?? '',
            'date_fin' => $_GET['date_fin'] ?? '',
            'action' => $_GET['action'] ?? '',
            'search' => $_GET['search'] ?? '',
            'table' => $_GET['table'] ?? '',
            'statut' => $_GET['statut'] ?? '',
            'utilisateur' => $_GET['utilisateur'] ?? ''
        ];
    }

    private function getFilteredAuditLog($filters, $offset, $limit) {
        $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur 
                FROM pister p 
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur 
                WHERE 1=1";
        $params = [];

        // Filtre par date de début
        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(p.date_creation) >= ?";
            $params[] = $filters['date_debut'];
        }

        // Filtre par date de fin
        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(p.date_creation) <= ?";
            $params[] = $filters['date_fin'];
        }

        // Filtre par action
        if (!empty($filters['action'])) {
            $sql .= " AND p.action = ?";
            $params[] = $filters['action'];
        }

        // Filtre par table
        if (!empty($filters['table'])) {
            $sql .= " AND p.nom_table = ?";
            $params[] = $filters['table'];
        }

        // Filtre par statut
        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut_action = ?";
            $params[] = $filters['statut'];
        }

        // Filtre par utilisateur
        if (!empty($filters['utilisateur'])) {
            $sql .= " AND (u.login_utilisateur LIKE ? OR u.nom_utilisateur LIKE ?)";
            $params[] = '%' . $filters['utilisateur'] . '%';
            $params[] = '%' . $filters['utilisateur'] . '%';
        }

        // Filtre de recherche générale
        if (!empty($filters['search'])) {
            $sql .= " AND (p.action LIKE ? OR p.nom_table LIKE ? OR p.statut_action LIKE ? OR u.login_utilisateur LIKE ? OR u.nom_utilisateur LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY p.date_creation DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTotalFilteredLogs($filters) {
        $sql = "SELECT COUNT(*) 
                FROM pister p 
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur 
                WHERE 1=1";
        $params = [];

        // Appliquer les mêmes filtres que pour getFilteredAuditLog
        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(p.date_creation) >= ?";
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(p.date_creation) <= ?";
            $params[] = $filters['date_fin'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND p.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['table'])) {
            $sql .= " AND p.nom_table = ?";
            $params[] = $filters['table'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut_action = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['utilisateur'])) {
            $sql .= " AND (u.login_utilisateur LIKE ? OR u.nom_utilisateur LIKE ?)";
            $params[] = '%' . $filters['utilisateur'] . '%';
            $params[] = '%' . $filters['utilisateur'] . '%';
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.action LIKE ? OR p.nom_table LIKE ? OR p.statut_action LIKE ? OR u.login_utilisateur LIKE ? OR u.nom_utilisateur LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
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
        // Récupérer tous les logs avec les filtres actuels
        $filters = $this->getFilters();
        $logs = $this->getFilteredAuditLog($filters, 0, 10000); // Limite élevée pour l'export

        // Définir les en-têtes pour le téléchargement
        $filename = 'audit_log_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Créer le fichier CSV
        $output = fopen('php://output', 'w');
        
        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes CSV
        fputcsv($output, [
            'Date',
            'Heure',
            'Action',
            'Statut',
            'Table',
            'Login Utilisateur',
            'Nom Utilisateur'
        ], ';');

        // Données
        foreach ($logs as $log) {
            fputcsv($output, [
                date('d/m/Y', strtotime($log['date_creation'])),
                date('H:i:s', strtotime($log['date_creation'])),
                $log['action'],
                $log['statut_action'],
                $log['nom_table'],
                $log['login_utilisateur'] ?? 'N/A',
                $log['nom_utilisateur'] ?? 'N/A'
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function cleanupAuditLog() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=piste_audit&error=invalid_method');
            exit;
        }

        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        if ($days < 1 || $days > 365) {
            header('Location: ?page=piste_audit&error=invalid_days');
            exit;
        }

        try {
            $sql = "DELETE FROM pister WHERE date_creation < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            $deletedCount = $stmt->rowCount();

            // Log l'action de nettoyage
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Nettoyage', 'pister', 'Succès');

            header('Location: ?page=piste_audit&success=cleanup&deleted=' . $deletedCount);
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

        $logId = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        
        if ($logId <= 0) {
            header('Location: ?page=piste_audit&error=invalid_id');
            exit;
        }

        try {
            $sql = "DELETE FROM pister WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$logId]);
            
            if ($stmt->rowCount() > 0) {
                // Log l'action de suppression
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Suppression', 'pister', 'Succès');
                header('Location: ?page=piste_audit&success=log_deleted');
            } else {
                header('Location: ?page=piste_audit&error=log_not_found');
            }
            exit;
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression du log: " . $e->getMessage());
            header('Location: ?page=piste_audit&error=delete_failed');
            exit;
        }
    }

    public function getTablesList() {
        $sql = "SELECT DISTINCT nom_table FROM pister ORDER BY nom_table";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getStatutsList() {
        $sql = "SELECT DISTINCT statut_action FROM pister ORDER BY statut_action";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} 