<?php

namespace App\Controllers;

use PDO;
use App\Models\AuditLog;
use App\Models\Action;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;

/**
 * AuditController - Visualisation des logs d'audit
 * 
 * Ce contrôleur gère l'affichage, le filtrage, l'export et le nettoyage
 * des logs d'audit de l'application.
 * 
 * @package App\Controllers
 */
class AuditController
{
    private PDO $pdo;
    private AuditLog $auditLog;
    private Action $action;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        AuditLog $auditLog,
        Action $action,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->auditLog = $auditLog;
        $this->action = $action;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Action : Afficher la liste des logs d'audit (READ)
     */
    public function index(): void
    {
        // Vérification des permissions
        if (!$this->checkPermission('read')) {
            return;
        }

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
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur dans AuditController::index(): " . $e->getMessage());
            $GLOBALS['auditLog'] = [];
            $GLOBALS['page'] = 1;
            $GLOBALS['perPage'] = 50;
            $GLOBALS['totalPages'] = 1;
            $GLOBALS['totalLogs'] = 0;
            $GLOBALS['error'] = "Une erreur s'est produite lors du chargement des logs d'audit.";
        }
    }

    /**
     * Vérification centralisée des permissions
     * 
     * @param string $action L'action à vérifier (read, create, update, delete)
     * @return bool True si l'utilisateur a la permission
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'piste_audit', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user {$_SESSION['id_utilisateur']} sur piste_audit"
            );
            
            // Définir l'erreur pour la vue
            $GLOBALS['error'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
            http_response_code(403);
            
            // Inclure la vue d'erreur 403
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Récupère les filtres depuis les paramètres GET
     */
    private function getFilters(): array
    {
        return [
            'date_debut' => $this->security->sanitizeInput($_GET['date_debut'] ?? ''),
            'date_fin' => $this->security->sanitizeInput($_GET['date_fin'] ?? ''),
            'action' => $this->security->sanitizeInput($_GET['action'] ?? ''),
            'search' => $this->security->sanitizeInput($_GET['search'] ?? ''),
            'table' => $this->security->sanitizeInput($_GET['table'] ?? ''),
            'statut' => $this->security->sanitizeInput($_GET['statut'] ?? ''),
            'utilisateur' => $this->security->sanitizeInput($_GET['utilisateur'] ?? '')
        ];
    }

    /**
     * Récupère les logs d'audit filtrés avec pagination
     */
    private function getFilteredAuditLog(array $filters, int $offset, int $limit): array
    {
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le total de logs filtrés
     */
    private function getTotalFilteredLogs(array $filters): int
    {
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Gère les actions spéciales (export, cleanup, delete)
     */
    private function handleSpecialActions(): void
    {
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

    /**
     * Exporte les logs d'audit en CSV
     */
    public function exportAuditLog(): void
    {
        // Vérifier la permission de lecture (export = lecture)
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Récupérer tous les logs avec les filtres actuels
            $filters = $this->getFilters();
            $logs = $this->getFilteredAuditLog($filters, 0, 10000);

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
            
            // Log l'export
            $this->auditLog->logAction(
                $_SESSION['id_utilisateur'] ?? null,
                'Export',
                'pister',
                'Succès'
            );
            
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'export des logs: " . $e->getMessage());
            header('Location: ?page=piste_audit&error=export_failed');
            exit;
        }
    }

    /**
     * Nettoie les anciens logs d'audit (DELETE)
     */
    public function cleanupAuditLog(): void
    {
        // Vérifier la permission de suppression
        if (!$this->checkPermission('delete')) {
            return;
        }

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
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$days]);
            $deletedCount = $stmt->rowCount();

            // Log l'action de nettoyage
            $this->auditLog->logAction(
                $_SESSION['id_utilisateur'] ?? null,
                'Nettoyage',
                'pister',
                'Succès'
            );

            $this->logger->info("Nettoyage audit: {$deletedCount} logs supprimés (> {$days} jours)");

            header('Location: ?page=piste_audit&success=cleanup&deleted=' . $deletedCount);
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors du nettoyage des logs: " . $e->getMessage());
            header('Location: ?page=piste_audit&error=cleanup_failed');
            exit;
        }
    }

    /**
     * Supprime un log d'audit spécifique (DELETE)
     */
    public function deleteSingleLog(): void
    {
        // Vérifier la permission de suppression
        if (!$this->checkPermission('delete')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=piste_audit&error=invalid_method');
            exit;
        }

        // Décoder l'ID hashé
        $hashedId = $_POST['log_id'] ?? '';
        $logId = $this->security->decodeId($hashedId);
        
        if (!$logId) {
            $this->logger->warning("Tentative de suppression avec ID invalide: {$hashedId}");
            header('Location: ?page=piste_audit&error=invalid_id');
            exit;
        }

        try {
            $sql = "DELETE FROM pister WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$logId]);
            
            if ($stmt->rowCount() > 0) {
                $this->auditLog->logSuppression(
                    $_SESSION['id_utilisateur'] ?? 0,
                    'pister',
                    'Succès'
                );
                header('Location: ?page=piste_audit&success=log_deleted');
            } else {
                header('Location: ?page=piste_audit&error=log_not_found');
            }
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la suppression du log: " . $e->getMessage());
            header('Location: ?page=piste_audit&error=delete_failed');
            exit;
        }
    }

    /**
     * Récupère la liste des tables distinctes dans les logs
     */
    public function getTablesList(): array
    {
        try {
            $sql = "SELECT DISTINCT nom_table FROM pister ORDER BY nom_table";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $this->logger->error("Erreur getTablesList: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la liste des statuts distincts dans les logs
     */
    public function getStatutsList(): array
    {
        try {
            $sql = "SELECT DISTINCT statut_action FROM pister ORDER BY statut_action";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $this->logger->error("Erreur getStatutsList: " . $e->getMessage());
            return [];
        }
    }
}