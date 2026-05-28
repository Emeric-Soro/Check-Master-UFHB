<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

use AuditLog;
use PDO;

class AuditService
{
    private $db;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Récupérer toutes les actions disponibles pour le filtre.
     */
    public function getAllActions()
    {
        $sql = "SELECT DISTINCT action FROM pister WHERE action IS NOT NULL AND action <> '' ORDER BY action";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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
     * Utilise un curseur PDO pour éviter de charger toutes les lignes en mémoire.
     *
     * @param resource $output  Flux ouvert (ex: fopen('php://output', 'w'))
     * @param array    $filters Filtres à appliquer
     */
    public function exportToStream($output, array $filters)
    {
        $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY p.date_creation DESC";

        // Mode non bufferisé pour streamer sans charger tout en mémoire
        $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // En-têtes CSV
        fputcsv($output, [
            'Date',
            'Heure',
            'Action',
            'Statut',
            'Contexte',
            'ID Utilisateur',
            'Login Utilisateur',
            'Nom Utilisateur'
        ], ';', '"', '');

        // Données ligne par ligne (pas de fetchAll)
        while ($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                date('d/m/Y', strtotime($log['date_creation'])),
                date('H:i:s', strtotime($log['date_creation'])),
                $log['action'],
                $log['statut_action'],
                $log['contexte'],
                $log['id_utilisateur'] ?? 'N/A',
                $log['login_utilisateur'] ?? 'N/A',
                $log['nom_utilisateur'] ?? 'N/A'
            ], ';', '"', '');
        }

        $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * Nettoyer les anciens logs (suppression par ancienneté en jours).
     *
     * @return array ['success' => bool, 'deleted' => int, 'message' => string]
     */
    public function cleanupLogs($days, $idUtilisateur)
    {
        if ($days < 1 || $days > 365) {
            error_log('[CLEANUP_DEBUG] jours invalides: ' . $days);
            return ['success' => false, 'deleted' => 0, 'message' => 'invalid_days'];
        }

        // Vérifier combien de lignes correspondent AVANT la suppression
        $checkSql = "SELECT COUNT(*) FROM pister WHERE date_creation < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$days]);
        $countBefore = (int) $checkStmt->fetchColumn();
        error_log('[CLEANUP_DEBUG] jours=' . $days . ', lignes correspondant AVANT DELETE=' . $countBefore);

        $sql = "DELETE FROM pister WHERE date_creation < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $deletedCount = $stmt->rowCount();
        error_log('[CLEANUP_DEBUG] DELETE exécuté, lignes supprimées=' . $deletedCount);

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

        $sql = "DELETE FROM pister WHERE id_piste = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$logId]);

        if ($stmt->rowCount() > 0) {
            $this->auditLog->logAction($idUtilisateur, 'Suppression', 'pister', 'Succès');
            return ['success' => true, 'message' => 'log_deleted'];
        }

        return ['success' => false, 'message' => 'log_not_found'];
    }

    /**
     * Récupérer la liste distincte des contextes dans les logs.
     */
    public function getTablesList()
    {
        $sql = "SELECT DISTINCT contexte FROM pister ORDER BY contexte";
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
     * Extraire les filtres de l'historique utilisateur (écran profil).
     */
    public function extractUserHistoryFilters(array $params)
    {
        return [
            'date_debut' => trim((string) ($params['history_date_debut'] ?? '')),
            'date_fin' => trim((string) ($params['history_date_fin'] ?? '')),
            'statut' => trim((string) ($params['history_statut'] ?? '')),
            'search' => trim((string) ($params['history_search'] ?? '')),
        ];
    }

    /**
     * Récupérer l'historique d'audit d'un utilisateur connecté avec pagination.
     */
    public function getUserAuditHistory($userId, array $filters, $offset, $limit)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(100, (int) $limit));
        $offset = max(0, (int) $offset);

        $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE p.id_utilisateur = ?";
        $params = [$userId];

        $this->applyUserHistoryFilters($sql, $params, $filters);

