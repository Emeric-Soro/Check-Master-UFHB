<?php

namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/MiseEnLigneMemoireService.php';
require_once __DIR__ . '/../utils/AcademicYear.php';
require_once __DIR__ . '/../Security/PermissionRegistry.php';

use PDO;
use Exception;
use CheckMaster\Security\PermissionRegistry;

class ValidationMemoireService
{
    private const TABLE_EVALUATIONS_MEMOIRES = 'evaluations_memoires';

    private PDO $pdo;
    private \MiseEnLigneMemoireService $memoireService;

    /** @var array<string,bool> */
    private array $tableExistsCache = [];

    /** @var array<string,bool> */
    private array $columnExistsCache = [];

    /** @var array<string,int|null> */
    private array $rapportCache = [];

    /** @var array<string,array<string,array<string,string>>> */
    private array $encadrementCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->memoireService = new \MiseEnLigneMemoireService($pdo);
    }

    private function ensureEvaluationsMemoireTable(): bool
    {
        if ($this->tableExists(self::TABLE_EVALUATIONS_MEMOIRES)) {
            return true;
        }

        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS " . self::TABLE_EVALUATIONS_MEMOIRES . " (
                    id_evaluation INT NOT NULL AUTO_INCREMENT,
                    id_document INT NOT NULL,
                    id_rapport INT NULL,
                    id_evaluateur INT NOT NULL,
                    type_evaluateur ENUM('encadrant', 'directeur', 'responsable_filiere') NOT NULL,
                    decision ENUM('valider', 'rejeter') NOT NULL,
                    commentaire TEXT NULL,
                    date_evaluation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id_evaluation),
                    UNIQUE KEY uq_eval_memoire_document_role (id_document, type_evaluateur),
                    KEY idx_eval_memoire_document (id_document),
                    KEY idx_eval_memoire_rapport (id_rapport),
                    KEY idx_eval_memoire_evaluateur (id_evaluateur)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->tableExistsCache[self::TABLE_EVALUATIONS_MEMOIRES] = true;
            return true;
        } catch (Exception $e) {
            error_log('[ValidationMemoireService] ensure table failed: ' . $e->getMessage());
            $this->tableExistsCache[self::TABLE_EVALUATIONS_MEMOIRES] = false;
            return false;
        }
    }

    private function tableExists(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Exception) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $key = strtolower($tableName . '.' . $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }
        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (Exception) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function normalizeSqlExpr(string $expr): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE($expr, '')), ' ', ''), '-', ''), '''', ''), '.', ''))";
    }

    private function enseignantResolutionSubquery(string $userAlias = 'u'): string
    {
        $userLoginExpr = "LOWER(COALESCE({$userAlias}.login_utilisateur, ''))";
        $userNameExpr = $this->normalizeSqlExpr($userAlias . '.nom_utilisateur');
        $teacherForwardExpr = $this->normalizeSqlExpr("CONCAT(COALESCE(e2.nom_enseignant, ''), COALESCE(e2.prenom_enseignant, ''))");
        $teacherReverseExpr = $this->normalizeSqlExpr("CONCAT(COALESCE(e2.prenom_enseignant, ''), COALESCE(e2.nom_enseignant, ''))");
        $emailMatch = "({$userLoginExpr} <> '' AND LOWER(COALESCE(e2.mail_enseignant, '')) = {$userLoginExpr})";
        $nameMatch = "({$userNameExpr} <> '' AND ({$userNameExpr} = {$teacherForwardExpr} OR {$userNameExpr} = {$teacherReverseExpr}))";

        return "
            (
                SELECT e2.id_enseignant
                FROM enseignants e2
                WHERE {$emailMatch} OR {$nameMatch}
                ORDER BY CASE WHEN {$emailMatch} THEN 0 ELSE 1 END, e2.id_enseignant
                LIMIT 1
            )
        ";
    }

    private function findEnseignantIdByUtilisateurId(int $idUtilisateur): ?string
    {
        if ($idUtilisateur <= 0 || !$this->tableExists('utilisateur')) {
            return null;
        }
        try {
            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("\n                SELECT {$enseignantResolution} AS id_enseignant\n                FROM utilisateur u\n                WHERE u.id_utilisateur = ?\n                LIMIT 1\n            ");
            $stmt->execute([$idUtilisateur]);
            $value = $stmt->fetchColumn();
            return $value !== false ? trim((string) $value) : null;
        } catch (Exception $e) {
            error_log('[ValidationMemoireService] resolution enseignant: ' . $e->getMessage());
            return null;
        }
    }

    private function findEnseignantIdByLogin(string $login): ?string
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }
        try {
            $stmt = $this->pdo->prepare('SELECT id_enseignant FROM enseignants WHERE LOWER(mail_enseignant) = LOWER(?) LIMIT 1');
            $stmt->execute([$login]);
            $value = $stmt->fetchColumn();
            return $value !== false ? trim((string) $value) : null;
        } catch (Exception $e) {
            error_log('[ValidationMemoireService] resolution enseignant login: ' . $e->getMessage());
            return null;
        }
    }

    private function resolveEnseignantIdFromSession(array $session): ?string
    {
        foreach (['id_enseignant', 'enseignant_id'] as $key) {
            $candidate = trim((string) ($session[$key] ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $byLogin = $this->findEnseignantIdByLogin((string) ($session['login_utilisateur'] ?? ''));
        if ($byLogin !== null && $byLogin !== '') {
            return $byLogin;
        }

        $byUserId = $this->findEnseignantIdByUtilisateurId((int) ($session['id_utilisateur'] ?? 0));
        if ($byUserId !== null && $byUserId !== '') {
            return $byUserId;
        }

        return null;
    }

    private function getAdminLikeGroupIds(): array
    {
        $groups = PermissionRegistry::groups();
        $ids = [];
        if (isset($groups['administrateur'])) {
            $ids[] = (int) $groups['administrateur'];
        }
        if (isset($groups['admin_responsable_filiere'])) {
            $ids[] = (int) $groups['admin_responsable_filiere'];
        }

        return array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
    }

    private function isResponsableFiliereGroup(int $groupId): bool
    {
        $groups = PermissionRegistry::groups();
        $responsableId = (int) ($groups['responsable_filiere'] ?? 0);
        return $responsableId > 0 && $groupId === $responsableId;
    }

    private function resolveRapportDateColumn(): ?string
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return 'date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return 'date_redaction_rapport';
        }
        return null;
    }

    private function resolveLatestRapportId(string $numCarte, string $numIdent): ?int
    {
        $key = $numCarte . '|' . $numIdent;
        if (array_key_exists($key, $this->rapportCache)) {
            return $this->rapportCache[$key];
        }
        if (!$this->tableExists('rapport_etudiants')) {
            $this->rapportCache[$key] = null;
            return null;
        }

        $orderColumn = $this->resolveRapportDateColumn();
        $orderBy = $orderColumn !== null ? "ORDER BY r.{$orderColumn} DESC, r.id_rapport DESC" : 'ORDER BY r.id_rapport DESC';

        try {
            $stmt = $this->pdo->prepare("\n                SELECT r.id_rapport\n                FROM rapport_etudiants r\n                WHERE r.num_etu = ? OR r.num_etu = ?\n                {$orderBy}\n                LIMIT 1\n            ");
            $stmt->execute([$numCarte, $numIdent]);
            $value = $stmt->fetchColumn();
            $rapportId = $value !== false ? (int) $value : null;
            $this->rapportCache[$key] = $rapportId;
            return $rapportId;
        } catch (Exception $e) {
            error_log('[ValidationMemoireService] rapport resolution failed: ' . $e->getMessage());
            $this->rapportCache[$key] = null;
            return null;
        }
    }

    private function resolveEncadrement(?int $rapportId, string $numSoutenance): array
    {
        $cacheKey = ($rapportId !== null ? (string) $rapportId : 'none') . '|' . $numSoutenance;
        if (array_key_exists($cacheKey, $this->encadrementCache)) {
            return $this->encadrementCache[$cacheKey];
        }

        $roles = [];

        if ($rapportId !== null && $rapportId > 0 && $this->tableExists('affecter')) {
            $stmt = $this->pdo->prepare("\n                SELECT a.role, a.id_enseignant,\n                       CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')) AS nom,\n                       e.mail_enseignant AS email\n                FROM affecter a\n                JOIN enseignants e ON e.id_enseignant = a.id_enseignant\n                WHERE a.id_rapport = ?\n            ");
            $stmt->execute([$rapportId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $role = strtolower(trim((string) ($row['role'] ?? '')));
                if ($role === 'directeur' || $role === 'encadrant') {
                    $roles[$role] = [
                        'id_enseignant' => (string) ($row['id_enseignant'] ?? ''),
                        'nom' => trim((string) ($row['nom'] ?? '')),
                        'email' => (string) ($row['email'] ?? ''),
                    ];
                }
            }
        }

        if (
            (!isset($roles['directeur']) || !isset($roles['encadrant']))
            && $numSoutenance !== ''
            && $this->tableExists('enseignant_jury')
            && $this->tableExists('qualite_jury')
        ) {
            $stmt = $this->pdo->prepare("\n                SELECT ej.id_enseignant, qj.lib_role,\n                       CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')) AS nom,\n                       e.mail_enseignant AS email\n                FROM enseignant_jury ej\n                JOIN qualite_jury qj ON ej.id_qualite_jury = qj.id_role_jury\n                JOIN enseignants e ON ej.id_enseignant = e.id_enseignant\n                WHERE ej.num_soutenance = ?\n            ");
            $stmt->execute([$numSoutenance]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $roleRaw = strtoupper(trim((string) ($row['lib_role'] ?? '')));
                $resolvedRole = null;
                if (str_contains($roleRaw, 'DIRECT')) {
                    $resolvedRole = 'directeur';
                } elseif (str_contains($roleRaw, 'ENCADR')) {
                    $resolvedRole = 'encadrant';
                }
                if ($resolvedRole !== null && !isset($roles[$resolvedRole])) {
                    $roles[$resolvedRole] = [
                        'id_enseignant' => (string) ($row['id_enseignant'] ?? ''),
                        'nom' => trim((string) ($row['nom'] ?? '')),
                        'email' => (string) ($row['email'] ?? ''),
                    ];
                }
            }
        }

        $this->encadrementCache[$cacheKey] = $roles;
        return $roles;
    }

    private function resolveRoleForEnseignant(?string $enseignantId, array $encadrement): ?string
    {
        $enseignantId = trim((string) $enseignantId);
        if ($enseignantId === '') {
            return null;
        }
        if (
            !empty($encadrement['directeur']['id_enseignant'])
            && (string) $encadrement['directeur']['id_enseignant'] === $enseignantId
        ) {
            return 'directeur';
        }
        if (
            !empty($encadrement['encadrant']['id_enseignant'])
            && (string) $encadrement['encadrant']['id_enseignant'] === $enseignantId
        ) {
            return 'encadrant';
        }
        return null;
    }

    private function getEvaluationsForMemoire(int $documentId): array
    {
        if ($documentId <= 0 || !$this->ensureEvaluationsMemoireTable()) {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare("\n                SELECT e.*,\n                       COALESCE(ens.nom_enseignant, u.nom_utilisateur) AS nom_evaluateur,\n                       COALESCE(ens.prenom_enseignant, '') AS prenom_evaluateur\n                FROM evaluations_memoires e\n                LEFT JOIN utilisateur u ON e.id_evaluateur = u.id_utilisateur\n                LEFT JOIN enseignants ens ON LOWER(ens.mail_enseignant) = LOWER(u.login_utilisateur)\n                WHERE e.id_document = ?\n                ORDER BY COALESCE(e.date_modification, e.date_evaluation) DESC\n            ");
            $stmt->execute([$documentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[ValidationMemoireService] evaluations fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function computeMemoireStatus(array $evaluations): string
    {
        if (empty($evaluations)) {
            return 'en_attente';
        }

        $requiredRoles = ['directeur', 'encadrant', 'responsable_filiere'];
        $decisions = [];
        $hasReject = false;

        foreach ($evaluations as $evaluation) {
            $role = strtolower(trim((string) ($evaluation['type_evaluateur'] ?? '')));
            $decision = strtolower(trim((string) ($evaluation['decision'] ?? '')));
            if ($role === '') {
                continue;
            }
            $decisions[$role] = $decision;
            if ($decision === 'rejeter') {
                $hasReject = true;
            }
        }

        if ($hasReject) {
            return 'rejete';
        }

        $allValidated = true;
        foreach ($requiredRoles as $role) {
            if (($decisions[$role] ?? '') !== 'valider') {
                $allValidated = false;
                break;
            }
        }

        return $allValidated ? 'valide' : 'en_cours';
    }

    private function getMemoireById(int $documentId): ?array
    {
        if ($documentId <= 0) {
            return null;
        }

        foreach ($this->memoireService->getMemoiresEnLigne() as $memoire) {
            if ((int) ($memoire['id_document'] ?? 0) === $documentId) {
                return $memoire;
            }
        }

        return null;
    }

    public function getIndexData(array $session): array
    {
        $this->ensureEvaluationsMemoireTable();

        $memoires = [];
        $stats = [
            'total' => 0,
            'en_attente' => 0,
            'en_cours' => 0,
            'valides' => 0,
            'rejetes' => 0,
        ];

        $groupId = (int) ($session['id_GU'] ?? 0);
        $isAdminLike = in_array($groupId, $this->getAdminLikeGroupIds(), true);
        $isResponsable = $this->isResponsableFiliereGroup($groupId) || $isAdminLike;
        $enseignantId = $isResponsable ? null : $this->resolveEnseignantIdFromSession($session);

        foreach ($this->memoireService->getMemoiresEnLigne() as $memoire) {
            $numCarte = trim((string) ($memoire['num_carte_etud'] ?? ''));
            $numIdent = trim((string) ($memoire['num_ident_etud'] ?? ''));
            $rapportId = $this->resolveLatestRapportId($numCarte, $numIdent);
            $numSoutenance = trim((string) ($memoire['num_soutenance'] ?? ''));
            $encadrement = $this->resolveEncadrement($rapportId, $numSoutenance);

            $role = $isResponsable ? 'responsable_filiere' : $this->resolveRoleForEnseignant($enseignantId, $encadrement);
            if (!$isResponsable && $role === null) {
                continue;
            }

            $documentId = (int) ($memoire['id_document'] ?? 0);
            $evaluations = $this->getEvaluationsForMemoire($documentId);
            $statut = $this->computeMemoireStatus($evaluations);

            $memoire['rapport_id'] = $rapportId;
            $memoire['encadrement'] = $encadrement;
            $memoire['evaluations'] = $evaluations;
            $memoire['statut'] = $statut;
            $memoire['role_utilisateur'] = $role;

            $memoires[] = $memoire;
            $stats['total']++;
            if ($statut === 'valide') {
                $stats['valides']++;
            } elseif ($statut === 'rejete') {
                $stats['rejetes']++;
            } elseif ($statut === 'en_cours') {
                $stats['en_cours']++;
            } else {
                $stats['en_attente']++;
            }
        }

        return ['stats' => $stats, 'memoires' => $memoires];
    }

    public function enregistrerDecision(array $post, array $session): array
    {
        if (!$this->ensureEvaluationsMemoireTable()) {
            return ['success' => false, 'message' => 'Le module de validation des memoires est indisponible.'];
        }

        $documentId = (int) ($post['id_document'] ?? 0);
        $decision = strtolower(trim((string) ($post['decision'] ?? '')));
        $commentaire = trim((string) ($post['commentaire'] ?? ''));
        $idUtilisateur = (int) ($session['id_utilisateur'] ?? 0);

        if ($documentId <= 0) {
            return ['success' => false, 'message' => 'Memoire introuvable.'];
        }
        if ($idUtilisateur <= 0) {
            return ['success' => false, 'message' => 'Utilisateur non identifie.'];
        }
        if (!in_array($decision, ['valider', 'rejeter'], true)) {
            return ['success' => false, 'message' => 'Decision invalide.'];
        }

        $memoire = $this->getMemoireById($documentId);
        if ($memoire === null) {
            return ['success' => false, 'message' => 'Memoire introuvable ou non actif.'];
        }

        $numCarte = trim((string) ($memoire['num_carte_etud'] ?? ''));
        $numIdent = trim((string) ($memoire['num_ident_etud'] ?? ''));
        $rapportId = $this->resolveLatestRapportId($numCarte, $numIdent);
        $numSoutenance = trim((string) ($memoire['num_soutenance'] ?? ''));
        $encadrement = $this->resolveEncadrement($rapportId, $numSoutenance);

        $groupId = (int) ($session['id_GU'] ?? 0);
        $isAdminLike = in_array($groupId, $this->getAdminLikeGroupIds(), true);
        $role = null;
        if ($this->isResponsableFiliereGroup($groupId) || $isAdminLike) {
            $role = 'responsable_filiere';
        } else {
            $role = $this->resolveRoleForEnseignant($this->resolveEnseignantIdFromSession($session), $encadrement);
        }

        if ($role === null) {
            return ['success' => false, 'message' => 'Vous n\'etes pas autorise a valider ce memoire.'];
        }

        try {
            $stmt = $this->pdo->prepare("\n                INSERT INTO evaluations_memoires\n                    (id_document, id_rapport, id_evaluateur, type_evaluateur, decision, commentaire, date_evaluation, date_modification)\n                VALUES\n                    (:id_document, :id_rapport, :id_evaluateur, :type_evaluateur, :decision, :commentaire, NOW(), NULL)\n                ON DUPLICATE KEY UPDATE\n                    decision = VALUES(decision),\n                    commentaire = VALUES(commentaire),\n                    date_modification = NOW()\n            ");
            $stmt->bindValue(':id_document', $documentId, PDO::PARAM_INT);
            if ($rapportId !== null && $rapportId > 0) {
                $stmt->bindValue(':id_rapport', $rapportId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':id_rapport', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':id_evaluateur', $idUtilisateur, PDO::PARAM_INT);
            $stmt->bindValue(':type_evaluateur', $role, PDO::PARAM_STR);
            $stmt->bindValue(':decision', $decision, PDO::PARAM_STR);
            $stmt->bindValue(':commentaire', $commentaire !== '' ? $commentaire : null, $commentaire !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
        } catch (Exception $e) {
            error_log('[ValidationMemoireService] decision insert failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la sauvegarde de votre decision.'];
        }

        $label = $decision === 'valider' ? 'valide' : 'rejete';
        return ['success' => true, 'message' => 'Decision enregistree: memoire ' . $label . '.'];
    }
}
