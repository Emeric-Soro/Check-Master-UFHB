<?php

declare(strict_types=1);

namespace CheckMaster\Services;

require_once __DIR__ . '/../utils/AcademicYear.php';
require_once __DIR__ . '/UeReferentielService.php';

use PDO;
use RuntimeException;

/**
 * Service central des évaluations détaillées du semestre M2/S1.
 */
final class EvaluationS3Service
{
    public function __construct(
        private readonly PDO $db,
        private readonly UeReferentielService $ueService
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function getAcademicYears(): array
    {
        $stmt = $this->db->query(
            'SELECT id_annee_acad, date_deb, date_fin
             FROM annee_academique ORDER BY date_deb DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getStudents(?int $yearId = null, string $search = ''): array
    {
        $params = [];
        $where = [];
        if ($yearId !== null && $yearId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM inscriptions i WHERE i.num_carte_etud = e.num_carte_etud AND i.id_annee_acad = :year_id)';
            $params[':year_id'] = $yearId;
        }
        $search = trim($search);
        if ($search !== '') {
            $where[] = '(e.num_carte_etud LIKE :search OR e.num_ident_etud LIKE :search OR e.nom_etu LIKE :search OR e.prenom_etu LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = 'SELECT e.num_carte_etud, e.num_ident_etud, e.nom_etu, e.prenom_etu,
                       e.email_etu, e.nouveau, e.promotion_etu
                FROM etudiants e';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.nom_etu, e.prenom_etu LIMIT 500';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findStudent(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.num_carte_etud, e.num_ident_etud, e.nom_etu, e.prenom_etu,
                    e.email_etu, e.nouveau, e.promotion_etu
             FROM etudiants e
             WHERE e.num_carte_etud = :identifier OR e.num_ident_etud = :identifier
             LIMIT 1'
        );
        $stmt->execute([':identifier' => trim($identifier)]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($student)) {
            return null;
        }

        // Sans année cible, conserver la projection calculée pour l'année active.
        // Le calcul métier ne peut déterminer Nouveau/Redoublant qu'en comparant
        // l'historique à une année précise ; les générateurs de documents utilisent
        // précisément cette recherche sans année.
        $student['nouveau'] = isset($student['nouveau']) ? (int) $student['nouveau'] : null;
        return $student;
    }

    /** @return array<string, mixed> */
    public function getIndexData(array $query): array
    {
        $years = $this->getAcademicYears();
        $yearId = isset($query['annee']) && is_numeric($query['annee'])
            ? (int) $query['annee']
            : $this->resolveDefaultYearId($years);
        $studentId = trim((string) ($query['student'] ?? ''));
        $student = $studentId !== '' ? $this->findStudent($studentId) : null;
        if ($student !== null) {
            $student['nouveau'] = $this->refreshStudentStatus((string) $student['num_carte_etud'], $yearId);
        }

        $ues = $this->getUesForYear($yearId);
        $grid = $student !== null && $yearId > 0
            ? $this->getGrid((string) $student['num_carte_etud'], $yearId, $ues)
            : $this->emptyGrid($ues);

        return [
            'years' => $years,
            'year_id' => $yearId,
            'students' => $this->getStudents($yearId, (string) ($query['search'] ?? '')),
            'student' => $student,
            'ues' => $ues,
            'grid' => $grid,
            'semester_code' => UeReferentielService::SEMESTER_CODE,
            'semester_aliases' => $this->ueService->getSemesters(),
            'import_batch' => null,
        ];
    }

    /** @return array<string, mixed> */
    public function getGrid(string $studentId, int $yearId, ?array $ues = null): array
    {
        $student = $this->findStudentByCard($studentId);
        if ($student === null) {
            throw new RuntimeException('Étudiant introuvable.');
        }
        $studentId = (string) $student['num_carte_etud'];
        $ues = $ues ?? $this->getUesForYear($yearId);

        $stmt = $this->db->prepare(
            'SELECT e.id_evaluation, e.id_ue, e.note_obtenue_ue, e.date_note,
                    e.session_normale, u.code_ue, u.libelle_ue, u.credit_ue
             FROM evaluation_s3 e
             INNER JOIN ue u ON u.id_ue = e.id_ue
             WHERE e.num_etu = :student AND e.id_annee_acad = :year
             ORDER BY u.ordre_ue, u.code_ue, e.session_normale DESC'
        );
        $stmt->execute([':student' => $studentId, ':year' => $yearId]);

        $byUe = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $byUe[(int) $row['id_ue']][((int) $row['session_normale']) === 1 ? 'normal' : 'rattrapage'] = [
                'id_evaluation' => (int) $row['id_evaluation'],
                'note' => (float) $row['note_obtenue_ue'],
                'date' => (string) $row['date_note'],
                'credit' => (float) $row['credit_ue'],
            ];
        }

        $rows = [];
        $normalTotal = 0.0;
        $normalCredits = 0.0;
        $rattrapageTotal = 0.0;
        $rattrapageCredits = 0.0;
        foreach ($ues as $ue) {
            $id = (int) $ue['id_ue'];
            $normal = $byUe[$id]['normal'] ?? null;
            $rattrapage = $byUe[$id]['rattrapage'] ?? null;
            $credit = (float) $ue['credit_ue'];
            $normalPoints = $normal !== null ? round(((float) $normal['note'] / 20) * $credit, 2) : null;
            $rattrapagePoints = $rattrapage !== null ? round(((float) $rattrapage['note'] / 20) * $credit, 2) : null;
            if ($normal !== null) {
                $normalTotal += (float) $normalPoints;
                $normalCredits += $credit;
            }
            if ($rattrapage !== null) {
                $rattrapageTotal += (float) $rattrapagePoints;
                $rattrapageCredits += $credit;
            }
            $rows[] = [
                'ue' => $ue,
                'normal' => $normal,
                'normal_points' => $normalPoints,
                'rattrapage' => $rattrapage,
                'rattrapage_points' => $rattrapagePoints,
            ];
        }

        return [
            'rows' => $rows,
            'normal_total' => round($normalTotal, 2),
            'normal_credits' => round($normalCredits, 2),
            'normal_average' => $normalCredits > 0 ? round(($normalTotal / $normalCredits) * 20, 2) : null,
            'rattrapage_total' => round($rattrapageTotal, 2),
            'rattrapage_credits' => round($rattrapageCredits, 2),
            'rattrapage_average' => $rattrapageCredits > 0 ? round(($rattrapageTotal / $rattrapageCredits) * 20, 2) : null,
            'expected_credits' => round(array_sum(array_map(static fn(array $ue): float => (float) $ue['credit_ue'], $ues)), 2),
            'credits_are_compliant' => abs(array_sum(array_map(static fn(array $ue): float => (float) $ue['credit_ue'], $ues)) - 30.0) < 0.001,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function getUesForYear(int $yearId): array
    {
        if ($yearId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare('SELECT date_deb FROM annee_academique WHERE id_annee_acad = :year LIMIT 1');
        $stmt->execute([':year' => $yearId]);
        $date = $stmt->fetchColumn();
        if ($date === false) {
            throw new RuntimeException('Année académique introuvable.');
        }
        return $this->ueService->getActiveUes((string) $date);
    }

    /** @return array<string, mixed> */
    public function saveEvaluations(string $studentIdentifier, int $yearId, array $payload, int $userId): array
    {
        $student = $this->findStudentByCard($studentIdentifier) ?? $this->findStudent($studentIdentifier);
        if ($student === null) {
            throw new RuntimeException('Étudiant introuvable.');
        }
        $studentId = (string) $student['num_carte_etud'];
        if ($yearId <= 0) {
            throw new RuntimeException('Année académique invalide.');
        }

        if (class_exists('AcademicYear')) {
            $guard = \AcademicYear::ensureWritableYear($this->db, $yearId, 'les évaluations M2/S1');
            if (!($guard['success'] ?? false)) {
                throw new RuntimeException((string) ($guard['message'] ?? 'Cette année académique est en lecture seule.'));
            }
        }

        $ues = $this->getUesForYear($yearId);
        $ueIds = [];
        foreach ($ues as $ue) {
            $ueIds[(int) $ue['id_ue']] = true;
        }

        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare(
                'DELETE FROM evaluation_s3
                 WHERE num_etu = :student AND id_ue = :ue AND id_annee_acad = :year AND session_normale = :session'
            );
            $upsert = $this->db->prepare(
                'INSERT INTO evaluation_s3
                    (num_etu, id_ue, id_annee_acad, note_obtenue_ue, date_note, session_normale, created_by, updated_by, date_modification)
                 VALUES (:student, :ue, :year, :note, :date_note, :session, :created_by, :updated_by, NOW())
                 ON DUPLICATE KEY UPDATE
                    note_obtenue_ue = VALUES(note_obtenue_ue),
                    date_note = VALUES(date_note),
                    updated_by = VALUES(updated_by),
                    date_modification = NOW()'
            );

            foreach ($payload as $ueId => $sessions) {
                $ueId = (int) $ueId;
                if (!isset($ueIds[$ueId]) || !is_array($sessions)) {
                    throw new RuntimeException('Une UE sélectionnée n’appartient pas au référentiel M2/S1.');
                }
                foreach (['normal' => 1, 'rattrapage' => 0] as $key => $session) {
                    $row = is_array($sessions[$key] ?? null) ? $sessions[$key] : [];
                    $rawNote = trim((string) ($row['note'] ?? ''));
                    if ($rawNote === '') {
                        $delete->execute([
                            ':student' => $studentId,
                            ':ue' => $ueId,
                            ':year' => $yearId,
                            ':session' => $session,
                        ]);
                        continue;
                    }
                    $note = str_replace(',', '.', $rawNote);
                    if (!is_numeric($note) || (float) $note < 0 || (float) $note > 20) {
                        throw new RuntimeException('Chaque note doit être comprise entre 0 et 20.');
                    }
                    $date = $this->normalizeDate((string) ($row['date'] ?? '')) ?? date('Y-m-d');
                    $upsert->execute([
                        ':student' => $studentId,
                        ':ue' => $ueId,
                        ':year' => $yearId,
                        ':note' => round((float) $note, 2),
                        ':date_note' => $date,
                        ':session' => $session,
                        ':created_by' => $userId > 0 ? $userId : null,
                        ':updated_by' => $userId > 0 ? $userId : null,
                    ]);
                }
            }

            $status = $this->refreshStudentStatus($studentId, $yearId);
            $this->db->commit();
            return ['success' => true, 'status_nouveau' => $status, 'message' => 'Les évaluations M2/S1 ont été enregistrées.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function persistImportedRows(array $rows, int $batchId, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO evaluation_s3
                (num_etu, id_ue, id_annee_acad, note_obtenue_ue, date_note, session_normale, id_batch_import, created_by, updated_by, date_modification)
             VALUES (:student, :ue, :year, :note, :date_note, :session, :batch, :created_by, :updated_by, NOW())
             ON DUPLICATE KEY UPDATE
                note_obtenue_ue = VALUES(note_obtenue_ue), date_note = VALUES(date_note),
                id_batch_import = VALUES(id_batch_import), updated_by = VALUES(updated_by), date_modification = NOW()'
        );
        $count = 0;
        foreach ($rows as $row) {
            $stmt->execute([
                ':student' => $row['num_etu'],
                ':ue' => (int) $row['id_ue'],
                ':year' => (int) $row['id_annee_acad'],
                ':note' => $row['note'],
                ':date_note' => $row['date_note'],
                ':session' => (int) $row['session_normale'],
                ':batch' => $batchId,
                ':created_by' => $userId > 0 ? $userId : null,
                ':updated_by' => $userId > 0 ? $userId : null,
            ]);
            $count++;
        }
        return $count;
    }

    private function findStudentByCard(string $studentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM etudiants WHERE num_carte_etud = :student LIMIT 1');
        $stmt->execute([':student' => trim($studentId)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function refreshStudentStatus(string $studentId, ?int $yearId): bool
    {
        $params = [':student' => $studentId];
        $prior = 0;
        if ($yearId !== null && $yearId > 0) {
            $params[':year'] = $yearId;
            $stmt = $this->db->prepare(
                'SELECT 1 FROM evaluation_s3 WHERE num_etu = :student AND id_annee_acad < :year LIMIT 1'
            );
            $stmt->execute($params);
            $prior = $stmt->fetchColumn() !== false ? 1 : 0;
            if ($prior === 0) {
                $stmt = $this->db->prepare(
                    'SELECT 1
                     FROM inscriptions i
                     LEFT JOIN niveau_etude n ON n.id_niv_etude = i.id_niv_etude
                     WHERE i.num_carte_etud = :student
                       AND i.id_annee_acad < :year
                       AND (UPPER(n.lib_niv_etude) LIKE "%M2%" OR UPPER(n.lib_niv_etude) LIKE "%MASTER 2%")
                     LIMIT 1'
                );
                $stmt->execute($params);
                $prior = $stmt->fetchColumn() !== false ? 1 : 0;
            }
        }
        $nouveau = $prior === 0;

        if ($yearId !== null && $yearId > 0) {
            $stmt = $this->db->prepare(
                'INSERT INTO cycle_etudiant_statut (num_etu, id_annee_acad, nouveau_m2)
                 VALUES (:student, :year, :nouveau)
                 ON DUPLICATE KEY UPDATE nouveau_m2 = VALUES(nouveau_m2), date_maj = NOW()'
            );
            $stmt->execute([':student' => $studentId, ':year' => $yearId, ':nouveau' => $nouveau ? 1 : 0]);
            $stmt = $this->db->prepare('UPDATE etudiants SET nouveau = :nouveau WHERE num_carte_etud = :student');
            $stmt->execute([':nouveau' => $nouveau ? 1 : 0, ':student' => $studentId]);
        }

        return $nouveau;
    }

    /** @param array<int, array<string, mixed>> $ues */
    private function emptyGrid(array $ues): array
    {
        return [
            'rows' => array_map(static fn(array $ue): array => [
                'ue' => $ue,
                'normal' => null,
                'normal_points' => null,
                'rattrapage' => null,
                'rattrapage_points' => null,
            ], $ues),
            'normal_total' => 0.0,
            'normal_credits' => 0.0,
            'normal_average' => null,
            'rattrapage_total' => 0.0,
            'rattrapage_credits' => 0.0,
            'rattrapage_average' => null,
            'expected_credits' => round(array_sum(array_map(static fn(array $ue): float => (float) $ue['credit_ue'], $ues)), 2),
            'credits_are_compliant' => abs(array_sum(array_map(static fn(array $ue): float => (float) $ue['credit_ue'], $ues)) - 30.0) < 0.001,
        ];
    }

    /** @param array<int, array<string, mixed>> $years */
    private function resolveDefaultYearId(array $years): int
    {
        if (class_exists('AcademicYear')) {
            $selected = \AcademicYear::getSelectedIdFromSession();
            if ($selected !== null) {
                return $selected;
            }
        }
        return isset($years[0]['id_annee_acad']) ? (int) $years[0]['id_annee_acad'] : 0;
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date !== false && (!is_array($errors) || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0))) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }
}
