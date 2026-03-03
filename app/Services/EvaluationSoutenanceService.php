<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';

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
        if ($this->tableExists('programmer')) {
            return 'programmer';
        }
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
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
        if ($this->tableExists('composer_jury')) {
            return 'composer_jury';
        }
        if ($this->tableExists('enseignant_jury')) {
            return 'enseignant_jury';
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
        if ($this->tableExists('roles_jury')) {
            return 'roles_jury';
        }
        if ($this->tableExists('qualite_jury')) {
            return 'qualite_jury';
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
                $id = (int) ($row['id_role_jury'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $label = $this->normalizeRoleName($row['lib_role'] ?? '');
                if (strpos($label, 'president') !== false) {
                    $this->roleIdsCache['president'] = $id;
                } elseif (strpos($label, 'examinateur') !== false) {
                    $this->roleIdsCache['examinateur'] = $id;
                } elseif (strpos($label, 'directeur') !== false) {
                    $this->roleIdsCache['directeur'] = $id;
                } elseif (strpos($label, 'encadr') !== false) {
                    $this->roleIdsCache['encadreur'] = $id;
                } elseif (strpos($label, 'maitre') !== false) {
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
        $roleId = (int) ($roleIds[$roleKey] ?? 0);
        $juryTable = $this->getJuryTable();
        $progTable = $this->getProgrammationTable();

        if ($roleId <= 0 || $juryTable === null || $progTable === null) {
            return 'NULL';
        }

        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $progJuryCol = $this->getProgrammationJuryColumn($progTable);

        return "(SELECT CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant)
                 FROM {$juryTable} cj
                 JOIN enseignants ens ON cj.id_enseignant = ens.id_enseignant
                 WHERE cj.{$juryRefCol} = {$progAlias}.{$progJuryCol}
                 AND cj.id_qualite_jury = {$roleId}
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
                SELECT c.id_critere, c.lib_critere, COALESCE(bc.bareme, 20) AS bareme_max
                FROM critere_evaluation c
                LEFT JOIN bareme_critere bc ON c.id_critere = bc.id_critere AND bc.id_annee_acad = ?
                ORDER BY c.id_critere
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idAnneeAcad]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->tableExists('correspondre')) {
            $sql = "
                SELECT c.id_critere, c.lib_critere, COALESCE(cr.bareme, 20) AS bareme_max
                FROM critere_evaluation c
                LEFT JOIN correspondre cr ON c.id_critere = cr.id_critere AND cr.id_annee_acad = ?
                ORDER BY c.id_critere
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idAnneeAcad]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->query("
            SELECT c.id_critere, c.lib_critere, 20 AS bareme_max
            FROM critere_evaluation c
            ORDER BY c.id_critere
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBaremeForCritere($idCritere, $idAnneeAcad)
    {
        $idCritere = (int) $idCritere;
        $idAnneeAcad = (string) $idAnneeAcad;
        if ($idCritere <= 0) {
            return 20.0;
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
            }
        } catch (Throwable $e) {
            return 20.0;
        }

        return 20.0;
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
                    s.lib_salle AS nom_salle,
                    {$presidentNom} AS president_nom,
                    {$examinateurNom} AS examinateur_nom,
                    {$directeurNom} AS directeur_nom,
                    {$encadreurNom} AS encadreur_nom,
                    ist.encadrant_entreprise AS maitre_stage_nom,
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
                WHERE p.id_salle IS NOT NULL
                  AND p.date_soutenance IS NOT NULL
                  AND p.heure_soutenance IS NOT NULL
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            ";

            $stmt = $this->pdo->prepare($sql);
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
            $dateActuelle = date('Y-m-d');
            $stmt = $this->pdo->prepare("
                SELECT id_annee_acad, date_deb, date_fin
                FROM annee_academique
                WHERE ? BETWEEN date_deb AND date_fin
                ORDER BY date_deb DESC
                LIMIT 1
            ");
            $stmt->execute([$dateActuelle]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $stmt = $this->pdo->prepare("
                    SELECT id_annee_acad, date_deb, date_fin
                    FROM annee_academique
                    ORDER BY date_deb DESC
                    LIMIT 1
                ");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Erreur getAnneeAcademiqueCourante: ' . $e->getMessage());
            return null;
        }
    }

    public function getCriteresEvaluation(): array
    {
        try {
            $annee = $this->getAnneeAcademiqueCourante();
            $idAnneeAcad = (string) ($annee['id_annee_acad'] ?? '');

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
                    'id_critere' => (int) ($row->id_critere ?? 0),
                    'lib_critere' => (string) ($row->lib_critere ?? ''),
                    'bareme_max' => 20,
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

            if (empty($idAnneeAcad)) {
                $annee = $this->getAnneeAcademiqueCourante();
                $idAnneeAcad = (string) ($annee['id_annee_acad'] ?? '');
            }

            $notesValides = [];
            foreach ($criteres as $idCritere => $note) {
                if ($note === '' || $note === null || !is_numeric($note)) {
                    continue;
                }
                $notesValides[(int) $idCritere] = (float) $note;
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

            $bareme = $this->getBaremeForCritere((int) $idCritere, $idAnneeAcad);
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

            $stmt = $this->pdo->prepare("DELETE FROM evaluer WHERE num_etudiant = ?");
            $stmt->execute([$numEtu]);

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
                ist.encadrant_entreprise AS maitre_stage
            FROM {$progTable} p
            LEFT JOIN etudiants e ON p.num_etud = e.num_carte_etud
            LEFT JOIN informations_stage ist ON ist.num_etu = COALESCE(e.num_carte_etud, p.num_etud)
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
                    ist.encadrant_entreprise AS maitre_stage
                FROM {$progTable} p
                JOIN etudiants e ON p.num_etud = e.num_carte_etud
                LEFT JOIN informations_stage ist ON ist.num_etu = e.num_carte_etud
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

        $annee = $this->getAnneeAcademiqueCourante();
        $idAnneeAcad = (string) ($annee['id_annee_acad'] ?? '');

        if ($this->tableExists('bareme_critere') && $idAnneeAcad !== '') {
            $stmtEval = $this->pdo->prepare("
                SELECT ev.id_critere, ev.note, ce.lib_critere, COALESCE(bc.bareme, 20) AS bareme
                FROM evaluer ev
                LEFT JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                LEFT JOIN bareme_critere bc ON bc.id_critere = ev.id_critere AND bc.id_annee_acad = ?
                WHERE ev.num_etudiant = ? AND ev.num_jury = ?
                ORDER BY ev.id_critere
            ");
            $stmtEval->execute([$idAnneeAcad, $soutenance['num_etu'], $juryRef]);
        } else {
            $stmtEval = $this->pdo->prepare("
                SELECT ev.id_critere, ev.note, ce.lib_critere, 20 AS bareme
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
