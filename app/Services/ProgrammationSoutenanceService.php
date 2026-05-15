<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use Exception;
use PDO;

class ProgrammationSoutenanceService
{
    private $pdo;
    private $tableExistsCache = [];
    private $columnExistsCache = [];
    private $roleIdsCache = null;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
    }

    private function getSelectedAcademicYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function ensureWritableContext(string $context): void
    {
        $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $this->getSelectedAcademicYearId(), $context);
        if (!$writeGuard['success']) {
            throw new Exception($writeGuard['message']);
        }
    }

    private function getStudentAcademicYearId(string $studentId): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                " . $this->latestCandidatureJoin('r', 'e', 'cs') . "
                LEFT JOIN valider v ON v.id_rapport = r.id_rapport
                WHERE (e.num_carte_etud = ? OR e.num_ident_etud = ?)
                  AND v.decision_validation = 'valider'
                  " . $this->validatedCandidatureWhere('cs') . "
                ORDER BY COALESCE(d.date_depot, " . $this->rapportDateExpr('r') . ") DESC, r.id_rapport DESC
                LIMIT 1
            ");
            $stmt->execute([$studentId, $studentId]);
            $value = $stmt->fetchColumn();
            return is_numeric($value) ? (int) $value : null;
        } catch (Exception $e) {
            error_log('Erreur getStudentAcademicYearId: ' . $e->getMessage());
            return null;
        }
    }

    private function ensureWritableStudent(string $studentId, string $context): void
    {
        $targetYearId = $this->getStudentAcademicYearId($studentId);
        $selectedYearId = $this->getSelectedAcademicYearId();

        if ($selectedYearId !== null && $targetYearId !== null && $selectedYearId !== $targetYearId) {
            throw new Exception("L'étudiant ne correspond pas à l'année académique actuellement sélectionnée.");
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $targetYearId, $context);
        if (!$writeGuard['success']) {
            throw new Exception($writeGuard['message']);
        }
    }

    private function isStudentProgrammable(string $studentId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM rapport_etudiants r
                JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                LEFT JOIN valider v ON v.id_rapport = r.id_rapport
                " . $this->latestCandidatureJoin('r', 'e', 'cs') . "
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                WHERE (e.num_carte_etud = ? OR e.num_ident_etud = ?)
                  AND v.decision_validation = 'valider'
                  " . $this->validatedCandidatureWhere('cs') . "
            ");
            $stmt->execute([$studentId, $studentId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log('Erreur isStudentProgrammable: ' . $e->getMessage());
            return false;
        }
    }

    private function getAttributionAcademicYearId($id): ?int
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return null;
            }
            $idCol = $this->getProgrammationIdColumn($progTable);
            $yearExpr = $this->columnExists($progTable, 'id_annee_acad')
                ? 'p.id_annee_acad'
                : "COALESCE(" . $this->getFallbackStudentYearExpr('e') . ")";
            $stmt = $this->pdo->prepare("
                SELECT {$yearExpr}
                FROM {$progTable} p
                INNER JOIN etudiants e ON (p.num_etud = e.num_carte_etud OR p.num_etud = e.num_ident_etud)
                WHERE p.{$idCol} = ?
                LIMIT 1
            ");
            $stmt->execute([(string) $id]);
            $value = $stmt->fetchColumn();
            return is_numeric($value) ? (int) $value : null;
        } catch (Exception $e) {
            error_log('Erreur getAttributionAcademicYearId: ' . $e->getMessage());
            return null;
        }
    }

    private function ensureWritableAttribution($id, string $context): void
    {
        $targetYearId = $this->getAttributionAcademicYearId($id);
        $selectedYearId = $this->getSelectedAcademicYearId();

        if ($selectedYearId !== null && $targetYearId !== null && $selectedYearId !== $targetYearId) {
            throw new Exception("L'attribution ne correspond pas à l'année académique actuellement sélectionnée.");
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $targetYearId, $context);
        if (!$writeGuard['success']) {
            throw new Exception($writeGuard['message']);
        }
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists($tableName, $columnName)
    {
        $key = strtolower((string) $tableName . '.' . (string) $columnName);
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
        } catch (Exception $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function studentJoinCondition(string $studentAlias = 'e', string $programmationAlias = 'p'): string
    {
        $conditions = [
            "{$programmationAlias}.num_etud = {$studentAlias}.num_carte_etud",
        ];

        if ($this->columnExists('etudiants', 'num_ident_etud')) {
            $conditions[] = "{$programmationAlias}.num_etud = {$studentAlias}.num_ident_etud";
        }

        return implode(' OR ', $conditions);
    }

    private function studentIdentifierExpr(string $studentAlias = 'e', string $programmationAlias = 'p'): string
    {
        if ($this->columnExists('etudiants', 'num_ident_etud')) {
            return "COALESCE(NULLIF({$studentAlias}.num_carte_etud, ''), NULLIF({$studentAlias}.num_ident_etud, ''), {$programmationAlias}.num_etud)";
        }

        return "COALESCE(NULLIF({$studentAlias}.num_carte_etud, ''), {$programmationAlias}.num_etud)";
    }

    private function studentCarteExpr(string $studentAlias = 'e'): string
    {
        if ($this->columnExists('etudiants', 'num_ident_etud')) {
            return "COALESCE(NULLIF({$studentAlias}.num_carte_etud, ''), NULLIF({$studentAlias}.num_ident_etud, ''))";
        }

        return "{$studentAlias}.num_carte_etud";
    }

    private function rapportDateExpr(string $alias = 'r'): string
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return $alias . '.date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return $alias . '.date_redaction_rapport';
        }
        return 'NULL';
    }

    private function getFallbackStudentYearExpr(string $studentAlias = 'e'): string
    {
        return "
            (SELECT i.id_annee_acad
             FROM inscriptions i
             WHERE i.num_carte_etud = " . $this->studentCarteExpr($studentAlias) . "
             ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC
             LIMIT 1)
        ";
    }

    private function getAcademicYearFromDateExpr(string $dateExpr): string
    {
        return "
            (SELECT aa.id_annee_acad
             FROM annee_academique aa
             WHERE DATE($dateExpr) BETWEEN aa.date_deb AND aa.date_fin
             ORDER BY aa.date_deb DESC
             LIMIT 1)
        ";
    }

    private function getReportAcademicYearExpr(string $rapportAlias = 'r', string $studentAlias = 'e', ?string $depotAlias = null): string
    {
        $candidates = [];

        if ($depotAlias !== null && $this->tableExists('deposer')) {
            $candidates[] = $this->getAcademicYearFromDateExpr($depotAlias . '.date_depot');
        }

        $dateExpr = $this->rapportDateExpr($rapportAlias);
        if ($dateExpr !== 'NULL') {
            $candidates[] = $this->getAcademicYearFromDateExpr($dateExpr);
        }

        $candidates[] = $this->getFallbackStudentYearExpr($studentAlias);

        return 'COALESCE(' . implode(', ', $candidates) . ')';
    }

    private function latestCandidatureJoin(string $rapportAlias = 'r', string $studentAlias = 'e', string $candidatureAlias = 'cs'): string
    {
        if (!$this->tableExists('candidature_soutenance')) {
            return '';
        }

        return "
            LEFT JOIN candidature_soutenance {$candidatureAlias} ON {$candidatureAlias}.id_candidature = (
                SELECT cs2.id_candidature
                FROM candidature_soutenance cs2
                WHERE (
                    cs2.id_candidature = {$rapportAlias}.id_candidature
                    OR (
                        ({$rapportAlias}.id_candidature IS NULL OR {$rapportAlias}.id_candidature = 0)
                        AND (cs2.num_etu = {$studentAlias}.num_carte_etud OR cs2.num_etu = {$studentAlias}.num_ident_etud)
                    )
                )
                ORDER BY cs2.date_candidature DESC, cs2.id_candidature DESC
                LIMIT 1
            )
        ";
    }

    private function validatedCandidatureWhere(string $candidatureAlias = 'cs'): string
    {
        return $this->tableExists('candidature_soutenance')
            ? " AND {$candidatureAlias}.statut_candidature IN ('Validee', 'Validée')"
            : '';
    }

    private function getProgrammationTable()
    {
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
        }
        if ($this->tableExists('programmer')) {
            return 'programmer';
        }
        return null;
    }

    private function getProgrammationIdColumn($table = null)
    {
        $table = $table ?: $this->getProgrammationTable();
        return $table === 'programmer' ? 'id_programmation' : 'num_soutenance';
    }

    private function getProgrammationJuryColumn($table = null)
    {
        $table = $table ?: $this->getProgrammationTable();
        return $table === 'programmer' ? 'num_jury' : 'num_soutenance';
    }

    private function getJuryTable()
    {
        if ($this->tableExists('enseignant_jury')) {
            return 'enseignant_jury';
        }
        if ($this->tableExists('composer_jury')) {
            return 'composer_jury';
        }
        return null;
    }

    private function getJuryRefColumn($juryTable = null)
    {
        $juryTable = $juryTable ?: $this->getJuryTable();
        return $juryTable === 'composer_jury' ? 'num_jury' : 'num_soutenance';
    }

    private function getRolesTable()
    {
        if ($this->tableExists('qualite_jury')) {
            return 'qualite_jury';
        }
        if ($this->tableExists('roles_jury')) {
            return 'roles_jury';
        }
        return null;
    }

    private function normalizeRoleName($value)
    {
        $value = (string) $value;
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = $ascii !== false ? $ascii : $value;
        return strtolower(trim($normalized));
    }

    private function roleLabelMatches(string $label, array $needles): bool
    {
        $rawLabel = function_exists('mb_strtolower')
            ? mb_strtolower(trim($label), 'UTF-8')
            : strtolower(trim($label));
        $normalizedLabel = $this->normalizeRoleName($label);

        foreach ($needles as $needle) {
            $rawNeedle = function_exists('mb_strtolower')
                ? mb_strtolower($needle, 'UTF-8')
                : strtolower($needle);
            $normalizedNeedle = $this->normalizeRoleName($needle);

            if (
                strpos($rawLabel, $rawNeedle) !== false
                || strpos($normalizedLabel, $normalizedNeedle) !== false
            ) {
                return true;
            }
        }

        return false;
    }

    private function getRoleIds()
    {
        if (is_array($this->roleIdsCache)) {
            return $this->roleIdsCache;
        }

        $this->roleIdsCache = [];
        $rolesTable = $this->getRolesTable();
        if ($rolesTable === null) {
            return $this->roleIdsCache;
        }

        try {
            $sql = "SELECT id_role_jury, lib_role FROM {$rolesTable}";
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $id = $row['id_role_jury'] ?? '';
                if ($id === '') {
                    continue;
                }
                $label = (string) ($row['lib_role'] ?? '');
                if ($this->roleLabelMatches($label, ['president', 'président'])) {
                    $this->roleIdsCache['president'] = $id;
                } elseif ($this->roleLabelMatches($label, ['examinateur'])) {
                    $this->roleIdsCache['examinateur'] = $id;
                } elseif ($this->roleLabelMatches($label, ['directeur'])) {
                    $this->roleIdsCache['directeur'] = $id;
                } elseif ($this->roleLabelMatches($label, ['encadrant', 'encadreur', 'encadr'])) {
                    $this->roleIdsCache['encadreur'] = $id;
                } elseif ($this->roleLabelMatches($label, ['maitre', 'maître'])) {
                    $this->roleIdsCache['maitre_stage'] = $id;
                }
            }
        } catch (Exception $e) {
            $this->roleIdsCache = [];
        }

        return $this->roleIdsCache;
    }

    private function getDefaultId($table, $idColumn)
    {
        try {
            if (!$this->tableExists($table) || !$this->columnExists($table, $idColumn)) {
                return 1;
            }
            $stmt = $this->pdo->query("SELECT COALESCE(MIN($idColumn), 1) as first_id FROM {$table}");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return max(1, (int) ($row['first_id'] ?? 1));
        } catch (Exception $e) {
            return 1;
        }
    }

    private function juryIdExpr($roleKey, $progAlias = 'p')
    {
        $roleIds = $this->getRoleIds();
        $roleId = $roleIds[$roleKey] ?? '';
        $juryTable = $this->getJuryTable();
        $progTable = $this->getProgrammationTable();
        if ($roleId === '' || $juryTable === null || $progTable === null) {
            return 'NULL';
        }
        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $progJuryCol = $this->getProgrammationJuryColumn($progTable);
        $roleIdEscaped = $this->pdo->quote((string) $roleId);
        return "(SELECT cj.id_enseignant
                 FROM {$juryTable} cj
                 WHERE cj.{$juryRefCol} = {$progAlias}.{$progJuryCol}
                 AND cj.id_qualite_jury = {$roleIdEscaped}
                 LIMIT 1)";
    }

    private function juryNameExpr($roleKey, $progAlias = 'p')
    {
        $roleIds = $this->getRoleIds();
        $roleId = $roleIds[$roleKey] ?? '';
        $juryTable = $this->getJuryTable();
        $progTable = $this->getProgrammationTable();
        if ($roleId === '' || $juryTable === null || $progTable === null) {
            return 'NULL';
        }
        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $progJuryCol = $this->getProgrammationJuryColumn($progTable);
        $roleIdEscaped = $this->pdo->quote((string) $roleId);
        return "(SELECT CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant)
                 FROM {$juryTable} cj
                 JOIN enseignants ens ON cj.id_enseignant = ens.id_enseignant
                 WHERE cj.{$juryRefCol} = {$progAlias}.{$progJuryCol}
                 AND cj.id_qualite_jury = {$roleIdEscaped}
                 LIMIT 1)";
    }

    private function normalizeMaitreStageName($value): string
    {
        $name = trim((string) $value);
        if ($name === '') {
            return '';
        }

        // Evite les prefixes de civilite en double dans les vues/PDF.
        $name = trim((string) preg_replace('/^\s*M\.\s*/u', '', $name));
        $name = (string) preg_replace('/\s+/u', ' ', $name);
        $upper = strtoupper($name);

        $invalidLabels = [
            'STAGE MAITRE',
            'MAITRE STAGE',
            'NON RENSEIGNE',
            'NON RENSEIGNER',
            'N/A',
            'NA',
            '-',
        ];

        return in_array($upper, $invalidLabels, true) ? '' : $name;
    }

    /**
     * Récupérer tous les étudiants avec rapport validé (pour affichage et modification)
     */
    public function getEtudiantsForView(): array
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return [];
            }
            $selectedYearId = $this->getSelectedAcademicYearId();

            $sql = "
                SELECT DISTINCT
                    " . $this->studentCarteExpr('e') . " as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    " . $this->studentCarteExpr('e') . " as matricule_etudiant,
                    e.email_etu as email_etudiant,
                    e.promotion_etu,
                    e.promotion_etu as lib_specialite,
                    r.theme_rapport,
                    " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " AS id_annee_acad,
                    ist.id_maitre_stage,
                    CONCAT(ms.prenom, ' ', ms.Nom) as maitre_stage_nom,
                    ms.email as maitre_stage_email,
                    (SELECT CONCAT(ens_dir.prenom_enseignant, ' ', ens_dir.nom_enseignant)
                     FROM affecter af_dir
                     JOIN enseignants ens_dir ON af_dir.id_enseignant = ens_dir.id_enseignant
                     WHERE af_dir.id_rapport = r.id_rapport
                     AND LOWER(af_dir.role) LIKE 'directeur%'
                     LIMIT 1) as directeur_nom,
                    (SELECT ens_dir.id_enseignant
                     FROM affecter af_dir
                     JOIN enseignants ens_dir ON af_dir.id_enseignant = ens_dir.id_enseignant
                     WHERE af_dir.id_rapport = r.id_rapport
                     AND LOWER(af_dir.role) LIKE 'directeur%'
                     LIMIT 1) as directeur_id,
                    (SELECT CONCAT(ens_enc.prenom_enseignant, ' ', ens_enc.nom_enseignant)
                     FROM affecter af_enc
                     JOIN enseignants ens_enc ON af_enc.id_enseignant = ens_enc.id_enseignant
                     WHERE af_enc.id_rapport = r.id_rapport
                     AND LOWER(af_enc.role) LIKE 'encadr%'
                     LIMIT 1) as encadreur_nom,
                    (SELECT ens_enc.id_enseignant
                     FROM affecter af_enc
                     JOIN enseignants ens_enc ON af_enc.id_enseignant = ens_enc.id_enseignant
                     WHERE af_enc.id_rapport = r.id_rapport
                     AND LOWER(af_enc.role) LIKE 'encadr%'
                     LIMIT 1) as encadreur_id,
                    CASE
                        WHEN p.num_etud IS NOT NULL THEN 'programmed'
                        ELSE 'available'
                    END as statut_programmation
                FROM etudiants e
                INNER JOIN rapport_etudiants r ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                " . $this->latestCandidatureJoin('r', 'e', 'cs') . "
                LEFT JOIN informations_stage ist ON (e.num_carte_etud = ist.num_etu OR e.num_ident_etud = ist.num_etu)
                LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
                LEFT JOIN {$progTable} p ON (e.num_carte_etud = p.num_etud OR e.num_ident_etud = p.num_etud)
                WHERE v.decision_validation = 'valider'
                " . $this->validatedCandidatureWhere('cs') . "
            ";

            if ($selectedYearId !== null && $selectedYearId > 0) {
                $sql .= " AND " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " = :id_annee_acad";
            }

            $sql .= " ORDER BY e.nom_etu, e.prenom_etu";

            $stmt = $this->pdo->prepare($sql);
            if ($selectedYearId !== null && $selectedYearId > 0) {
                $stmt->bindValue(':id_annee_acad', $selectedYearId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['maitre_stage_nom'] = $this->normalizeMaitreStageName($row['maitre_stage_nom'] ?? '');
            }
            unset($row);

            return $rows;
        } catch (Exception $e) {
            error_log('Erreur getEtudiantsForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les étudiants disponibles pour nouvelle programmation (non encore programmés)
     */
    public function getEtudiantsDisponiblesForView(): array
    {
        $rows = $this->getEtudiantsForView();
        return array_values(array_filter($rows, static function (array $row): bool {
            return (string) ($row['statut_programmation'] ?? '') !== 'programmed';
        }));
    }

    /**
     * Récupérer tous les enseignants
     */
    public function getEnseignants(): array
    {
        try {
            $sql = "
                SELECT
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) as nom_complet,
                    e.mail_enseignant as email_enseignant,
                    COALESCE(f.lib_fonction, '') as lib_fonction
                FROM enseignants e
                LEFT JOIN occuper o ON e.id_enseignant = o.id_enseignant
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getEnseignants: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les salles disponibles pour la programmation
     */
    public function getSalles(): array
    {
        try {
            $sql = "
                SELECT
                    id_salle,
                    lib_salle,
                    NULL as disponibilite_salle
                FROM salles
                ORDER BY lib_salle
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getSalles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les professeurs titulaires
     */
    public function getProfesseursTitulaires(): array
    {
        try {
            $sql = "
                SELECT
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) as nom_complet,
                    e.mail_enseignant as email_enseignant,
                    f.lib_fonction
                FROM enseignants e
                INNER JOIN occuper o ON e.id_enseignant = o.id_enseignant
                INNER JOIN fonction f ON o.id_fonction = f.id_fonction
                WHERE LOWER(f.lib_fonction) LIKE '%professeur%'
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getProfesseursTitulaires: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les attributions de jury
     */
    public function getAttributions(): array
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return [];
            }
            $idCol = $this->getProgrammationIdColumn($progTable);
            $selectedYearId = $this->getSelectedAcademicYearId();

            $presidentId = $this->juryIdExpr('president', 'p');
            $presidentNom = $this->juryNameExpr('president', 'p');
            $examinateurId = $this->juryIdExpr('examinateur', 'p');
            $examinateurNom = $this->juryNameExpr('examinateur', 'p');
            $directeurId = $this->juryIdExpr('directeur', 'p');
            $directeurNom = $this->juryNameExpr('directeur', 'p');
            $encadreurId = $this->juryIdExpr('encadreur', 'p');
            $encadreurNom = $this->juryNameExpr('encadreur', 'p');
            // Le maître de stage n'est pas dans `enseignants` mais dans `maitre_de_stage`.
            // On utilise une sous-requête directe sur informations_stage → maitre_de_stage.
            $maitreNomExpr = "(SELECT CONCAT(ms2.prenom, ' ', ms2.Nom)
                              FROM informations_stage ist2
                              JOIN maitre_de_stage ms2 ON ms2.id_maitre_stage = ist2.id_maitre_stage
                              WHERE ist2.num_etu = p.num_etud
                              LIMIT 1)";
            $maitreIdExpr = "(SELECT ist2.id_maitre_stage
                             FROM informations_stage ist2
                             WHERE ist2.num_etu = p.num_etud
                             LIMIT 1)";
            $yearSelect = $this->columnExists($progTable, 'id_annee_acad')
                ? 'p.id_annee_acad as id_annee_acad,'
                : 'NULL as id_annee_acad,';
            $studentJoin = $this->studentJoinCondition('e', 'p');
            $studentIdentifier = $this->studentIdentifierExpr('e', 'p');

            $sql = "
                SELECT
                    p.{$idCol} as id_attribution,
                    {$yearSelect}
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle,
                    {$studentIdentifier} as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    {$studentIdentifier} as matricule_etudiant,
                    e.promotion_etu,
                    {$presidentId} as president_id,
                    {$presidentNom} as president_nom,
                    {$examinateurId} as examinateur_id,
                    {$examinateurNom} as examinateur_nom,
                    {$directeurId} as directeur_id,
                    {$directeurNom} as directeur_nom,
                    {$encadreurId} as encadreur_id,
                    {$encadreurNom} as encadreur_nom,
                    {$maitreIdExpr} as maitre_stage_id,
                    {$maitreNomExpr} as maitre_stage_nom,
                    {$maitreIdExpr} as maitre_stage_ref
                FROM {$progTable} p
                LEFT JOIN etudiants e ON {$studentJoin}
                LEFT JOIN rapport_etudiants r ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                " . $this->latestCandidatureJoin('r', 'e', 'cs') . "
                LEFT JOIN valider v ON v.id_rapport = r.id_rapport
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE 1=1
                " . $this->validatedCandidatureWhere('cs') . "
            ";

            if ($selectedYearId !== null && $selectedYearId > 0) {
                if ($this->columnExists($progTable, 'id_annee_acad')) {
                    $sql .= " AND p.id_annee_acad = :id_annee_acad";
                } else {
                    $sql .= " AND " . $this->getFallbackStudentYearExpr('e') . " = :id_annee_acad";
                }
            }

            $sql .= " ORDER BY p.date_soutenance ASC, p.heure_soutenance ASC";

            $stmt = $this->pdo->prepare($sql);
            if ($selectedYearId !== null && $selectedYearId > 0) {
                $stmt->bindValue(':id_annee_acad', $selectedYearId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['maitre_stage_nom'] = $this->normalizeMaitreStageName($row['maitre_stage_nom'] ?? '');
            }
            unset($row);

            return $rows;
        } catch (Exception $e) {
            error_log('Erreur getAttributions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les étudiants avec rapport validé (pour API JSON)
     */
    public function getEtudiantsValides(): array
    {
        return $this->getEtudiantsForView();
    }

    /**
     * Créer une nouvelle attribution de jury
     */
    public function createAttribution(array $data): array
    {
        try {
            if (!isset($data['id_etudiant']) || empty($data['id_etudiant'])) {
                throw new Exception('L\'étudiant est requis');
            }
            if (!isset($data['theme_soutenance']) || empty(trim($data['theme_soutenance']))) {
                throw new Exception('Le thème de soutenance est requis');
            }
            if (!isset($data['date_soutenance']) || empty($data['date_soutenance'])) {
                throw new Exception('La date de soutenance est requise');
            }
            if (!isset($data['heure_soutenance']) || empty($data['heure_soutenance'])) {
                throw new Exception('L\'heure de soutenance est requise');
            }
            if (!isset($data['id_salle']) || empty($data['id_salle'])) {
                throw new Exception('La salle est requise');
            }

            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                throw new Exception('Aucune table de programmation de soutenance disponible');
            }

            if (!$this->isStudentProgrammable((string) $data['id_etudiant'])) {
                throw new Exception('Cet étudiant ne peut pas être programmé tant que sa candidature n\'est pas validée.');
            }

            $this->ensureWritableStudent((string) $data['id_etudiant'], 'une programmation de soutenance');

            $this->pdo->beginTransaction();

            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$progTable} WHERE num_etud = ?");
            $checkStmt->execute([(string) $data['id_etudiant']]);
            if ((int) $checkStmt->fetchColumn() > 0) {
                throw new Exception('Cet étudiant a déjà une programmation de soutenance');
            }

            if ($progTable === 'programmer') {
                $juryStmt = $this->pdo->prepare("SELECT COALESCE(MAX(num_jury), 0) + 1 as next_jury FROM programmer");
                $juryStmt->execute();
                $numJury = (int) ($juryStmt->fetch(PDO::FETCH_ASSOC)['next_jury'] ?? 1);

                $sql = "
                    INSERT INTO programmer (
                        num_etud, num_jury, theme_soutenance,
                        date_soutenance, heure_soutenance, id_salle
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    (string) $data['id_etudiant'],
                    $numJury,
                    trim((string) $data['theme_soutenance']),
                    (string) $data['date_soutenance'],
                    (string) $data['heure_soutenance'],
                    (int) $data['id_salle'],
                ]);
                $attributionId = (string) $this->pdo->lastInsertId();
                $juryRef = (string) $numJury;
            } else {
                $idDomaine = !empty($data['id_domaine']) ? (int) $data['id_domaine'] : $this->getDefaultId('domaine', 'id_domaine');
                $idSession = !empty($data['id_session']) ? (int) $data['id_session'] : $this->getDefaultId('session', 'id_session');
                $selectedYearId = $this->getSelectedAcademicYearId();

                $nextStmt = $this->pdo->prepare("SELECT COALESCE(MAX(CAST(num_soutenance AS UNSIGNED)), 0) + 1 as next_id FROM programmer_soutenance");
                $nextStmt->execute();
                $numSoutenance = (string) ((int) ($nextStmt->fetch(PDO::FETCH_ASSOC)['next_id'] ?? 1));

                $hasYearColumn = $this->columnExists('programmer_soutenance', 'id_annee_acad');
                $sql = $hasYearColumn
                    ? "
                        INSERT INTO programmer_soutenance (
                            num_soutenance, num_etud, theme_soutenance, id_domaine, id_session, id_salle, date_soutenance, heure_soutenance, id_annee_acad
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    "
                    : "
                        INSERT INTO programmer_soutenance (
                            num_soutenance, num_etud, theme_soutenance, id_domaine, id_session, id_salle, date_soutenance, heure_soutenance
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                $stmt = $this->pdo->prepare($sql);
                $params = [
                    $numSoutenance,
                    (string) $data['id_etudiant'],
                    trim((string) $data['theme_soutenance']),
                    $idDomaine,
                    $idSession,
                    (int) $data['id_salle'],
                    (string) $data['date_soutenance'],
                    (string) $data['heure_soutenance'],
                ];
                if ($hasYearColumn) {
                    $params[] = $selectedYearId;
                }
                $stmt->execute($params);
                $attributionId = $numSoutenance;
                $juryRef = $numSoutenance;
            }

            $this->insertJuryMembers($juryRef, $data);
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Attribution créée avec succès',
                'data' => ['id' => $attributionId],
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Mettre à jour une attribution de jury
     */
    public function updateAttribution(array $data): array
    {
        try {
            if (!isset($data['id']) || $data['id'] === '') {
                throw new Exception('ID de l\'attribution requis');
            }
            if (!isset($data['theme_soutenance']) || empty(trim($data['theme_soutenance']))) {
                throw new Exception('Le thème de soutenance est requis');
            }
            if (!isset($data['date_soutenance']) || empty($data['date_soutenance'])) {
                throw new Exception('La date de soutenance est requise');
            }
            if (!isset($data['heure_soutenance']) || empty($data['heure_soutenance'])) {
                throw new Exception('L\'heure de soutenance est requise');
            }
            if (!isset($data['id_salle']) || empty($data['id_salle'])) {
                throw new Exception('La salle est requise');
            }

            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                throw new Exception('Aucune table de programmation de soutenance disponible');
            }
            $idCol = $this->getProgrammationIdColumn($progTable);
            $this->ensureWritableAttribution((string) $data['id'], 'une programmation de soutenance');

            $this->pdo->beginTransaction();

            $juryRef = null;
            if ($progTable === 'programmer') {
                $juryStmt = $this->pdo->prepare("SELECT num_jury FROM programmer WHERE {$idCol} = ?");
                $juryStmt->execute([(string) $data['id']]);
                $row = $juryStmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    throw new Exception('Programmation non trouvée');
                }
                $juryRef = (string) ($row['num_jury'] ?? '');
            } else {
                $juryRef = (string) $data['id'];
            }

            $sql = "UPDATE {$progTable}
                    SET theme_soutenance = ?, date_soutenance = ?, heure_soutenance = ?, id_salle = ?
                    WHERE {$idCol} = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                trim((string) $data['theme_soutenance']),
                (string) $data['date_soutenance'],
                (string) $data['heure_soutenance'],
                (int) $data['id_salle'],
                (string) $data['id'],
            ]);

            $juryTable = $this->getJuryTable();
            if ($juryTable !== null && $juryRef !== '') {
                $juryRefCol = $this->getJuryRefColumn($juryTable);
                $deleteStmt = $this->pdo->prepare("DELETE FROM {$juryTable} WHERE {$juryRefCol} = ?");
                $deleteStmt->execute([$juryRef]);
            }

            $this->insertJuryMembers($juryRef, $data);
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Attribution mise à jour avec succès',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Supprimer une attribution de jury
     */
    public function deleteAttribution($id): array
    {
        try {
            if ($id === null || $id === '') {
                throw new Exception('ID de l\'attribution requis');
            }

            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                throw new Exception('Aucune table de programmation de soutenance disponible');
            }
            $idCol = $this->getProgrammationIdColumn($progTable);
            $this->ensureWritableAttribution($id, 'une programmation de soutenance');

            $this->pdo->beginTransaction();

            $juryRef = null;
            if ($progTable === 'programmer') {
                $juryStmt = $this->pdo->prepare("SELECT num_jury FROM programmer WHERE {$idCol} = ?");
                $juryStmt->execute([(string) $id]);
                $row = $juryStmt->fetch(PDO::FETCH_ASSOC);
                $juryRef = $row ? (string) ($row['num_jury'] ?? '') : '';
            } else {
                $juryRef = (string) $id;
            }

            $juryTable = $this->getJuryTable();
            if ($juryTable !== null && $juryRef !== '') {
                $juryRefCol = $this->getJuryRefColumn($juryTable);
                $deleteJuryStmt = $this->pdo->prepare("DELETE FROM {$juryTable} WHERE {$juryRefCol} = ?");
                $deleteJuryStmt->execute([$juryRef]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM {$progTable} WHERE {$idCol} = ?");
            $stmt->execute([(string) $id]);

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Attribution supprimée avec succès',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Insérer les membres du jury avec leurs rôles.
     */
    private function insertJuryMembers($juryRef, array $data): void
    {
        $juryTable = $this->getJuryTable();
        if ($juryTable === null || $juryRef === null || $juryRef === '') {
            return;
        }

        $roleIds = $this->getRoleIds();
        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $insertSql = "INSERT INTO {$juryTable} ({$juryRefCol}, id_enseignant, id_qualite_jury, date_composer_jury) VALUES (?, ?, ?, UNIX_TIMESTAMP())";
        $stmt = $this->pdo->prepare($insertSql);

        $members = [
            'president_id' => 'president',
            'examinateur_id' => 'examinateur',
            'directeur_id' => 'directeur',
            'encadreur_id' => 'encadreur',
            'maitre_stage_id' => 'maitre_stage',
        ];

        foreach ($members as $field => $roleKey) {
            $enseignantId = $data[$field] ?? '';
            $roleId = $roleIds[$roleKey] ?? '';
            if ($enseignantId !== '' && $roleId !== '') {
                $stmt->execute([(string) $juryRef, (string) $enseignantId, (string) $roleId]);
            }
        }
    }

    public function getEnseignantJury(): array
    {
        try {
            $sql = "SELECT * FROM enseignant_jury";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $enseignantJury = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $enseignantJury;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