        $sql .= " ORDER BY p.date_creation DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les lignes d'historique d'un utilisateur connecté.
     */
    public function getTotalUserAuditHistory($userId, array $filters)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*)
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE p.id_utilisateur = ?";
        $params = [$userId];

        $this->applyUserHistoryFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Journaliser l'action HTTP courante dans la piste d'audit.
     * Le format est compact pour respecter les tailles existantes.
     *
     * @param bool $hasError Passer true si une erreur applicative a été détectée avant l'appel.
     */
    public function logRequestActivity($userId, array $get, array $post, $method, bool $hasError = false)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        $page = trim((string) ($get['page'] ?? ''));
        if ($page === '') {
            return false;
        }

        $method = strtoupper(trim((string) $method));
        if ($method === '') {
            $method = 'GET';
        }

        $statut = $hasError ? 'Erreur' : 'Succès';

        $actionToken = $this->resolveRequestActionToken($get, $post, $method);
        $tabToken = trim((string) ($get['tab'] ?? ''));
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $standardPayload = $this->resolveStandardAuditPayload($page, $actionToken, $method, $get);
        if (is_array($standardPayload)) {
            $actionLabel = $this->truncateForColumn((string) ($standardPayload['action'] ?? ''), 120);
            $contextLabel = $this->truncateForColumn((string) ($standardPayload['contexte'] ?? ''), 100);
            return $this->auditLog->logAction($userId, $actionLabel, $contextLabel, $statut);
        }

        // Fallback: journalisation compacte technique pour conserver la traçabilité fine.
        $actionParts = [$method, $page];
        if ($actionToken !== '') {
            $actionParts[] = 'act:' . $actionToken;
        }
        if ($tabToken !== '') {
            $actionParts[] = 'tab:' . $tabToken;
        }
        $actionLabel = $this->truncateForColumn(implode(' | ', $actionParts), 120);

        $contextParts = [$isAjax ? 'xhr' : 'ui', 'page=' . $page];
        if ($actionToken !== '') {
            $contextParts[] = 'action=' . $actionToken;
        }
        if ($tabToken !== '') {
            $contextParts[] = 'tab=' . $tabToken;
        }
        $contextLabel = $this->truncateForColumn(implode(' | ', $contextParts), 100);

        return $this->auditLog->logAction($userId, $actionLabel, $contextLabel, $statut);
    }

    /**
     * Déterminer une action d'audit normalisée alignée sur le PRD.
     *
     * @return array{action:string,contexte:string}|null
     */
    private function resolveStandardAuditPayload($page, $actionToken, $method, array $get)
    {
        $page = strtolower(trim((string) $page));
        $actionToken = strtolower(trim((string) $actionToken));
        $method = strtoupper(trim((string) $method));

        $actionFromGet = strtolower(trim((string) ($get['action'] ?? '')));
        $modalAction = strtolower(trim((string) ($get['modalAction'] ?? '')));
        if ($actionToken === '' && $actionFromGet !== '') {
            $actionToken = $actionFromGet;
        }
        if ($actionToken === '' && $modalAction !== '') {
            $actionToken = $modalAction;
        }

        $isArchivePage = $this->isArchivePage($page);
        $targetTable = $this->resolveAuditTableFromPage($page);

        $isExport = $actionToken !== '' && (str_contains($actionToken, 'export') || str_contains($actionToken, 'download'));
        $isPrint = $actionToken !== '' && (str_contains($actionToken, 'imprimer') || str_contains($actionToken, 'print'));
        $isCloseYear = $actionToken !== '' && (str_contains($actionToken, 'cloture') || str_contains($actionToken, 'close'))
            && (str_contains($actionToken, 'annee') || $page === 'parametres_generaux');

        if ($isCloseYear) {
            return ['action' => 'Clôture année', 'contexte' => 'annee_academique'];
        }

        if ($isExport) {
            return ['action' => 'Exportation', 'contexte' => 'exports_conformite'];
        }

        if ($isPrint) {
            return ['action' => 'Impression', 'contexte' => $targetTable];
        }

        if ($method === 'GET' && $isArchivePage) {
            return ['action' => 'Consultation archive', 'contexte' => 'archives_documents'];
        }

        if (($isArchivePage && $method === 'POST') || str_contains($actionToken, 'archive')) {
            return ['action' => 'Archivage', 'contexte' => 'archives_documents'];
        }

        if ($actionToken === 'valider') {
            return ['action' => 'Validation', 'contexte' => 'valider'];
        }

        if ($actionToken === 'rejeter') {
            return ['action' => 'Rejet', 'contexte' => 'valider'];
        }

        if ($actionToken !== '' && str_contains($actionToken, 'eval')) {
            return ['action' => 'Evaluation', 'contexte' => 'evaluer'];
        }

        if ($actionToken !== '' && (str_contains($actionToken, 'depot') || str_contains($actionToken, 'deposer'))) {
            return ['action' => 'Dépôt', 'contexte' => 'deposer'];
        }

        if ($actionToken !== '' && (str_contains($actionToken, 'add') || str_contains($actionToken, 'create'))) {
            return ['action' => 'Création', 'contexte' => $targetTable];
        }

        if (
            $actionToken !== ''
            && (str_contains($actionToken, 'delete') || str_contains($actionToken, 'remove') || str_contains($actionToken, 'supp'))
        ) {
            return ['action' => 'Suppression', 'contexte' => $targetTable];
        }

        // Un POST sans token reconnu est une modification certaine.
        // Un token non vide non reconnu → fallback compact pour préserver la traçabilité fine.
        if ($method === 'POST' && $actionToken === '') {
            return ['action' => 'Modification', 'contexte' => $targetTable];
        }

        return null;
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
            $sql .= " AND p.contexte = ?";
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
            $sql .= " AND (p.action LIKE ? OR p.contexte LIKE ? OR p.statut_action LIKE ? OR u.login_utilisateur LIKE ? OR u.nom_utilisateur LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
    }

    /**
     * Appliquer les filtres de l'historique utilisateur (profil).
     */
    private function applyUserHistoryFilters(&$sql, &$params, array $filters)
    {
        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(p.date_creation) >= ?";
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(p.date_creation) <= ?";
            $params[] = $filters['date_fin'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut_action = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.action LIKE ? OR p.contexte LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
    }

    /**
     * Résoudre un token d'action compact à partir de la requête courante.
     */
    private function resolveRequestActionToken(array $get, array $post, $method)
    {
        if (isset($get['action']) && trim((string) $get['action']) !== '') {
            return trim((string) $get['action']);
        }

        if (isset($post['action']) && trim((string) $post['action']) !== '') {
            return trim((string) $post['action']);
        }

        if (isset($get['modalAction']) && trim((string) $get['modalAction']) !== '') {
            return trim((string) $get['modalAction']);
        }

        if (strtoupper((string) $method) !== 'POST') {
            return '';
        }

        $markers = [
            'btn_add_utilisateur' => 'btn_add_utilisateur',
            'btn_add_multiple' => 'btn_add_multiple',
            'btn_modifier_utilisateur' => 'btn_modifier_utilisateur',
            'submit_enable_multiple' => 'submit_enable_multiple',
            'submit_disable_multiple' => 'submit_disable_multiple',
            'submit_send_access' => 'submit_send_access',
            'submit_add_etudiant' => 'submit_add_etudiant',
            'submit_modifier_etudiant' => 'submit_modifier_etudiant',
            'btn_add_enseignant' => 'btn_add_enseignant',
            'btn_modifier_enseignant' => 'btn_modifier_enseignant',
            'btn_add_pers_admin' => 'btn_add_pers_admin',
            'btn_modifier_pers_admin' => 'btn_modifier_pers_admin',
            'submit_delete_multiple' => 'submit_delete_multiple',
            'btn_enregistrer_notes' => 'btn_enregistrer_notes',
            'update_email' => 'update_email',
            'update_password' => 'update_password',
            'valider' => 'valider',
            'rejeter' => 'rejeter',
        ];

        foreach ($markers as $key => $value) {
            if (array_key_exists($key, $post)) {
                return $value;
            }
        }

        if (isset($post['selected_ids'])) {
            return 'selected_ids';
        }

        if (isset($post['nouveau_statut'])) {
            return 'repondre_reclamation';
        }

        return '';
    }

    private function isArchivePage($page)
    {
        $page = strtolower(trim((string) $page));
        if ($page === '') {
            return false;
        }

        if (str_starts_with($page, 'archives_')) {
            return true;
        }

        return in_array($page, [
            'admin_historique',
            'hub_historique',
            'archive_comptes_rendus',
            'archive_documents',
            'archive_history',
            'parcours_etudiant',
            'fiche_etudiant_archive',
            'fiche_soutenance',
        ], true);
    }

    private function resolveAuditTableFromPage($page)
    {
        $page = strtolower(trim((string) $page));

        $map = [
            'profil' => 'utilisateur',
            'gestion_utilisateurs' => 'utilisateur',
            'piste_audit' => 'pister',
            'gestion_scolarite' => 'inscriptions',
            'gestion_etudiants' => 'inscriptions',
            'gestion_notes_evaluations' => 'notes',
            'evaluation_dossiers' => 'evaluer',
            'evaluation_soutenance' => 'evaluer',
            'redaction_compte_rendu' => 'compte_rendu',
            'consultation_cr_etud' => 'compte_rendu',
            'dashboard' => 'tableau_de_bord',
            'dashboard_commission' => 'tableau_de_bord',
            'dashboard_enseignant' => 'tableau_de_bord',
            'dashboard_scolarite' => 'tableau_de_bord',
        ];

        if (isset($map[$page])) {
            return $map[$page];
        }

        if ($this->isArchivePage($page)) {
            return 'archives_documents';
        }

        return $page !== '' ? $page : 'systeme';
    }

    /**
     * Tronquer une chaîne pour respecter une colonne SQL fixe.
     */
    private function truncateForColumn($value, $maxLength)
    {
        $value = trim((string) preg_replace('/\s+/', ' ', (string) $value));
        $maxLength = max(1, (int) $maxLength);

        if ($value === '') {
            return '-';
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        if ($maxLength <= 3) {
            return substr($value, 0, $maxLength);
        }

        return rtrim(substr($value, 0, $maxLength - 3)) . '...';
    }
}
