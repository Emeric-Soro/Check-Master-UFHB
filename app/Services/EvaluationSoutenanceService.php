<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use CritereEvaluation;
use Exception;
use PDO;
use Throwable;

class EvaluationSoutenanceService
{
    private $pdo;
    private $critereModel;
    private $tableExistsCache = [];
    private $columnExistsCache = [];
    private $roleIdsCache = null;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
        $this->critereModel = new CritereEvaluation($this->pdo);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveAcademicYear(?string $preferredId = null): ?array
    {
        if ($preferredId !== null && is_numeric($preferredId) && (int) $preferredId > 0) {
            $year = \AcademicYear::getById($this->pdo, (int) $preferredId);
            if ($year !== null) {
                return $year;
            }
        }

        $selectedId = \AcademicYear::getSelectedIdFromSession();
        if ($selectedId !== null && $selectedId > 0) {
            $year = \AcademicYear::getById($this->pdo, $selectedId);
            if ($year !== null) {
                return $year;
            }
        }

        return \AcademicYear::getActive($this->pdo);
    }

    private function getStudentAcademicYearId(string $numEtu): ?int
    {
        try {
            $orderParts = [];
            if ($this->columnExists('inscriptions', 'date_inscription')) {
                $orderParts[] = 'date_inscription DESC';
            }
            if ($this->columnExists('inscriptions', 'num_versement')) {
                $orderParts[] = 'num_versement DESC';
            }
            $orderParts[] = 'id_annee_acad DESC';
            $orderBy = ' ORDER BY ' . implode(', ', array_unique($orderParts));

            $stmt = $this->pdo->prepare("SELECT id_annee_acad FROM inscriptions WHERE num_carte_etud = ?" . $orderBy . " LIMIT 1");
            $stmt->execute([$numEtu]);
            $value = $stmt->fetchColumn();
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        } catch (Throwable $e) {
            error_log('Erreur getStudentAcademicYearId by card: ' . $e->getMessage());
        }

        if ($this->columnExists('etudiants', 'num_ident_etud')) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT i.id_annee_acad
                    FROM inscriptions i
                    INNER JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
                    WHERE e.num_ident_etud = ?
                    ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC
                    LIMIT 1
                ");
                $stmt->execute([$numEtu]);
                $value = $stmt->fetchColumn();
                if (is_numeric($value) && (int) $value > 0) {
                    return (int) $value;
                }
            } catch (Throwable $e) {
                error_log('Erreur getStudentAcademicYearId by ident: ' . $e->getMessage());
            }
        }

        return null;
    }

    private function resolveEvaluationJuryRefForEtudiant(string $numEtu): ?string
    {
        $juryRef = $this->resolveJuryRefForEtudiant($numEtu);
        if ($juryRef !== null && $juryRef !== '') {
            return (string) $juryRef;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT num_jury
                FROM evaluer
                WHERE num_etudiant = ?
                ORDER BY date_eval DESC, id_critere ASC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            $value = $stmt->fetchColumn();
            if ($value !== false && $value !== null && $value !== '') {
                return (string) $value;
            }
        } catch (Throwable $e) {
            error_log('Erreur resolveEvaluationJuryRefForEtudiant: ' . $e->getMessage());
        }

        return null;
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
        } catch (Throwable $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function critereCodeSelect(string $alias = 'c'): string
    {
        if ($this->columnExists('critere_evaluation', 'code_critere')) {
            return $alias . '.code_critere AS code_critere';
        }

        return $alias . '.id_critere AS code_critere';
    }

    private function normalizeCriteriaRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $row['id_critere'] = (string) ($row['id_critere'] ?? '');
            $row['code_critere'] = (string) ($row['code_critere'] ?? $row['id_critere'] ?? '');
            $row['lib_critere'] = (string) ($row['lib_critere'] ?? '');
            $row['bareme_max'] = isset($row['bareme_max']) && $row['bareme_max'] !== null
                ? (float) $row['bareme_max']
                : null;
            $normalized[] = $row;
        }

        return $normalized;
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
        } catch (Throwable $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
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

    private function getPromotionLabelExpr(string $studentAlias = 'e', string $programmationAlias = 'p'): string
    {
        $studentPromotionExpr = "NULLIF(TRIM({$studentAlias}.promotion_etu), '')";
        $programmationYearExpr = "NULLIF(TRIM({$programmationAlias}.id_annee_acad), '')";

        return "COALESCE(
            CASE
                WHEN {$studentPromotionExpr} REGEXP '^[0-9]{4}-[0-9]{4}$' THEN {$studentPromotionExpr}
                WHEN {$studentPromotionExpr} REGEXP '^[0-9]{4}$' THEN CONCAT({$studentPromotionExpr}, '-', CAST({$studentPromotionExpr} AS UNSIGNED) + 1)
                WHEN {$studentPromotionExpr} REGEXP '^2[0-9]{4}$' THEN CONCAT('20', RIGHT({$studentPromotionExpr}, 2), '-', '20', SUBSTRING({$studentPromotionExpr}, 2, 2))
                ELSE {$studentPromotionExpr}
            END
            ,
            (
                SELECT CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin))
                FROM annee_academique aa
                WHERE aa.id_annee_acad = {$programmationAlias}.id_annee_acad
                LIMIT 1
            ),
            CASE
                WHEN {$programmationYearExpr} REGEXP '^[0-9]{4}-[0-9]{4}$' THEN {$programmationYearExpr}
                WHEN {$programmationYearExpr} REGEXP '^[0-9]{4}$' THEN CONCAT({$programmationYearExpr}, '-', CAST({$programmationYearExpr} AS UNSIGNED) + 1)
                WHEN {$programmationYearExpr} REGEXP '^2[0-9]{4}$' THEN CONCAT('20', RIGHT({$programmationYearExpr}, 2), '-', '20', SUBSTRING({$programmationYearExpr}, 2, 2))
                ELSE {$programmationYearExpr}
            END
        )";
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
            $rows = $this->pdo->query("SELECT id_role_jury, lib_role FROM {$rolesTable}")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $id = (string) ($row['id_role_jury'] ?? '');
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
        } catch (Throwable $e) {
            $this->roleIdsCache = [];
        }

        return $this->roleIdsCache;
    }

    private function juryNameExpr($roleKey, $progAlias = 'p')
    {
        $roleIds = $this->getRoleIds();
        $roleId = (string) ($roleIds[$roleKey] ?? '');
        $juryTable = $this->getJuryTable();
        $progTable = $this->getProgrammationTable();

        if ($roleId === '' || $juryTable === null || $progTable === null) {
            return 'NULL';
        }

        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $progJuryCol = $this->getProgrammationJuryColumn($progTable);
        $quotedRoleId = $this->pdo->quote($roleId);

        return "(SELECT CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant)
                 FROM {$juryTable} cj
                 JOIN enseignants ens ON cj.id_enseignant = ens.id_enseignant
                 WHERE cj.{$juryRefCol} = {$progAlias}.{$progJuryCol}
                 AND cj.id_qualite_jury = {$quotedRoleId}
                 LIMIT 1)";
    }

    private function resolveJuryRefForEtudiant($numEtu)
    {
        $progTable = $this->getProgrammationTable();
        if ($progTable === null) {
            return null;
        }

        $juryCol = $this->getProgrammationJuryColumn($progTable);

        try {
            $stmt = $this->pdo->prepare("
                SELECT {$juryCol} AS jury_ref
                FROM {$progTable}
                WHERE num_etud = ?
                ORDER BY date_soutenance DESC, heure_soutenance DESC
                LIMIT 1
            ");
            $stmt->execute([(string) $numEtu]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['jury_ref'] !== null && $row['jury_ref'] !== '') {
                return (string) $row['jury_ref'];
            }
        } catch (Throwable $e) {
            // fallback below
        }

        if ($this->columnExists('etudiants', 'num_ident_etud')) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT p.{$juryCol} AS jury_ref
                    FROM {$progTable} p
                    JOIN etudiants e ON p.num_etud = e.num_carte_etud
                    WHERE e.num_ident_etud = ?
                    ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
                    LIMIT 1
                ");
                $stmt->execute([(string) $numEtu]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['jury_ref'] !== null && $row['jury_ref'] !== '') {
                    return (string) $row['jury_ref'];
                }
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function getCriteriaRowsByYear($idAnneeAcad)
    {
        $idAnneeAcad = (string) $idAnneeAcad;

        if ($this->tableExists('bareme_critere')) {
            $sql = "
                SELECT c.id_critere, " . $this->critereCodeSelect('c') . ", c.lib_critere, bc.bareme AS bareme_max
                FROM critere_evaluation c
                INNER JOIN bareme_critere bc ON c.id_critere = bc.id_critere
                WHERE bc.id_annee_acad = ?
                ORDER BY c.id_critere
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idAnneeAcad]);
            $rows = $this->normalizeCriteriaRows($stmt->fetchAll(PDO::FETCH_ASSOC));
            if (!empty($rows)) {
                return $rows;
            }

            $stmt = $this->pdo->query("
                SELECT c.id_critere, " . $this->critereCodeSelect('c') . ", c.lib_critere, bc.bareme AS bareme_max
                FROM critere_evaluation c
                INNER JOIN bareme_critere bc ON c.id_critere = bc.id_critere
                WHERE bc.id_annee_acad = (SELECT MAX(id_annee_acad) FROM bareme_critere)
                ORDER BY c.id_critere
            ");
            $rows = $this->normalizeCriteriaRows($stmt->fetchAll(PDO::FETCH_ASSOC));
            if (!empty($rows)) {
                return $rows;
            }
        }

        if ($this->tableExists('correspondre')) {
            $sql = "
                SELECT c.id_critere, " . $this->critereCodeSelect('c') . ", c.lib_critere, cr.bareme AS bareme_max
                FROM critere_evaluation c
                INNER JOIN correspondre cr ON c.id_critere = cr.id_critere
                WHERE cr.id_annee_acad = ?
                ORDER BY c.id_critere
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idAnneeAcad]);
            $rows = $this->normalizeCriteriaRows($stmt->fetchAll(PDO::FETCH_ASSOC));
            if (!empty($rows)) {
                return $rows;
            }

            $stmt = $this->pdo->query("
                SELECT c.id_critere, " . $this->critereCodeSelect('c') . ", c.lib_critere, cr.bareme AS bareme_max
                FROM critere_evaluation c
                INNER JOIN correspondre cr ON c.id_critere = cr.id_critere
                WHERE cr.id_annee_acad = (SELECT MAX(id_annee_acad) FROM correspondre)
                ORDER BY c.id_critere
            ");
            $rows = $this->normalizeCriteriaRows($stmt->fetchAll(PDO::FETCH_ASSOC));
            if (!empty($rows)) {
                return $rows;
            }
        }

        $stmt = $this->pdo->query("
            SELECT c.id_critere, " . $this->critereCodeSelect('c') . ", c.lib_critere, NULL AS bareme_max
            FROM critere_evaluation c
            ORDER BY c.id_critere
        ");
        return $this->normalizeCriteriaRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function getBaremeForCritere($idCritere, $idAnneeAcad)
    {
        $idCritere = trim((string) $idCritere);
        $idAnneeAcad = (string) $idAnneeAcad;
        if ($idCritere === '') {
            throw new Exception('Critere invalide');
        }

        try {
            if ($this->tableExists('bareme_critere')) {
                $stmt = $this->pdo->prepare("
                    SELECT bareme
                    FROM bareme_critere
                    WHERE id_critere = ? AND id_annee_acad = ?
                    LIMIT 1
                ");
                $stmt->execute([$idCritere, $idAnneeAcad]);
                $value = $stmt->fetchColumn();
                if ($value !== false && $value !== null) {
                    return (float) $value;
                }

                $stmt = $this->pdo->prepare("
                    SELECT bareme
                    FROM bareme_critere
                    WHERE id_critere = ?
                    ORDER BY id_annee_acad DESC
                    LIMIT 1
                ");
                $stmt->execute([$idCritere]);
                $value = $stmt->fetchColumn();
                if ($value !== false && $value !== null) {
                    return (float) $value;
                }
            }

            if ($this->tableExists('correspondre')) {
                $stmt = $this->pdo->prepare("
                    SELECT bareme
                    FROM correspondre
                    WHERE id_critere = ? AND id_annee_acad = ?
                    LIMIT 1
                ");
                $stmt->execute([$idCritere, $idAnneeAcad]);
                $value = $stmt->fetchColumn();
                if ($value !== false && $value !== null) {
                    return (float) $value;
                }

                $stmt = $this->pdo->prepare("
                    SELECT bareme
                    FROM correspondre
                    WHERE id_critere = ?
                    ORDER BY id_annee_acad DESC
                    LIMIT 1
                ");
                $stmt->execute([$idCritere]);
                $value = $stmt->fetchColumn();
                if ($value !== false && $value !== null) {
                    return (float) $value;
                }
            }
        } catch (Throwable $e) {
            throw new Exception('Barème introuvable pour le critère ' . $idCritere);
        }

        throw new Exception('Barème introuvable pour le critère ' . $idCritere);
    }

    public function getSoutenancesProgrammeesForView(): array
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return [];
            }

            $idCol = $this->getProgrammationIdColumn($progTable);
            $juryCol = $this->getProgrammationJuryColumn($progTable);

            $presidentNom = $this->juryNameExpr('president', 'p');
            $examinateurNom = $this->juryNameExpr('examinateur', 'p');
            $directeurNom = $this->juryNameExpr('directeur', 'p');
            $encadreurNom = $this->juryNameExpr('encadreur', 'p');
            $promotionLabel = $this->getPromotionLabelExpr('e', 'p');
            $selectedYearId = \AcademicYear::getSelectedIdFromSession();

            $sql = "
                SELECT
                    p.{$idCol} AS id_programmation,
                    p.{$juryCol} AS jury_ref,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    COALESCE(e.num_carte_etud, p.num_etud) AS num_etu,
                    CONCAT(COALESCE(e.prenom_etu, ''), ' ', COALESCE(e.nom_etu, '')) AS nom_etudiant,
                    COALESCE(e.num_carte_etud, p.num_etud) AS matricule_etudiant,
                    COALESCE(e.promotion_etu, '') AS promotion_etu,
                    {$promotionLabel} AS promotion_label,
                    s.lib_salle AS nom_salle,
                    {$presidentNom} AS president_nom,
                    {$examinateurNom} AS examinateur_nom,
                    {$directeurNom} AS directeur_nom,
                    {$encadreurNom} AS encadreur_nom,
                    CONCAT(ms.prenom, ' ', ms.Nom) AS maitre_stage_nom,
                    (
                        SELECT COUNT(*)
                        FROM evaluer ev
                        WHERE ev.num_etudiant = COALESCE(e.num_carte_etud, p.num_etud)
                          AND ev.num_jury = p.{$juryCol}
                    ) AS est_evalue,
                    (
                        SELECT SUM(ev.note)
                        FROM evaluer ev
                        WHERE ev.num_etudiant = COALESCE(e.num_carte_etud, p.num_etud)
                          AND ev.num_jury = p.{$juryCol}
                    ) AS note_finale,
                    (
                        SELECT GROUP_CONCAT(
                            CONCAT(COALESCE(ce.lib_critere, CONCAT('Critere ', ev2.id_critere)), ': ', ev2.note)
                            SEPARATOR '; '
                        )
                        FROM evaluer ev2
                        LEFT JOIN critere_evaluation ce ON ce.id_critere = ev2.id_critere
                        WHERE ev2.num_etudiant = COALESCE(e.num_carte_etud, p.num_etud)
                          AND ev2.num_jury = p.{$juryCol}
                    ) AS commentaire_general
                FROM {$progTable} p
                LEFT JOIN etudiants e ON p.num_etud = e.num_carte_etud
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                LEFT JOIN informations_stage ist ON ist.num_etu = COALESCE(e.num_carte_etud, p.num_etud)
                LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
                WHERE p.id_salle IS NOT NULL
                  AND p.date_soutenance IS NOT NULL
                  AND p.heure_soutenance IS NOT NULL
            ";

            if ($selectedYearId !== null && $selectedYearId > 0) {
                $sql .= " AND EXISTS (SELECT 1 FROM inscriptions i WHERE i.num_carte_etud = e.num_carte_etud AND i.id_annee_acad = :id_annee_acad)";
            }

            $sql .= "
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            if ($selectedYearId !== null && $selectedYearId > 0) {
                $stmt->bindValue(':id_annee_acad', $selectedYearId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Erreur getSoutenancesProgrammeesForView: ' . $e->getMessage());
            return [];
        }
    }

    public function getAnneeAcademiqueCourante()
    {
        try {
            $row = \AcademicYear::getActive($this->pdo);
            if ($row === null) {
                return null;
            }

            return [
                'id_annee_acad' => $row['id'] ?? null,
                'date_deb' => $row['date_deb'] ?? null,
                'date_fin' => $row['date_fin'] ?? null,
            ];
        } catch (Throwable $e) {
            error_log('Erreur getAnneeAcademiqueCourante: ' . $e->getMessage());
            return null;
        }
    }

    public function getCriteresEvaluation(): array
    {
        try {
            $annee = $this->resolveAcademicYear();
            $idAnneeAcad = (string) ($annee['id_annee_acad'] ?? '');
            if ($idAnneeAcad === '' && isset($annee['id'])) {
                $idAnneeAcad = (string) $annee['id'];
            }

            if ($idAnneeAcad !== '') {
                $rows = $this->getCriteriaRowsByYear($idAnneeAcad);
                if (!empty($rows)) {
                    return $rows;
                }
            }

            $rows = $this->critereModel->getAllCriteres();
            $result = [];
            foreach ($rows as $row) {
                $result[] = [
                    'id_critere' => (string) ($row->id_critere ?? ''),
                    'code_critere' => (string) ($row->code_critere ?? $row->id_critere ?? ''),
                    'lib_critere' => (string) ($row->lib_critere ?? ''),
                    'bareme_max' => null,
                ];
            }
            return $result;
        } catch (Throwable $e) {
            error_log('Erreur getCriteresEvaluation: ' . $e->getMessage());
            return [];
        }
    }

    public function getAnneesAcademiques(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_annee_acad, date_deb, date_fin,
                       CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS lib_annee
                FROM annee_academique
                ORDER BY date_deb DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Erreur getAnneesAcademiques: ' . $e->getMessage());
            return [];
        }
    }

    public function getEvaluationExistante($numEtu)
    {
        try {
            $numEtu = (string) $numEtu;
            if ($numEtu === '') {
                return [];
            }

            $juryRef = $this->resolveJuryRefForEtudiant($numEtu);
            if ($juryRef !== null) {
                $stmt = $this->pdo->prepare("
                    SELECT e.id_critere, e.note, e.date_eval, c.lib_critere
                    FROM evaluer e
                    LEFT JOIN critere_evaluation c ON e.id_critere = c.id_critere
                    WHERE e.num_etudiant = ? AND e.num_jury = ?
                    ORDER BY e.date_eval DESC, e.id_critere
                ");
                $stmt->execute([$numEtu, $juryRef]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT e.id_critere, e.note, e.date_eval, c.lib_critere
                    FROM evaluer e
                    LEFT JOIN critere_evaluation c ON e.id_critere = c.id_critere
                    WHERE e.num_etudiant = ?
                    ORDER BY e.date_eval DESC, e.id_critere
                ");
                $stmt->execute([$numEtu]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Erreur getEvaluationExistante: ' . $e->getMessage());
            return [];
        }
    }

    public function enregistrerEvaluation(string $numEtu, array $criteres, string $commentaireGeneral = '', ?string $idAnneeAcad = null): array
    {
        try {
            if ($numEtu === '') {
                throw new Exception('Numero etudiant requis');
            }

            $juryRef = $this->resolveJuryRefForEtudiant($numEtu);
            if ($juryRef === null || $juryRef === '') {
                throw new Exception('Aucune soutenance programmee pour cet etudiant');
            }

            $studentYearId = $this->getStudentAcademicYearId($numEtu);
            if ($studentYearId === null || $studentYearId <= 0) {
                throw new Exception("Impossible de determiner l'annee academique de l'etudiant");
            }

            $selectedYearId = \AcademicYear::getSelectedIdFromSession();
            if ($selectedYearId !== null && $selectedYearId > 0 && $selectedYearId !== $studentYearId) {
                throw new Exception("L'etudiant ne correspond pas a l'annee academique actuellement selectionnee.");
            }

            $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $studentYearId, 'une evaluation de soutenance');
            if (!$writeGuard['success']) {
                throw new Exception($writeGuard['message']);
            }

            $idAnneeAcad = (string) $studentYearId;

            $notesValides = [];
            foreach ($criteres as $idCritere => $note) {
                $idCritere = trim((string) $idCritere);
                if ($note === '' || $note === null || !is_numeric($note)) {
                    continue;
                }
                if ($idCritere === '') {
                    continue;
                }
                $notesValides[$idCritere] = (float) $note;
            }

            if (empty($notesValides)) {
                throw new Exception('Au moins un critere doit etre renseigne');
            }

            $noteFinale = $this->calculerSommeNotes($notesValides, (string) $idAnneeAcad);

            $this->pdo->beginTransaction();

            $deleteStmt = $this->pdo->prepare("DELETE FROM evaluer WHERE num_etudiant = ? AND num_jury = ?");
            $deleteStmt->execute([$numEtu, $juryRef]);

            $insertStmt = $this->pdo->prepare("
                INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note)
                VALUES (?, ?, ?, ?, ?)
            ");

            $dateEval = date('Y-m-d');
            foreach ($notesValides as $idCritere => $note) {
                $insertStmt->execute([$numEtu, $juryRef, $idCritere, $dateEval, $note]);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Evaluation enregistree avec succes',
                'note_finale' => $noteFinale,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
            ];
        }
    }

    public function calculerSommeNotes(array $criteres, string $idAnneeAcad): float
    {
        $somme = 0.0;
        foreach ($criteres as $idCritere => $note) {
            if (!is_numeric($note)) {
                continue;
            }

            $note = (float) $note;
            if ($note < 0) {
                throw new Exception('Une note ne peut pas etre negative');
            }

            $bareme = $this->getBaremeForCritere((string) $idCritere, $idAnneeAcad);
            if ($note > $bareme) {
                throw new Exception('La note du critere ' . $idCritere . ' depasse le bareme (' . $bareme . ')');
            }

            $somme += $note;
        }

        return $somme;
    }

    public function supprimerEvaluation(string $numEtu): array
    {
        try {
            if ($numEtu === '') {
                throw new Exception('Numero etudiant requis');
            }

            $studentYearId = $this->getStudentAcademicYearId($numEtu);
            $selectedYearId = \AcademicYear::getSelectedIdFromSession();
            if ($selectedYearId !== null && $studentYearId !== null && $selectedYearId !== $studentYearId) {
                throw new Exception("L'etudiant ne correspond pas a l'annee academique actuellement selectionnee.");
            }

            $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $studentYearId, 'une suppression d evaluation de soutenance');
            if (!$writeGuard['success']) {
                throw new Exception((string) $writeGuard['message']);
            }

            $juryRef = $this->resolveEvaluationJuryRefForEtudiant($numEtu);
            if ($juryRef === null || $juryRef === '') {
                throw new Exception('Aucune evaluation cible n a ete trouvee pour cet etudiant.');
            }

            $stmt = $this->pdo->prepare("DELETE FROM evaluer WHERE num_etudiant = ? AND num_jury = ?");
            $stmt->execute([$numEtu, $juryRef]);

            return [
                'success' => true,
                'message' => 'Evaluation supprimee avec succes',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ];
        }
    }

    public function getCriteresParAnnee(string $idAnneeAcad): array
    {
        try {
            if ($idAnneeAcad === '') {
                throw new Exception('ID annee academique requis');
            }

            return [
                'success' => true,
                'data' => $this->getCriteriaRowsByYear($idAnneeAcad),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ];
        }
    }

    public function getDonneesPV(string $numEtu, ?float $moyenneMaster1Input = null): array
    {
        if ($numEtu === '') {
            throw new Exception('Numero etudiant requis');
        }

        $progTable = $this->getProgrammationTable();
        if ($progTable === null) {
            throw new Exception('Aucune table de programmation disponible');
        }

        $idCol = $this->getProgrammationIdColumn($progTable);
        $juryCol = $this->getProgrammationJuryColumn($progTable);

        $presidentNom = $this->juryNameExpr('president', 'p');
        $examinateurNom = $this->juryNameExpr('examinateur', 'p');
        $directeurNom = $this->juryNameExpr('directeur', 'p');
        $encadreurNom = $this->juryNameExpr('encadreur', 'p');

        $sql = "
            SELECT
                p.{$idCol} AS id_programmation,
                p.{$juryCol} AS jury_ref,
                p.theme_soutenance,
                p.date_soutenance,
                p.heure_soutenance,
                COALESCE(e.num_carte_etud, p.num_etud) AS num_etu,
                CONCAT(COALESCE(e.prenom_etu, ''), ' ', COALESCE(e.nom_etu, '')) AS nom_etudiant,
                COALESCE(e.promotion_etu, '') AS promotion_etu,
                {$presidentNom} AS president,
                {$examinateurNom} AS examinateur,
                {$directeurNom} AS directeur,
                {$encadreurNom} AS encadreur,
                CONCAT(ms.prenom, ' ', ms.Nom) AS maitre_stage
            FROM {$progTable} p
            LEFT JOIN etudiants e ON p.num_etud = e.num_carte_etud
            LEFT JOIN informations_stage ist ON ist.num_etu = COALESCE(e.num_carte_etud, p.num_etud)
            LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
            WHERE p.num_etud = ?
            ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$numEtu]);
        $soutenance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$soutenance && $this->columnExists('etudiants', 'num_ident_etud')) {
            $sql = "
                SELECT
                    p.{$idCol} AS id_programmation,
                    p.{$juryCol} AS jury_ref,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    COALESCE(e.num_carte_etud, p.num_etud) AS num_etu,
                    CONCAT(COALESCE(e.prenom_etu, ''), ' ', COALESCE(e.nom_etu, '')) AS nom_etudiant,
                    COALESCE(e.promotion_etu, '') AS promotion_etu,
                    {$presidentNom} AS president,
                    {$examinateurNom} AS examinateur,
                    {$directeurNom} AS directeur,
                    {$encadreurNom} AS encadreur,
                    CONCAT(ms.prenom, ' ', ms.Nom) AS maitre_stage
                FROM {$progTable} p
                JOIN etudiants e ON p.num_etud = e.num_carte_etud
                LEFT JOIN informations_stage ist ON ist.num_etu = e.num_carte_etud
                LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
                WHERE e.num_ident_etud = ?
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$numEtu]);
            $soutenance = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$soutenance) {
            throw new Exception('Soutenance non trouvee');
        }

        $juryRef = (string) ($soutenance['jury_ref'] ?? '');
        if ($juryRef === '') {
            throw new Exception('Jury non trouve pour cette soutenance');
        }

        $annee = $this->resolveAcademicYear();
        $idAnneeAcad = (string) ($annee['id'] ?? $annee['id_annee_acad'] ?? '');

        if ($this->tableExists('bareme_critere') && $idAnneeAcad !== '') {
            $stmtEval = $this->pdo->prepare("
                SELECT
                    ev.id_critere,
                    ev.note,
                    ce.lib_critere,
                    COALESCE(
                        bc.bareme,
                        (
                            SELECT b2.bareme
                            FROM bareme_critere b2
                            WHERE b2.id_critere = ev.id_critere
                            ORDER BY b2.id_annee_acad DESC
                            LIMIT 1
                        )
                    ) AS bareme
                FROM evaluer ev
                LEFT JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                LEFT JOIN bareme_critere bc ON bc.id_critere = ev.id_critere AND bc.id_annee_acad = ?
                WHERE ev.num_etudiant = ? AND ev.num_jury = ?
                ORDER BY ev.id_critere
            ");
            $stmtEval->execute([$idAnneeAcad, $soutenance['num_etu'], $juryRef]);
        } elseif ($this->tableExists('correspondre') && $idAnneeAcad !== '') {
            $stmtEval = $this->pdo->prepare("
                SELECT
                    ev.id_critere,
                    ev.note,
                    ce.lib_critere,
                    COALESCE(
                        cr.bareme,
                        (
                            SELECT c2.bareme
                            FROM correspondre c2
                            WHERE c2.id_critere = ev.id_critere
                            ORDER BY c2.id_annee_acad DESC
                            LIMIT 1
                        )
                    ) AS bareme
                FROM evaluer ev
                LEFT JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                LEFT JOIN correspondre cr ON cr.id_critere = ev.id_critere AND cr.id_annee_acad = ?
                WHERE ev.num_etudiant = ? AND ev.num_jury = ?
                ORDER BY ev.id_critere
            ");
            $stmtEval->execute([$idAnneeAcad, $soutenance['num_etu'], $juryRef]);
        } else {
            $stmtEval = $this->pdo->prepare("
                SELECT ev.id_critere, ev.note, ce.lib_critere, NULL AS bareme
                FROM evaluer ev
                LEFT JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                WHERE ev.num_etudiant = ? AND ev.num_jury = ?
                ORDER BY ev.id_critere
            ");
            $stmtEval->execute([$soutenance['num_etu'], $juryRef]);
        }
        $evaluations = $stmtEval->fetchAll(PDO::FETCH_ASSOC);

        $sommeNotes = 0.0;
        $sommeBaremes = 0.0;
        foreach ($evaluations as $eval) {
            $sommeNotes += (float) ($eval['note'] ?? 0);
            $sommeBaremes += (float) ($eval['bareme'] ?? 0);
        }

        $promotion = (string) ($soutenance['promotion_etu'] ?? '');
        $niveau = $promotion;
        if (stripos($promotion, 'M1') !== false) {
            $niveau = 'Master 1';
        } elseif (stripos($promotion, 'M2') !== false) {
            $niveau = 'Master 2';
        }

        $baseData = [
            'niveau' => $niveau,
            'date_soutenance' => !empty($soutenance['date_soutenance']) ? date('d/m/Y', strtotime((string) $soutenance['date_soutenance'])) : '',
            'promotion' => $promotion,
            'theme' => (string) ($soutenance['theme_soutenance'] ?? ''),
            'nom_etudiant' => trim((string) ($soutenance['nom_etudiant'] ?? '')),
            'president' => (string) ($soutenance['president'] ?? ''),
            'examinateur' => (string) ($soutenance['examinateur'] ?? ''),
            'directeur' => (string) ($soutenance['directeur'] ?? ''),
            'encadreur' => (string) ($soutenance['encadreur'] ?? ''),
            'maitre_stage' => (string) ($soutenance['maitre_stage'] ?? ''),
        ];

        $dataAnnexe1 = $baseData;
        $dataAnnexe1['criteres'] = $evaluations;
        $dataAnnexe1['note_finale'] = $sommeNotes;
        $dataAnnexe1['total_bareme'] = $sommeBaremes;

        $moyennes = $this->calculerMoyennesPourAnnexe2((string) ($soutenance['num_etu'] ?? $numEtu));

        $dataAnnexe2 = $dataAnnexe1;
        $dataAnnexe2['moyenne_master1'] = $moyennes['moyenne_master1'];
        $dataAnnexe2['moyenne_s1_master2'] = $moyennes['moyenne_s1_master2'];
        $dataAnnexe2['note_memoire'] = $sommeNotes;
        $dataAnnexe2['coef_master1'] = 2;
        $dataAnnexe2['coef_s1_master2'] = 3;
        $dataAnnexe2['coef_memoire'] = 3;
        $dataAnnexe2['total_coef'] = 8;
        $dataAnnexe2['note_finale'] = (
            $dataAnnexe2['moyenne_master1'] * $dataAnnexe2['coef_master1'] +
            $dataAnnexe2['moyenne_s1_master2'] * $dataAnnexe2['coef_s1_master2'] +
            $dataAnnexe2['note_memoire'] * $dataAnnexe2['coef_memoire']
        ) / max(1, $dataAnnexe2['total_coef']);
        $dataAnnexe2['mention'] = $this->calculerMention((float) $dataAnnexe2['note_finale']);

        $dataAnnexe3 = $dataAnnexe1;
        $m1Systeme = (float) ($moyennes['moyenne_master1'] ?? 0);
        $m1Final = ($moyenneMaster1Input !== null && $moyenneMaster1Input > 0)
            ? (float) $moyenneMaster1Input
            : $m1Systeme;
        $dataAnnexe3['moyenne_master1'] = round(max(0, $m1Final), 2);
        $dataAnnexe3['note_memoire'] = $sommeNotes;
        $dataAnnexe3['coef_master1'] = 1;
        $dataAnnexe3['coef_memoire'] = 2;
        $dataAnnexe3['total_coef'] = 3;
        $dataAnnexe3['note_finale'] = (
            $dataAnnexe3['moyenne_master1'] * $dataAnnexe3['coef_master1'] +
            $dataAnnexe3['note_memoire'] * $dataAnnexe3['coef_memoire']
        ) / max(1, $dataAnnexe3['total_coef']);
        $dataAnnexe3['mention'] = $this->calculerMention((float) $dataAnnexe3['note_finale']);

        return [
            'annexe1' => $dataAnnexe1,
            'annexe2' => $dataAnnexe2,
            'annexe3' => $dataAnnexe3,
        ];
    }

    public function calculerMention(float $noteTotale): string
    {
        if ($noteTotale >= 18) {
            return 'Honorable';
        }
        if ($noteTotale >= 16) {
            return 'Tres Bien';
        }
        if ($noteTotale >= 14) {
            return 'Bien';
        }
        if ($noteTotale >= 12) {
            return 'Assez Bien';
        }
        if ($noteTotale >= 10) {
            return 'Passable';
        }
        return 'Insuffisant';
    }

    public function getBaremeCriteres()
    {
        try {
            $annee = $this->getAnneeAcademiqueCourante();
            $idAnneeAcad = (string) ($annee['id_annee_acad'] ?? '');
            if ($idAnneeAcad === '') {
                return [];
            }
            return $this->getCriteriaRowsByYear($idAnneeAcad);
        } catch (Throwable $e) {
            error_log('Erreur getBaremeCriteres: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer l'ID de soutenance à partir du numéro étudiant
     * @param string $numEtu Numéro étudiant
     * @return string|null ID de soutenance ou null si non trouvé
     */
    public function getSoutenanceIdByNumEtu(string $numEtu): ?string
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return null;
            }

            $idCol = $this->getProgrammationIdColumn($progTable);

            $stmt = $this->pdo->prepare("
                SELECT {$idCol}
                FROM {$progTable}
                WHERE num_etud = ?
                ORDER BY date_soutenance DESC, heure_soutenance DESC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            $result = $stmt->fetchColumn();

            return $result !== false && $result !== null ? (string)$result : null;
        } catch (Throwable $e) {
            error_log('Erreur getSoutenanceIdByNumEtu: ' . $e->getMessage());
            return null;
        }
    }
    public function calculerMoyennesPourAnnexe2(string $numEtu): array
    {
        try {
            if (!$this->tableExists('notes')) {
                return [
                    'moyenne_master1' => 0.0,
                    'moyenne_s1_master2' => 0.0,
                ];
            }

            $stmt = $this->pdo->prepare("
                SELECT moyenne_M1, moyenne_M2
                FROM notes
                WHERE num_etu = ?
                ORDER BY COALESCE(date_modification, date_creation) DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row && $this->columnExists('etudiants', 'num_ident_etud')) {
                $stmt = $this->pdo->prepare("
                    SELECT n.moyenne_M1, n.moyenne_M2
                    FROM notes n
                    JOIN etudiants e ON n.num_etu = e.num_ident_etud
                    WHERE e.num_carte_etud = ?
                    ORDER BY COALESCE(n.date_modification, n.date_creation) DESC, n.id DESC
                    LIMIT 1
                ");
                $stmt->execute([$numEtu]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return [
                'moyenne_master1' => round((float) ($row['moyenne_M1'] ?? 0), 2),
                'moyenne_s1_master2' => round((float) ($row['moyenne_M2'] ?? 0), 2),
            ];
        } catch (Throwable $e) {
            error_log('Erreur calculerMoyennesPourAnnexe2: ' . $e->getMessage());
            return [
                'moyenne_master1' => 0.0,
                'moyenne_s1_master2' => 0.0,
            ];
        }
    }
}
