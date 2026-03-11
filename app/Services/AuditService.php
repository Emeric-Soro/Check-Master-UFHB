<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Action.php';

use AuditLog;
use Action;
use PDO;

class AuditService
{
    private $db;
    private $auditLog;
    private $action;

    public function __construct($db)
    {
        $this->db = $db;
        $this->auditLog = new AuditLog($db);
        $this->action = new Action($db);
    }

    /**
     * Récupérer toutes les actions disponibles pour le filtre.
     */
    public function getAllActions()
    {
        return $this->action->getAllAction();
    }

    /**
     * Extraire les paramètres de filtrage depuis un tableau (typiquement $_GET).
     */
    public function extractFilters(array $params)
    {
        return [
            'date_debut'  => $params['date_debut'] ?? '',
            'date_fin'    => $params['date_fin'] ?? '',
            'action'      => $params['action'] ?? '',
            'search'      => $params['search'] ?? '',
            'table'       => $params['table'] ?? '',
            'statut'      => $params['statut'] ?? '',
            'utilisateur' => $params['utilisateur'] ?? ''
        ];
    }

    /**
     * Récupérer les logs d'audit filtrés avec pagination.
     */
    public function getFilteredAuditLog(array $filters, $offset, $limit)
    {
        $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur 
                FROM pister p 
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur 
                WHERE 1=1";
        $params = [];

        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY p.date_creation DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter le nombre total de logs correspondant aux filtres.
     */
    public function getTotalFilteredLogs(array $filters)
    {
        $sql = "SELECT COUNT(*) 
                FROM pister p 
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur 
                WHERE 1=1";
        $params = [];

        $this->applyFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Générer les données CSV d'export et les écrire dans un flux.
     *
     * @param resource $output  Flux ouvert (ex: fopen('php://output', 'w'))
     * @param array    $filters Filtres à appliquer
     */
    public function exportToStream($output, array $filters)
    {
        $logs = $this->getFilteredAuditLog($filters, 0, 10000);

        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
    }

    /**
     * Nettoyer les anciens logs (suppression par ancienneté en jours).
     *
     * @return array ['success' => bool, 'deleted' => int, 'message' => string]
     */
    public function cleanupLogs($days, $idUtilisateur)
    {
        if ($days < 1 || $days > 365) {
            return ['success' => false, 'deleted' => 0, 'message' => 'invalid_days'];
        }

        $sql = "DELETE FROM pister WHERE date_creation < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $deletedCount = $stmt->rowCount();

        $this->auditLog->logAction($idUtilisateur, 'Nettoyage', 'pister', 'Succès');

        return ['success' => true, 'deleted' => $deletedCount, 'message' => 'cleanup'];
    }

    /**
     * Supprimer un seul log par son identifiant.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteSingleLog($logId, $idUtilisateur)
    {
        if ($logId <= 0) {
            return ['success' => false, 'message' => 'invalid_id'];
        }

        $sql = "DELETE FROM pister WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$logId]);

        if ($stmt->rowCount() > 0) {
            $this->auditLog->logAction($idUtilisateur, 'Suppression', 'pister', 'Succès');
            return ['success' => true, 'message' => 'log_deleted'];
        }

        return ['success' => false, 'message' => 'log_not_found'];
    }

    /**
     * Récupérer la liste distincte des tables dans les logs.
     */
    public function getTablesList()
    {
        $sql = "SELECT DISTINCT nom_table FROM pister ORDER BY nom_table";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupérer la liste distincte des statuts dans les logs.
     */
    public function getStatutsList()
    {
        $sql = "SELECT DISTINCT statut_action FROM pister ORDER BY statut_action";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Appliquer les clauses WHERE de filtrage à une requête SQL.
     */
    private function applyFilters(&$sql, &$params, array $filters)
    {
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
    }
}