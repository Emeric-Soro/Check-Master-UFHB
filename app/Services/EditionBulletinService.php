<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../utils/AcademicYear.php';
require_once __DIR__ . '/../utils/EmailService.php';

use Etudiant;
use Scolarite;
use Inscription;
use RapportEtudiant;
use CompteRendu;
use Note;
use Exception;
use PDO;
use Throwable;

class EditionBulletinService
{
    private $pdo;
    private $etudiantModel;
    private $scolariteModel;
    private $inscriptionModel;
    private $rapportModel;
    private $compteRenduModel;
    private $notesModel;
    private $tableExistsCache = [];
    private $columnExistsCache = [];
    private $emailService;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
        $this->etudiantModel = new Etudiant($this->pdo);
        $this->scolariteModel = new Scolarite($this->pdo);
        $this->inscriptionModel = new Inscription($this->pdo);
        $this->rapportModel = new RapportEtudiant($this->pdo);
        $this->compteRenduModel = new CompteRendu($this->pdo);
        $this->notesModel = new Note($this->pdo);
        $this->emailService = new \EmailService();
    }

    private function getSelectedAcademicYearId(): ?int
    {
        $selectedId = \AcademicYear::getSelectedIdFromSession();
        return ($selectedId !== null && $selectedId > 0) ? (int) $selectedId : null;
    }

    private function tableExists($tableName): bool
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $this->tableExistsCache[$tableName] = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->tableExistsCache[$tableName] = false;
        }

        return $this->tableExistsCache[$tableName];
    }

    private function columnExists($tableName, $columnName): bool
    {
        $cacheKey = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $this->columnExistsCache[$cacheKey] = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->columnExistsCache[$cacheKey] = false;
        }

        return $this->columnExistsCache[$cacheKey];
    }

    private function getProgrammationTable(): ?string
    {
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
        }

        if ($this->tableExists('programmer')) {
            return 'programmer';
        }

        return null;
    }

    private function getProgrammationIdColumn($table = null): string
    {
        $table = $table ?: $this->getProgrammationTable();
        return $table === 'programmer' ? 'id_programmation' : 'num_soutenance';
    }

    private function getProgrammationJuryColumn($table = null): string
    {
        $table = $table ?: $this->getProgrammationTable();
        return $table === 'programmer' ? 'num_jury' : 'num_soutenance';
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

    private function evaluationStudentRefExpr(string $studentAlias = 'e', string $programmationAlias = 'p'): string
    {
        if ($this->columnExists('etudiants', 'num_carte_etud')) {
            return "COALESCE(NULLIF({$studentAlias}.num_carte_etud, ''), {$programmationAlias}.num_etud)";
        }

        return "{$programmationAlias}.num_etud";
    }

    private function studentMatriculeExpr(string $studentAlias = 'e', string $programmationAlias = 'p'): string
    {
        if ($this->columnExists('etudiants', 'num_ident_etud')) {
            return "COALESCE(NULLIF({$studentAlias}.num_carte_etud, ''), NULLIF({$studentAlias}.num_ident_etud, ''), {$programmationAlias}.num_etud)";
        }

        return "COALESCE(NULLIF({$studentAlias}.num_carte_etud, ''), {$programmationAlias}.num_etud)";
    }

    private function getAcademicYearLabelById(?int $yearId): string
    {
        if ($yearId === null || $yearId <= 0) {
            return '';
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS lib_annee
                FROM annee_academique
                WHERE id_annee_acad = ?
                LIMIT 1
            ");
            $stmt->execute([$yearId]);
            return (string) ($stmt->fetchColumn() ?: '');
        } catch (Throwable $e) {
            return '';
        }
    }

    private function getAcademicYearDateBounds(?int $yearId): ?array
    {
        if ($yearId === null || $yearId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT date_deb, date_fin
                FROM annee_academique
                WHERE id_annee_acad = ?
                LIMIT 1
            ");
            $stmt->execute([$yearId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || empty($row['date_deb']) || empty($row['date_fin'])) {
                return null;
            }

            $dateDebut = date('Y-m-d', strtotime((string) $row['date_deb']));
            $dateFin = date('Y-m-d', strtotime((string) $row['date_fin'])) . ' 23:59:59';

            return [
                'start' => $dateDebut,
                'end' => $dateFin,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    private function resolveStudentReferences(string $numEtu): array
    {
        $refs = [];
        $value = trim($numEtu);
        if ($value === '') {
            return $refs;
        }

        $refs[$value] = true;

        if (!$this->columnExists('etudiants', 'num_ident_etud')) {
            return array_keys($refs);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT num_carte_etud, num_ident_etud
                FROM etudiants
                WHERE num_carte_etud = ? OR num_ident_etud = ?
                LIMIT 1
            ");
            $stmt->execute([$value, $value]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $numCarte = trim((string) ($row['num_carte_etud'] ?? ''));
                $numIdent = trim((string) ($row['num_ident_etud'] ?? ''));
                if ($numCarte !== '') {
                    $refs[$numCarte] = true;
                }
                if ($numIdent !== '') {
                    $refs[$numIdent] = true;
                }
            }
        } catch (Throwable $e) {
            // Keep only the input reference on lookup failure.
        }

        return array_keys($refs);
    }

    private function getProgrammedSoutenances(?int $yearId = null): array
    {
        $progTable = $this->getProgrammationTable();
        if ($progTable === null) {
            return [];
        }

        $idCol = $this->getProgrammationIdColumn($progTable);
        $juryCol = $this->getProgrammationJuryColumn($progTable);
        $studentJoin = $this->studentJoinCondition('e', 'p');
        $evalStudentRef = $this->evaluationStudentRefExpr('e', 'p');
        $matriculeExpr = $this->studentMatriculeExpr('e', 'p');
        $hasYearColumn = $this->columnExists($progTable, 'id_annee_acad');

        try {
            $sql = "
                SELECT
                    p.{$idCol} AS id_programmation,
                    p.{$juryCol} AS jury_ref,
                    " . ($hasYearColumn ? 'p.id_annee_acad' : 'NULL') . " AS id_annee_acad,
                    p.num_etud,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    {$evalStudentRef} AS num_etu,
                    {$matriculeExpr} AS matricule_etudiant,
                    COALESCE(e.nom_etu, '') AS nom_etu,
                    COALESCE(e.prenom_etu, '') AS prenom_etu,
                    COALESCE(e.promotion_etu, '') AS promotion_etu
                FROM {$progTable} p
                LEFT JOIN etudiants e ON {$studentJoin}
                WHERE p.id_salle IS NOT NULL
                  AND p.date_soutenance IS NOT NULL
                  AND p.heure_soutenance IS NOT NULL
            ";

            $params = [];
            if ($yearId !== null && $yearId > 0 && $hasYearColumn) {
                $sql .= " AND p.id_annee_acad = ?";
                $params[] = $yearId;
            } elseif ($yearId !== null && $yearId > 0) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE (i.num_carte_etud = e.num_carte_etud";
                if ($this->columnExists('etudiants', 'num_ident_etud')) {
                    $sql .= " OR i.num_carte_etud = e.num_ident_etud";
                }
                $sql .= ")
                      AND i.id_annee_acad = ?
                )";
                $params[] = $yearId;
            }

            $sql .= "
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC, p.{$idCol} DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Erreur getProgrammedSoutenances: ' . $e->getMessage());
            return [];
        }
    }

    private function resolveSoutenanceRow(string $numEtu, ?int $yearId = null): ?array
    {
        foreach ($this->getProgrammedSoutenances($yearId) as $row) {
            $rowNum = (string) ($row['num_etu'] ?? '');
            $rowMatricule = (string) ($row['matricule_etudiant'] ?? '');
            $rowProgrammationNum = (string) ($row['num_etud'] ?? '');

            if ($numEtu === $rowNum || $numEtu === $rowMatricule || $numEtu === $rowProgrammationNum) {
                return $row;
            }
        }

        return null;
    }

    private function getCriteriaCount(?int $yearId): int
    {
        try {
            if ($yearId !== null && $yearId > 0 && $this->tableExists('correspondre') && $this->columnExists('correspondre', 'id_annee_acad')) {
                $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT id_critere) FROM correspondre WHERE id_annee_acad = ?");
                $stmt->execute([$yearId]);
                $count = (int) $stmt->fetchColumn();
                if ($count > 0) {
                    return $count;
                }
            }

            if ($yearId !== null && $yearId > 0 && $this->tableExists('bareme_critere') && $this->columnExists('bareme_critere', 'id_annee_acad')) {
                $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT id_critere) FROM bareme_critere WHERE id_annee_acad = ?");
                $stmt->execute([$yearId]);
                $count = (int) $stmt->fetchColumn();
                if ($count > 0) {
                    return $count;
                }
            }

            if ($this->tableExists('critere_evaluation')) {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM critere_evaluation");
                return (int) $stmt->fetchColumn();
            }
        } catch (Throwable $e) {
            error_log('Erreur getCriteriaCount: ' . $e->getMessage());
        }

        return 0;
    }

    private function getSoutenanceEvaluationSummary(string $numEtu, string $juryRef, ?int $yearId = null): array
    {
        try {
            $studentRefs = $this->resolveStudentReferences($numEtu);
            if (empty($studentRefs)) {
                return [
                    'count' => 0,
                    'total' => 0.0,
                    'criteria_count' => $this->getCriteriaCount($yearId),
                    'complete' => false,
                ];
            }

            $studentPlaceholders = implode(',', array_fill(0, count($studentRefs), '?'));
            $sql = "
                SELECT COUNT(*) AS nb_notes, COALESCE(SUM(note), 0) AS total_notes
                FROM evaluer
                WHERE num_etudiant IN ({$studentPlaceholders}) AND num_jury = ?
            ";

            $params = $studentRefs;
            $params[] = $juryRef;

            $yearBounds = $this->getAcademicYearDateBounds($yearId);
            if ($yearBounds !== null && $this->columnExists('evaluer', 'date_eval')) {
                $sql .= " AND date_eval >= ? AND date_eval <= ?";
                $params[] = $yearBounds['start'];
                $params[] = $yearBounds['end'];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $count = (int) ($row['nb_notes'] ?? 0);
            $total = (float) ($row['total_notes'] ?? 0);
            $criteriaCount = $this->getCriteriaCount($yearId);

            return [
                'count' => $count,
                'total' => $total,
                'criteria_count' => $criteriaCount,
                'complete' => $criteriaCount > 0 && $count >= $criteriaCount,
            ];
        } catch (Throwable $e) {
            error_log('Erreur getSoutenanceEvaluationSummary: ' . $e->getMessage());
            return [
                'count' => 0,
                'total' => 0.0,
                'criteria_count' => $this->getCriteriaCount($yearId),
                'complete' => false,
            ];
        }
    }

    private function getNoteRecordForStudent(string $numEtu, ?int $yearId = null): ?array
    {
        try {
            $sql = "
                SELECT moyenne_M1, moyenne_M2, id_annee_acad, date_creation, date_modification
                FROM notes
                WHERE num_etu = ?
            ";
            $params = [$numEtu];
            if ($yearId !== null && $yearId > 0 && $this->columnExists('notes', 'id_annee_acad')) {
                $sql .= " AND id_annee_acad = ?";
                $params[] = $yearId;
            }
            $sql .= " ORDER BY COALESCE(date_modification, date_creation) DESC, id_annee_acad DESC LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }

            if ($this->columnExists('etudiants', 'num_ident_etud')) {
                $sql = "
                    SELECT n.moyenne_M1, n.moyenne_M2, n.id_annee_acad, n.date_creation, n.date_modification
                    FROM notes n
                    JOIN etudiants e ON n.num_etu = e.num_ident_etud
                    WHERE e.num_carte_etud = ?
                ";
                $params = [$numEtu];
                if ($yearId !== null && $yearId > 0 && $this->columnExists('notes', 'id_annee_acad')) {
                    $sql .= " AND n.id_annee_acad = ?";
                    $params[] = $yearId;
                }
                $sql .= " ORDER BY COALESCE(n.date_modification, n.date_creation) DESC, n.id_annee_acad DESC LIMIT 1";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return $row;
                }
            }
        } catch (Throwable $e) {
            error_log('Erreur getNoteRecordForStudent: ' . $e->getMessage());
        }

        return null;
    }

    private function calculateBulletinAverage(string $numEtu, string $juryRef, ?int $yearId = null): float
    {
        $evaluation = $this->getSoutenanceEvaluationSummary($numEtu, $juryRef, $yearId);
        $moyenneSoutenance = (float) ($evaluation['total'] ?? 0);
        $noteRow = $this->getNoteRecordForStudent($numEtu, $yearId);

        if ($noteRow && isset($noteRow['moyenne_M1'], $noteRow['moyenne_M2'])) {
            $moyenneMaster = ((float) $noteRow['moyenne_M1'] + (float) $noteRow['moyenne_M2']) / 2;
            return round(($moyenneMaster + $moyenneSoutenance) / 2, 2);
        }

        return round($moyenneSoutenance, 2);
    }

    private function calculerMention(float $moyenne): string
    {
        if ($moyenne >= 18) {
            return 'Honorable';
        }
        if ($moyenne >= 16) {
            return 'Tres Bien';
        }
        if ($moyenne >= 14) {
            return 'Bien';
        }
        if ($moyenne >= 12) {
            return 'Assez Bien';
        }
        if ($moyenne >= 10) {
            return 'Passable';
        }
        return 'Insuffisant';
    }

    private function getLatestBulletinForStudent(string $numEtu, ?int $yearId = null): ?array
    {
        try {
            $studentRefs = $this->resolveStudentReferences($numEtu);
            if (empty($studentRefs)) {
                return null;
            }

            $placeholders = implode(',', array_fill(0, count($studentRefs), '?'));
            $sql = "
                SELECT id_CR, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR
                FROM compte_rendu
                WHERE num_etu IN ({$placeholders}) AND nom_CR LIKE 'BULLETIN_%'
            ";

            $params = $studentRefs;
            $yearBounds = $this->getAcademicYearDateBounds($yearId);
            if ($yearBounds !== null) {
                $sql .= " AND date_CR >= ? AND date_CR <= ?";
                $params[] = $yearBounds['start'];
                $params[] = $yearBounds['end'];
            }

            $sql .= "
                ORDER BY date_CR DESC, id_CR DESC
                LIMIT 1
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Erreur getLatestBulletinForStudent: ' . $e->getMessage());
            return null;
        }
    }

    public function getIndexData(): array
    {
        return [
            'etudiants' => $this->getEtudiantsWithBulletinStatus(),
            'anneesAcademiques' => $this->getAnneesAcademiques(),
            'selectedYearId' => $this->getSelectedAcademicYearId(),
        ];
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
        } catch (Exception $e) {
            error_log('Erreur getAnneesAcademiques: ' . $e->getMessage());
            return [];
        }
    }

    public function getEtudiantsWithBulletinStatus(): array
    {
        try {
            $selectedYearId = $this->getSelectedAcademicYearId();
            $soutenances = $this->getProgrammedSoutenances($selectedYearId);
            $result = [];

            foreach ($soutenances as $soutenance) {
                $numEtu = (string) ($soutenance['num_etu'] ?? '');
                $juryRef = (string) ($soutenance['jury_ref'] ?? '');
                if ($numEtu === '' || $juryRef === '') {
                    continue;
                }

                $yearId = isset($soutenance['id_annee_acad']) && is_numeric($soutenance['id_annee_acad'])
                    ? (int) $soutenance['id_annee_acad']
                    : $selectedYearId;

                $evaluation = $this->getSoutenanceEvaluationSummary($numEtu, $juryRef, $yearId);
                $eligible = $this->estEligiblePourBulletin($numEtu);
                $latestBulletin = $this->getLatestBulletinForStudent($numEtu, $yearId);
                $hasBulletin = $latestBulletin !== null;
                $status = $hasBulletin ? 'genere' : ($eligible ? 'eligible' : 'non_eligible');
                $moyenne = $evaluation['count'] > 0 ? $this->calculateBulletinAverage($numEtu, $juryRef, $yearId) : 0.0;
                $mention = $evaluation['complete'] ? $this->calculerMention($moyenne) : '-';
                $promotion = trim((string) ($soutenance['promotion_etu'] ?? ''));
                $niveau = '-';
                if (stripos($promotion, 'M2') !== false) {
                    $niveau = 'M2';
                } elseif (stripos($promotion, 'M1') !== false) {
                    $niveau = 'M1';
                }

                $result[] = [
                    'num_etu' => $numEtu,
                    'matricule' => (string) ($soutenance['matricule_etudiant'] ?? $numEtu),
                    'nom' => (string) ($soutenance['nom_etu'] ?? ''),
                    'prenom' => (string) ($soutenance['prenom_etu'] ?? ''),
                    'promotion' => $promotion,
                    'niveau' => $niveau,
                    'semestre' => 'S1',
                    'moyenne' => $moyenne,
                    'mention' => $mention,
                    'eligible' => $eligible,
                    'has_bulletin' => $hasBulletin,
                    'status' => $status,
                    'bulletin_id' => $latestBulletin['id_CR'] ?? null,
                    'annee_id' => $yearId,
                    'annee_label' => $this->getAcademicYearLabelById($yearId),
                    'theme' => (string) ($soutenance['theme_soutenance'] ?? ''),
                    'evaluation_complete' => (bool) ($evaluation['complete'] ?? false),
                ];
            }

            return $result;
        } catch (Exception $e) {
            error_log('Erreur getEtudiantsWithBulletinStatus: ' . $e->getMessage());
            return [];
        }
    }

    public function estEligiblePourBulletin(string $numEtu): bool
    {
        try {
            $selectedYearId = $this->getSelectedAcademicYearId();
            $soutenance = $this->resolveSoutenanceRow($numEtu, $selectedYearId);
            if (!$soutenance) {
                return false;
            }

            $targetNumEtu = (string) ($soutenance['num_etu'] ?? $numEtu);
            $juryRef = (string) ($soutenance['jury_ref'] ?? '');
            $yearId = isset($soutenance['id_annee_acad']) && is_numeric($soutenance['id_annee_acad'])
                ? (int) $soutenance['id_annee_acad']
                : $selectedYearId;

            $scolarite = $this->scolariteModel->getScolariteEtudiant($targetNumEtu);
            if (!$scolarite || (float) ($scolarite['reste_a_payer'] ?? 0) > 0) {
                return false;
            }

            $noteRow = $this->getNoteRecordForStudent($targetNumEtu, $yearId);
            if (!$noteRow || (float) ($noteRow['moyenne_M2'] ?? 0) < 10) {
                return false;
            }

            if (!$this->rapportModel->estRapportValideCommunication($targetNumEtu)) {
                return false;
            }

            if ($this->rapportModel->getDerniereDecisionCommission($targetNumEtu) !== 'favorable') {
                return false;
            }

            $evaluation = $this->getSoutenanceEvaluationSummary($targetNumEtu, $juryRef, $yearId);
            return (bool) ($evaluation['complete'] ?? false);
        } catch (Exception $e) {
            error_log('Erreur estEligiblePourBulletin: ' . $e->getMessage());
            return false;
        }
    }

    public function existeBulletinPourEtudiant(string $numEtu): bool
    {
        return $this->getLatestBulletinForStudent($numEtu, $this->getSelectedAcademicYearId()) !== null;
    }

    public function genererBulletin(string $numEtu): array
    {
        if (!$this->estEligiblePourBulletin($numEtu)) {
            return ['success' => false, 'message' => "L'étudiant n'est pas éligible pour le bulletin."];
        }

        $contenu = $this->genererContenuBulletin($numEtu);
        if ($contenu === '') {
            return ['success' => false, 'message' => 'Impossible de générer le contenu du bulletin.'];
        }

        $nomCR = 'BULLETIN_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $numEtu) . '_' . date('Y-m-d_His');
        $pdfResult = $this->exporterPdf($contenu, $nomCR);
        if (!$pdfResult['success']) {
            return ['success' => false, 'message' => 'Erreur lors de la génération du PDF : ' . ($pdfResult['message'] ?? '')];
        }

        $idCR = $this->compteRenduModel->creer(
            $numEtu,
            $nomCR,
            $contenu,
            (string) ($pdfResult['path'] ?? ''),
            date('Y-m-d H:i:s'),
            []
        );

        if (!$idCR) {
            return ['success' => false, 'message' => "Erreur lors de l'enregistrement du bulletin."];
        }

        $this->persistBulletinDocument((int) $idCR, $nomCR, (string) ($pdfResult['path'] ?? ''));

        // Notification
        $this->notifierBulletinDisponible($numEtu, 'Semestre Final', 0.0, 'Validation');

        return [
            'success' => true,
            'message' => 'Bulletin généré avec succès.',
            'bulletinId' => $idCR,
        ];
    }

    public function genererContenuBulletin(string $numEtu): string
    {
        $selectedYearId = $this->getSelectedAcademicYearId();
        $soutenance = $this->resolveSoutenanceRow($numEtu, $selectedYearId);
        if (!$soutenance) {
            return '';
        }

        $targetNumEtu = (string) ($soutenance['num_etu'] ?? $numEtu);
        $juryRef = (string) ($soutenance['jury_ref'] ?? '');
        $yearId = isset($soutenance['id_annee_acad']) && is_numeric($soutenance['id_annee_acad'])
            ? (int) $soutenance['id_annee_acad']
            : $selectedYearId;
        $evaluation = $this->getSoutenanceEvaluationSummary($targetNumEtu, $juryRef, $yearId);
        $moyenneSoutenance = (float) ($evaluation['total'] ?? 0);
        $noteRow = $this->getNoteRecordForStudent($targetNumEtu, $yearId);

        $moyenneMaster1 = (float) ($noteRow['moyenne_M1'] ?? 0);
        $moyenneMaster2 = (float) ($noteRow['moyenne_M2'] ?? 0);
        $moyenneMaster = ($moyenneMaster1 > 0 || $moyenneMaster2 > 0)
            ? round(($moyenneMaster1 + $moyenneMaster2) / 2, 2)
            : 0.0;
        $moyenneGenerale = $moyenneMaster > 0
            ? round(($moyenneMaster + $moyenneSoutenance) / 2, 2)
            : round($moyenneSoutenance, 2);

        $nom = trim((string) ($soutenance['nom_etu'] ?? ''));
        $prenom = trim((string) ($soutenance['prenom_etu'] ?? ''));
        $promotion = trim((string) ($soutenance['promotion_etu'] ?? ''));
        $anneeLabel = $this->getAcademicYearLabelById($yearId);
        $dateSoutenance = !empty($soutenance['date_soutenance'])
            ? date('d/m/Y', strtotime((string) $soutenance['date_soutenance']))
            : date('d/m/Y');
        $mention = $this->calculerMention($moyenneGenerale);
        $dateGeneration = date('d/m/Y H:i');
        $theme = (string) ($soutenance['theme_soutenance'] ?? 'Non défini');
        $matricule = (string) ($soutenance['matricule_etudiant'] ?? $targetNumEtu);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de {$prenom} {$nom}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 36px; color: #18324b; }
        h1, h2 { margin: 0 0 8px; }
        .header { text-align: center; margin-bottom: 28px; }
        .section { margin-bottom: 18px; }
        .label { font-weight: bold; }
        .box { border: 1px solid #c9d9e8; border-radius: 8px; padding: 16px; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { border: 1px solid #d5e0ea; padding: 10px 12px; }
        .footer { margin-top: 26px; font-size: 12px; color: #61758a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bulletin de soutenance</h1>
        <h2>{$prenom} {$nom}</h2>
        <div>Année académique : {$anneeLabel}</div>
    </div>

    <div class="section box">
        <p><span class="label">Matricule :</span> {$matricule}</p>
        <p><span class="label">Promotion :</span> {$promotion}</p>
        <p><span class="label">Thème de soutenance :</span> {$theme}</p>
        <p><span class="label">Date de soutenance :</span> {$dateSoutenance}</p>
    </div>

    <div class="section box">
        <table>
            <tr>
                <td><span class="label">Note soutenance</span></td>
                <td>{$moyenneSoutenance} / 20</td>
            </tr>
            <tr>
                <td><span class="label">Moyenne Master 1</span></td>
                <td>{$moyenneMaster1} / 20</td>
            </tr>
            <tr>
                <td><span class="label">Moyenne Semestre M2</span></td>
                <td>{$moyenneMaster2} / 20</td>
            </tr>
            <tr>
                <td><span class="label">Moyenne générale</span></td>
                <td>{$moyenneGenerale} / 20</td>
            </tr>
            <tr>
                <td><span class="label">Mention</span></td>
                <td>{$mention}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Généré le {$dateGeneration}
    </div>
</body>
</html>
HTML;
    }

    public function exporterPdf(string $contenuCR, string $nomCR): array
    {
        if ($contenuCR === '') {
            return ['success' => false, 'message' => 'Le contenu du bulletin est vide.'];
        }

        try {
            if (strpos($contenuCR, '<!DOCTYPE html>') !== false || strpos($contenuCR, '<html') !== false) {
                $html = $contenuCR;
            } else {
                $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $contenuCR . '</body></html>';
            }

            $pdfGen = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../../storage',
                __DIR__ . '/../../public/assets/img/logo.png'
            );
            $pdf = $pdfGen->createDocument('P', 'A4', $nomCR);
            $pdf->AddPage();
            $pdfGen->writeHtml($pdf, $html);

            $pdfOutput = $pdf->Output($nomCR . '.pdf', 'S');
            $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomCR) . '.pdf';
            $pdfDir = __DIR__ . '/../../storage/documents/bulletins/';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }
            $pdfPath = $pdfDir . $pdfName;
            file_put_contents($pdfPath, $pdfOutput);

            return [
                'success' => true,
                'pdf' => $pdfOutput,
                'path' => $pdfPath,
                'message' => 'PDF généré avec succès.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage(),
            ];
        }
    }

    private function persistBulletinDocument(int $idCR, string $nomCR, string $pdfPath): void
    {
        if ($idCR <= 0 || $pdfPath === '' || !is_file($pdfPath)) {
            return;
        }

        try {
            $storage = new \App\Services\Document\DocumentStorageService($this->pdo, dirname(__DIR__, 2));
            $storage->storeFileFromPath(
                'bulletin',
                $pdfPath,
                'compte_rendu',
                (string) $idCR,
                isset($_SESSION['id_utilisateur']) ? (int) $_SESSION['id_utilisateur'] : null,
                null,
                null,
                true
            );
        } catch (\Throwable $e) {
            error_log('Erreur persistBulletinDocument: ' . $e->getMessage());
        }
    }

    public function getHistoriqueBulletins(string $numEtu): array
    {
        try {
            $studentRefs = $this->resolveStudentReferences($numEtu);
            if (empty($studentRefs)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($studentRefs), '?'));
            $sql = "
                SELECT id_CR, nom_CR, date_CR, chemin_fichier_pdf
                FROM compte_rendu
                WHERE num_etu IN ({$placeholders}) AND nom_CR LIKE 'BULLETIN_%'
            ";

            $params = $studentRefs;
            $yearBounds = $this->getAcademicYearDateBounds($this->getSelectedAcademicYearId());
            if ($yearBounds !== null) {
                $sql .= " AND date_CR >= ? AND date_CR <= ?";
                $params[] = $yearBounds['start'];
                $params[] = $yearBounds['end'];
            }

            $sql .= "
                ORDER BY date_CR DESC, id_CR DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getHistoriqueBulletins: ' . $e->getMessage());
            return [];
        }
    }

    public function supprimerBulletin(int $idCR): array
    {
        return ['success' => false, 'message' => "La suppression des bulletins n'est pas autorisée pour conserver l'historique."];
    }

    public function getBulletinById(int $idCR): ?array
    {
        try {
            $sql = "
                SELECT id_CR, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR
                FROM compte_rendu
                WHERE id_CR = ? AND nom_CR LIKE 'BULLETIN_%'
            ";

            $params = [$idCR];
            $yearBounds = $this->getAcademicYearDateBounds($this->getSelectedAcademicYearId());
            if ($yearBounds !== null) {
                $sql .= " AND date_CR >= ? AND date_CR <= ?";
                $params[] = $yearBounds['start'];
                $params[] = $yearBounds['end'];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Erreur getBulletinById: ' . $e->getMessage());
            return null;
        }
    }

    public function notifierBulletinDisponible(string $numEtu, string $semestre, float $moyenne, string $credits): void
    {
        try {
            $stmt = $this->pdo->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
            $stmt->execute([$numEtu, $numEtu]);
            $etu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $this->emailService->sendTemplate('BULLETIN_NOTES', $etu['email_etu'], [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'semestre' => htmlspecialchars($semestre, ENT_QUOTES, 'UTF-8'),
                'moyenne' => number_format($moyenne, 2) . '/20',
                'credits' => htmlspecialchars($credits, ENT_QUOTES, 'UTF-8'),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierBulletinDisponible: ' . $e->getMessage());
        }
    }
}
