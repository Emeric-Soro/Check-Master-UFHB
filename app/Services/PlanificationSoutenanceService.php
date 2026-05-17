<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Salle.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/NotificationService.php';

use Exception;
use Salle;

class PlanificationSoutenanceService
{
    private $pdo;
    private $salleModel;
    private $tableExistsCache = [];

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
        $this->salleModel = new Salle($this->pdo);
    }

    private function getSelectedAcademicYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function tableExists(string $tableName): bool
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
        } catch (\Throwable $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
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

    private function requireProgrammationTable(): string
    {
        $table = $this->getProgrammationTable();
        if ($table === null) {
            throw new Exception('Aucune table de programmation de soutenance disponible.');
        }

        return $table;
    }

    private function getProgrammationIdColumn(?string $table = null): string
    {
        $table = $table ?: $this->getProgrammationTable();
        return $table === 'programmer' ? 'id_programmation' : 'num_soutenance';
    }

    private function getProgrammationJuryColumn(?string $table = null): string
    {
        $table = $table ?: $this->getProgrammationTable();
        return $table === 'programmer' ? 'num_jury' : 'num_soutenance';
    }

    private function getJuryTable(): ?string
    {
        if ($this->tableExists('enseignant_jury')) {
            return 'enseignant_jury';
        }
        if ($this->tableExists('composer_jury')) {
            return 'composer_jury';
        }

        return null;
    }

    private function getJuryRefColumn(?string $juryTable = null): string
    {
        $juryTable = $juryTable ?: $this->getJuryTable();
        return $juryTable === 'composer_jury' ? 'num_jury' : 'num_soutenance';
    }

    private function buildJuryJoinClause(string $progAlias = 'p', string $juryAlias = 'ej', ?string $progTable = null): string
    {
        $juryTable = $this->getJuryTable();
        $progTable = $progTable ?: $this->getProgrammationTable();
        if ($juryTable === null || $progTable === null) {
            return '';
        }

        $juryRefColumn = $this->getJuryRefColumn($juryTable);
        $progJuryColumn = $this->getProgrammationJuryColumn($progTable);

        return " LEFT JOIN {$juryTable} {$juryAlias}
                 ON CAST({$juryAlias}.{$juryRefColumn} AS CHAR) = CAST({$progAlias}.{$progJuryColumn} AS CHAR) ";
    }

    private function getJuryPresenceExpression(string $progAlias = 'p', string $juryAlias = 'ej', ?string $progTable = null): string
    {
        $progTable = $progTable ?: $this->getProgrammationTable();
        if ($progTable === 'programmer') {
            $progJuryColumn = $this->getProgrammationJuryColumn($progTable);
            return "(COUNT(DISTINCT {$juryAlias}.id_enseignant) > 0 OR MAX(CASE WHEN {$progAlias}.{$progJuryColumn} IS NOT NULL THEN 1 ELSE 0 END) = 1)";
        }

        return "COUNT(DISTINCT {$juryAlias}.id_enseignant) > 0";
    }

    private function getProgrammationAcademicYearId($idProgrammation): ?int
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return null;
            }

            $idColumn = $this->getProgrammationIdColumn($progTable);
            $stmt = $this->pdo->prepare("
                SELECT ins.id_annee_acad
                FROM {$progTable} p
                INNER JOIN etudiants e ON (p.num_etud = e.num_carte_etud OR p.num_etud = e.num_ident_etud)
                LEFT JOIN LATERAL (
                        SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement, i2.date_inscription
                        FROM inscriptions i2 
                        WHERE i2.num_carte_etud = e.num_carte_etud 
                        ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                    ) ins ON TRUE
                WHERE p.{$idColumn} = ?
                LIMIT 1
            ");
            $stmt->execute([(string) $idProgrammation]);
            $value = $stmt->fetchColumn();

            return is_numeric($value) ? (int) $value : null;
        } catch (\Throwable $e) {
            error_log('Erreur getProgrammationAcademicYearId: ' . $e->getMessage());
            return null;
        }
    }

    private function ensureWritableProgrammation($idProgrammation, string $context): void
    {
        $targetYearId = $this->getProgrammationAcademicYearId($idProgrammation);
        $selectedYearId = $this->getSelectedAcademicYearId();

        if ($selectedYearId !== null && $targetYearId !== null && $selectedYearId !== $targetYearId) {
            throw new Exception("La soutenance ne correspond pas à l'année académique actuellement sélectionnée.");
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $targetYearId, $context);
        if (!$writeGuard['success']) {
            throw new Exception($writeGuard['message']);
        }
    }

    public function etudiantDejaPlannifie($numEtu, $excludeId = null)
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return false;
            }

            $idColumn = $this->getProgrammationIdColumn($progTable);
            $sql = "
                SELECT COUNT(*) as count
                FROM {$progTable}
                WHERE num_etud = ?
                AND id_salle IS NOT NULL
                AND date_soutenance IS NOT NULL
                AND heure_soutenance IS NOT NULL
            ";

            $params = [(string) $numEtu];
            if ($excludeId !== null && $excludeId !== '') {
                $sql .= " AND {$idColumn} != ?";
                $params[] = (string) $excludeId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0) > 0;
        } catch (\Throwable $e) {
            error_log('Erreur etudiantDejaPlannifie: ' . $e->getMessage());
            return false;
        }
    }

    public function getEtudiantsAvecJury()
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return [];
            }

            $selectedYearId = $this->getSelectedAcademicYearId();
            $idColumn = $this->getProgrammationIdColumn($progTable);
            $juryJoin = $this->buildJuryJoinClause('p', 'ej', $progTable);
            $juryPresenceExpression = $this->getJuryPresenceExpression('p', 'ej', $progTable);

            $sql = "
                SELECT
                    p.{$idColumn} as id_programmation,
                    p.num_etud as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_carte_etud as matricule_etudiant,
                    e.promotion_etu,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle,
                    COUNT(DISTINCT ej.id_enseignant) as jury_count,
                    CASE
                        WHEN p.id_salle IS NOT NULL AND p.date_soutenance IS NOT NULL AND p.heure_soutenance IS NOT NULL THEN 'complete'
                        WHEN {$juryPresenceExpression} THEN 'partial'
                        ELSE 'none'
                    END as statut_planification
                FROM {$progTable} p
                INNER JOIN etudiants e ON (p.num_etud = e.num_carte_etud OR p.num_etud = e.num_ident_etud)
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                {$juryJoin}
                WHERE 1 = 1
            ";

            if ($selectedYearId !== null && $selectedYearId > 0) {
                $sql .= " AND EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
            }

            $sql .= "
                GROUP BY
                    p.{$idColumn},
                    p.num_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.num_carte_etud,
                    e.promotion_etu,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle
                HAVING {$juryPresenceExpression}
                ORDER BY e.nom_etu ASC, e.prenom_etu ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            if ($selectedYearId !== null && $selectedYearId > 0) {
                $stmt->bindValue(':id_annee_acad', $selectedYearId, \PDO::PARAM_INT);
            }
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('Erreur getEtudiantsAvecJury: ' . $e->getMessage());
            return [];
        }
    }

    public function getEtudiantsDisponibles()
    {
        return array_values(array_filter(
            $this->getEtudiantsAvecJury(),
            static function (array $row): bool {
                return (string) ($row['statut_planification'] ?? '') !== 'complete';
            }
        ));
    }

    public function getSalles()
    {
        $rows = $this->salleModel->getAllSalles();
        return is_array($rows) ? $rows : [];
    }

    public function getPlanifications()
    {
        try {
            $progTable = $this->getProgrammationTable();
            if ($progTable === null) {
                return [];
            }

            $selectedYearId = $this->getSelectedAcademicYearId();
            $idColumn = $this->getProgrammationIdColumn($progTable);
            $sql = "
                SELECT
                    p.{$idColumn} as id_programmation,
                    p.num_etud as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_carte_etud as matricule_etudiant,
                    e.promotion_etu,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle
                FROM {$progTable} p
                INNER JOIN etudiants e ON (p.num_etud = e.num_carte_etud OR p.num_etud = e.num_ident_etud)
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.id_salle IS NOT NULL
                  AND p.date_soutenance IS NOT NULL
                  AND p.heure_soutenance IS NOT NULL
            ";

            if ($selectedYearId !== null && $selectedYearId > 0) {
                $sql .= " AND EXISTS (SELECT 1 FROM inscriptions i WHERE (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud) AND i.id_annee_acad = :id_annee_acad)";
            }

            $sql .= " ORDER BY p.date_soutenance ASC, p.heure_soutenance ASC";

            $stmt = $this->pdo->prepare($sql);
            if ($selectedYearId !== null && $selectedYearId > 0) {
                $stmt->bindValue(':id_annee_acad', $selectedYearId, \PDO::PARAM_INT);
            }
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('Erreur getPlanifications: ' . $e->getMessage());
            return [];
        }
    }

    public function planifier($idProgrammation, $idSalle, $dateSoutenance, $heureSoutenance, $editId = null)
    {
        try {
            if (empty($idProgrammation)) {
                throw new Exception('ID de programmation requis');
            }
            if (empty($idSalle)) {
                throw new Exception('Salle requise');
            }
            if (empty($dateSoutenance)) {
                throw new Exception('Date de soutenance requise');
            }
            if (empty($heureSoutenance)) {
                throw new Exception('Heure de soutenance requise');
            }

            $selectedDateTime = new \DateTime($dateSoutenance . ' ' . $heureSoutenance);
            if ($selectedDateTime <= new \DateTime()) {
                throw new Exception("La date et l'heure de soutenance doivent être dans le futur");
            }

            $progTable = $this->requireProgrammationTable();
            $idColumn = $this->getProgrammationIdColumn($progTable);
            $targetId = ($editId !== null && $editId !== '') ? $editId : $idProgrammation;

            $this->pdo->beginTransaction();
            $this->ensureWritableProgrammation($targetId, 'une planification de soutenance');

            $etudiantStmt = $this->pdo->prepare("SELECT p.num_etud, CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant, p.theme_soutenance FROM {$progTable} p LEFT JOIN etudiants e ON (p.num_etud = e.num_carte_etud OR p.num_etud = e.num_ident_etud) WHERE p.{$idColumn} = ?");
            $etudiantStmt->execute([(string) $targetId]);
            $etudiantData = $etudiantStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$etudiantData) {
                throw new Exception('Programmation non trouvée');
            }

            if ($this->etudiantDejaPlannifie($etudiantData['num_etud'] ?? '', $editId ?: null)) {
                throw new Exception('Cet étudiant a déjà une soutenance complètement planifiée');
            }

            $conflictStmt = $this->pdo->prepare("
                SELECT COUNT(*) as conflicts
                FROM {$progTable}
                WHERE id_salle = ?
                  AND date_soutenance = ?
                  AND heure_soutenance = ?
                  AND {$idColumn} != ?
            ");
            $conflictStmt->execute([
                (int) $idSalle,
                (string) $dateSoutenance,
                (string) $heureSoutenance,
                (string) $targetId,
            ]);

            if ((int) ($conflictStmt->fetch(\PDO::FETCH_ASSOC)['conflicts'] ?? 0) > 0) {
                throw new Exception('Conflit : cette salle est déjà occupée à cette date et heure');
            }

            $stmt = $this->pdo->prepare("
                UPDATE {$progTable}
                SET id_salle = ?,
                    date_soutenance = ?,
                    heure_soutenance = ?
                WHERE {$idColumn} = ?
            ");
            $success = $stmt->execute([
                (int) $idSalle,
                (string) $dateSoutenance,
                (string) $heureSoutenance,
                (string) $targetId,
            ]);

            if (!$success) {
                throw new Exception('Erreur lors de la mise à jour en base de données');
            }

            $this->pdo->commit();

            try {
                $emailService = new \EmailService();
                $stmtEns = $this->pdo->prepare("SELECT id_enseignant FROM enseignants LIMIT 1");
                $stmtEns->execute();
                $enseignantRow = $stmtEns->fetch(\PDO::FETCH_ASSOC);
                $enseignantId = $enseignantRow['id_enseignant'] ?? null;
                $role = 'encadrant';
                $etudiantNom = $etudiantData['nom_etudiant'] ?? '';
                $theme = $etudiantData['theme_soutenance'] ?? '';
                $entreprise = '';
                $ensEmail = (new \NotificationService())->getEnseignantEmail($enseignantId);
                $ensNom = (new \NotificationService())->getEnseignantNom($enseignantId);
                $templateKey = ($role === 'encadrant') ? 'AFFECTATION_ENCADRANT' : 'AFFECTATION_DIRECTEUR';
                if ($ensEmail !== null) {
                    $emailService->sendTemplate($templateKey, $ensEmail, [
                        'nom_enseignant' => htmlspecialchars($ensNom, ENT_QUOTES, 'UTF-8'),
                        'nom_etudiant' => htmlspecialchars((string)($etudiantNom ?? ''), ENT_QUOTES, 'UTF-8'),
                        'theme' => htmlspecialchars((string)($theme ?? ''), ENT_QUOTES, 'UTF-8'),
                        'entreprise' => htmlspecialchars((string)($entreprise ?? ''), ENT_QUOTES, 'UTF-8'),
                    ]);
                }
            } catch (\Throwable $e) {
                error_log('Erreur notif affectation: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => $editId ? 'Planification modifiée avec succès' : 'Soutenance planifiée avec succès',
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de la planification : ' . $e->getMessage(),
            ];
        }
    }

    public function supprimer($idProgrammation)
    {
        try {
            if (empty($idProgrammation)) {
                throw new Exception('ID de programmation requis');
            }

            $progTable = $this->requireProgrammationTable();
            $idColumn = $this->getProgrammationIdColumn($progTable);
            $this->ensureWritableProgrammation($idProgrammation, 'une planification de soutenance');

            $stmt = $this->pdo->prepare("
                UPDATE {$progTable}
                SET id_salle = NULL,
                    date_soutenance = NULL,
                    heure_soutenance = NULL
                WHERE {$idColumn} = ?
            ");
            $success = $stmt->execute([(string) $idProgrammation]);
            if (!$success) {
                throw new Exception('Erreur lors de la suppression en base de données');
            }

            return [
                'success' => true,
                'message' => 'Planification supprimée avec succès',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ];
        }
    }

    public function getPlanificationById($id)
    {
        try {
            if (!$id) {
                throw new Exception('ID requis');
            }

            $progTable = $this->requireProgrammationTable();
            $idColumn = $this->getProgrammationIdColumn($progTable);
            $stmt = $this->pdo->prepare("
                SELECT
                    p.{$idColumn} as id_programmation,
                    p.num_etud as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle
                FROM {$progTable} p
                INNER JOIN etudiants e ON (p.num_etud = e.num_carte_etud OR p.num_etud = e.num_ident_etud)
                WHERE p.{$idColumn} = ?
                LIMIT 1
            ");
            $stmt->execute([(string) $id]);
            $planification = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$planification) {
                throw new Exception('Planification non trouvée');
            }

            $selectedYearId = $this->getSelectedAcademicYearId();
            $targetYearId = $this->getProgrammationAcademicYearId($id);
            if ($selectedYearId !== null && $targetYearId !== null && $selectedYearId !== $targetYearId) {
                throw new Exception("La planification ne correspond pas à l'année académique actuellement sélectionnée.");
            }

            return [
                'success' => true,
                'data' => $planification,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ];
        }
    }
}
