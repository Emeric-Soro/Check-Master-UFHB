<?php
/**
 * HistoriqueModificationsService
 *
 * Service pour l'historique des modifications (piste d'audit par entité).
 * Tables : pister, utilisateur
 */

namespace CheckMaster\Services;

use PDO;

class HistoriqueModificationsService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les logs d'audit avec filtres par entité, action, date.
     */
    public function getFilteredLogs(array $filters, int $offset = 0, int $limit = 50): array
    {
        $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE 1=1";
        $params = [];

        if (!empty($filters['date_debut'])) {
            $sql .= " AND p.date_creation >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND p.date_creation <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['action'])) {
            $sql .= " AND p.action LIKE :action";
            $params[':action'] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['entite'])) {
            $sql .= " AND p.contexte LIKE :entite";
            $params[':entite'] = '%' . $filters['entite'] . '%';
        }
        if (!empty($filters['utilisateur'])) {
            $sql .= " AND (u.nom_utilisateur LIKE :utilisateur OR u.login_utilisateur LIKE :utilisateur2)";
            $params[':utilisateur'] = '%' . $filters['utilisateur'] . '%';
            $params[':utilisateur2'] = '%' . $filters['utilisateur'] . '%';
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut_action = :statut";
            $params[':statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY p.date_creation DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre total de logs correspondant aux filtres.
     */
    public function getTotalFilteredLogs(array $filters): int
    {
        $sql = "SELECT COUNT(*)
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE 1=1";
        $params = [];

        if (!empty($filters['date_debut'])) {
            $sql .= " AND p.date_creation >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND p.date_creation <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
        }
        if (!empty($filters['action'])) {
            $sql .= " AND p.action LIKE :action";
            $params[':action'] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['entite'])) {
            $sql .= " AND p.contexte LIKE :entite";
            $params[':entite'] = '%' . $filters['entite'] . '%';
        }
        if (!empty($filters['utilisateur'])) {
            $sql .= " AND (u.nom_utilisateur LIKE :utilisateur OR u.login_utilisateur LIKE :utilisateur2)";
            $params[':utilisateur'] = '%' . $filters['utilisateur'] . '%';
            $params[':utilisateur2'] = '%' . $filters['utilisateur'] . '%';
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut_action = :statut";
            $params[':statut'] = $filters['statut'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupère les logs groupés par entité pour la timeline.
     */
    public function getLogsByEntity(array $filters, int $offset = 0, int $limit = 50): array
    {
        $logs = $this->getFilteredLogs($filters, $offset, $limit);

        $grouped = [];
        foreach ($logs as $log) {
            $entite = $log['contexte'] ?? 'autre';
            if (!isset($grouped[$entite])) {
                $grouped[$entite] = [
                    'entite' => $entite,
                    'items'  => [],
                ];
            }
            $grouped[$entite]['items'][] = $log;
        }

        // Trier par nombre d'items descendant
        uasort($grouped, function ($a, $b) {
            return count($b['items']) - count($a['items']);
        });

        return array_values($grouped);
    }

    /**
     * Exporte les logs filtrés au format CSV.
     */
    public function exportLogsCSV(array $filters): void
    {
        require_once __DIR__ . '/../utils/ExportHelper.php';

        $logs = $this->getFilteredLogs($filters, 0, 10000);
        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'date'        => $log['date_creation'] ?? '',
                'action'      => $log['action'] ?? '',
                'entite'      => $log['contexte'] ?? '',
                'id_entite'   => $log['id_entite'] ?? '',
                'utilisateur' => $log['nom_utilisateur'] ?? $log['login_utilisateur'] ?? '',
                'statut'      => $log['statut_action'] ?? '',
                'details'     => $log['details'] ?? '',
            ];
        }

        $headers = [
            'date'        => 'Date',
            'action'      => 'Action',
            'entite'      => 'Entité',
            'id_entite'   => 'ID Entité',
            'utilisateur' => 'Utilisateur',
            'statut'      => 'Statut',
            'details'     => 'Détails',
        ];

        \ExportHelper::exportToCSV($data, $headers, 'historique_modifications_' . date('Ymd_His') . '.csv');
    }

    /**
     * Retourne la liste des actions distinctes.
     */
    public function getAllActions(): array
    {
        try {
            $stmt = $this->db->query("SELECT DISTINCT action FROM pister WHERE action IS NOT NULL AND action <> '' ORDER BY action");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retourne la liste des entités (contexte) distinctes.
     */
    public function getAllEntities(): array
    {
        try {
            $stmt = $this->db->query("SELECT DISTINCT contexte FROM pister WHERE contexte IS NOT NULL AND contexte <> '' ORDER BY contexte");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Extrait les filtres depuis un tableau (GET).
     */
    public function extractFilters(array $params): array
    {
        return [
            'date_debut'  => $params['date_debut'] ?? '',
            'date_fin'    => $params['date_fin'] ?? '',
            'action'      => $params['action'] ?? '',
            'entite'      => $params['entite'] ?? '',
            'utilisateur' => $params['utilisateur'] ?? '',
            'statut'      => $params['statut'] ?? '',
        ];
    }
}
