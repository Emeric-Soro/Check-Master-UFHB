<?php

declare(strict_types=1);

namespace CheckMaster\Services;

use DateTimeImmutable;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/** Import prévisualisé, auditable et rejouable des évaluations Excel. */
final class EvaluationS3ImportService
{
    private const REQUIRED_HEADERS = [
        'annee_academique',
        'semestre',
        'identifiant_etudiant',
        'code_ue',
        'note',
        'date_evaluation',
        'session_normale',
    ];

    public function __construct(
        private readonly PDO $db,
        private readonly EvaluationS3Service $evaluationService,
        private readonly UeReferentielService $ueService
    ) {
    }

    /** @return array<string, mixed> */
    public function previewUploadedFile(array $file, int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le téléversement du fichier a échoué.');
        }
        $path = (string) ($file['tmp_name'] ?? '');
        $name = trim((string) ($file['name'] ?? 'evaluations'));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!is_file($path) || !is_readable($path) || !in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
            throw new RuntimeException('Formats acceptés : CSV, XLS et XLSX.');
        }
        if (filesize($path) > 10 * 1024 * 1024) {
            throw new RuntimeException('Le fichier dépasse la taille maximale de 10 Mo.');
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray('', true, true, false);
        if (count($rows) < 2) {
            throw new RuntimeException('Le fichier ne contient aucune ligne d’évaluation.');
        }

        $headerMap = $this->resolveHeaders((array) $rows[0]);
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headerMap)));
        if ($missing !== []) {
            throw new RuntimeException('Colonnes manquantes : ' . implode(', ', $missing) . '.');
        }

        $validRows = [];
        $rowResults = [];
        $seen = [];
        for ($index = 1; $index < count($rows); $index++) {
            $rawRow = (array) $rows[$index];
            if (!$this->hasValues($rawRow)) {
                continue;
            }
            $lineNumber = $index + 1;
            $normalized = $this->normalizeRow($rawRow, $headerMap);
            $validation = $this->validateRow($normalized, $seen);
            $status = $validation['errors'] === [] ? 'valide' : 'erreur';
            $rowResults[] = [
                'line' => $lineNumber,
                'status' => $status,
                'data' => $normalized,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
            ];
            if ($status === 'valide') {
                $validRows[] = $normalized;
            }
        }

        if ($rowResults === []) {
            throw new RuntimeException('Aucune ligne exploitable n’a été trouvée.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO evaluation_s3_import_batch
                    (nom_fichier, empreinte_fichier, statut_batch, total_lignes, lignes_valides, lignes_erreur, id_utilisateur)
                 VALUES (:name, :hash, "previsualisation", :total, :valides, :erreurs, :user)'
            );
            $stmt->execute([
                ':name' => $name,
                ':hash' => hash_file('sha256', $path) ?: null,
                ':total' => count($rowResults),
                ':valides' => count($validRows),
                ':erreurs' => count($rowResults) - count($validRows),
                ':user' => $userId > 0 ? $userId : null,
            ]);
            $batchId = (int) $this->db->lastInsertId();

            $rowStmt = $this->db->prepare(
                'INSERT INTO evaluation_s3_import_row
                    (id_batch, numero_ligne, payload_brut, num_etu, id_ue, id_annee_acad, note, date_note, session_normale,
                     statut_ligne, message_erreur, message_avertissement)
                 VALUES (:batch, :line, :payload, :student, :ue, :year, :note, :date_note, :session, :status, :errors, :warnings)'
            );
            foreach ($rowResults as $result) {
                $data = $result['data'];
                $rowStmt->execute([
                    ':batch' => $batchId,
                    ':line' => $result['line'],
                    ':payload' => json_encode($data['raw'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ':student' => $data['num_etu'] ?? null,
                    ':ue' => $data['id_ue'] ?? null,
                    ':year' => $data['id_annee_acad'] ?? null,
                    ':note' => $data['note'] ?? null,
                    ':date_note' => $data['date_note'] ?? null,
                    ':session' => $data['session_normale'] ?? null,
                    ':status' => $result['status'],
                    ':errors' => $result['errors'] !== [] ? implode(' ', $result['errors']) : null,
                    ':warnings' => $result['warnings'] !== [] ? implode(' ', $result['warnings']) : null,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $this->getBatch($batchId);
    }

    /** @return array<string, mixed> */
    public function getBatch(int $batchId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM evaluation_s3_import_batch WHERE id_batch = :id LIMIT 1');
        $stmt->execute([':id' => $batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($batch)) {
            throw new RuntimeException('Lot d’import introuvable.');
        }
        $rows = $this->db->prepare(
            'SELECT * FROM evaluation_s3_import_row WHERE id_batch = :id ORDER BY numero_ligne'
        );
        $rows->execute([':id' => $batchId]);
        $batch['rows'] = $rows->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $batch;
    }

    /** @return array<string, mixed> */
    public function confirm(int $batchId, int $userId): array
    {
        $batch = $this->getBatch($batchId);
        if ((string) ($batch['statut_batch'] ?? '') !== 'previsualisation') {
            throw new RuntimeException('Ce lot a déjà été traité.');
        }
        $rows = $batch['rows'] ?? [];
        $validRows = [];
        foreach ($rows as $row) {
            if ((string) ($row['statut_ligne'] ?? '') !== 'valide') {
                throw new RuntimeException('Le lot contient des erreurs. Corrigez le fichier puis relancez la prévisualisation.');
            }
            $validRows[] = [
                'num_etu' => $row['num_etu'],
                'id_ue' => (int) $row['id_ue'],
                'id_annee_acad' => (int) $row['id_annee_acad'],
                'note' => (float) $row['note'],
                'date_note' => $row['date_note'],
                'session_normale' => (int) $row['session_normale'],
            ];
        }

        $this->db->beginTransaction();
        try {
            $imported = $this->evaluationService->persistImportedRows($validRows, $batchId, $userId);
            $updateRows = $this->db->prepare(
                'UPDATE evaluation_s3_import_row r
                 SET r.statut_ligne = "importe",
                     r.id_evaluation = (
                         SELECT e.id_evaluation FROM evaluation_s3 e
                         WHERE e.num_etu = r.num_etu AND e.id_ue = r.id_ue
                           AND e.id_annee_acad = r.id_annee_acad AND e.session_normale = r.session_normale
                         LIMIT 1
                     )
                 WHERE r.id_batch = :batch'
            );
            $updateRows->execute([':batch' => $batchId]);
            $updateBatch = $this->db->prepare(
                'UPDATE evaluation_s3_import_batch
                 SET statut_batch = "confirme", date_confirmation = NOW()
                 WHERE id_batch = :batch'
            );
            $updateBatch->execute([':batch' => $batchId]);
            $this->db->commit();
            return ['success' => true, 'imported' => $imported, 'message' => $imported . ' évaluation(s) importée(s).'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string, int|string|null> */
    private function normalizeRow(array $rawRow, array $headerMap): array
    {
        $value = function (string $key) use ($rawRow, $headerMap): string {
            $index = $headerMap[$key] ?? null;
            return $index === null ? '' : trim((string) ($rawRow[$index] ?? ''));
        };

        $yearRaw = $value('annee_academique');
        $yearId = is_numeric($yearRaw) ? (int) $yearRaw : $this->resolveYearLabel($yearRaw);
        $semesterRaw = $value('semestre');
        $semester = $this->ueService->normalizeSemester($semesterRaw);
        $studentRaw = $value('identifiant_etudiant');
        $student = $this->evaluationService->findStudent($studentRaw);
        $date = $this->parseDate($value('date_evaluation'));
        $session = $this->parseSession($value('session_normale'));
        $noteRaw = str_replace(',', '.', $value('note'));
        $ue = null;
        if ($semester === UeReferentielService::SEMESTER_CODE && $date !== null) {
            $ue = $this->ueService->findByCode($value('code_ue'), $date, $semester);
        }

        return [
            'raw' => $rawRow,
            'annee_raw' => $yearRaw,
            'semestre_raw' => $semesterRaw,
            'semestre_code' => $semester,
            'identifiant_raw' => $studentRaw,
            'num_etu' => is_array($student) ? (string) $student['num_carte_etud'] : null,
            'code_ue' => strtoupper($value('code_ue')),
            'id_ue' => is_array($ue) ? (int) $ue['id_ue'] : null,
            'id_annee_acad' => $yearId,
            'note' => is_numeric($noteRaw) ? round((float) $noteRaw, 2) : null,
            'date_note' => $date,
            'session_normale' => $session,
        ];
    }

    /** @return array{errors: array<int, string>, warnings: array<int, string>} */
    private function validateRow(array $row, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        if (($row['id_annee_acad'] ?? null) === null || (int) $row['id_annee_acad'] <= 0 || !$this->academicYearExists((int) $row['id_annee_acad'])) {
            $errors[] = 'Année académique inconnue.';
        }
        if (($row['semestre_code'] ?? null) !== UeReferentielService::SEMESTER_CODE) {
            $errors[] = 'Semestre inconnu : utilisez S3, S9 ou M2 S1.';
        }
        if (($row['num_etu'] ?? null) === null) {
            $errors[] = 'Étudiant introuvable.';
        }
        if (($row['id_ue'] ?? null) === null) {
            $errors[] = 'Code UE absent du référentiel ou version applicable introuvable.';
        }
        if (($row['note'] ?? null) === null || (float) $row['note'] < 0 || (float) $row['note'] > 20) {
            $errors[] = 'Note invalide : elle doit être comprise entre 0 et 20.';
        }
        if (($row['date_note'] ?? null) === null) {
            $errors[] = 'Date d’évaluation invalide.';
        }
        if (!in_array($row['session_normale'] ?? null, [0, 1], true)) {
            $errors[] = 'Session invalide : OUI pour normale, NON pour rattrapage.';
        }

        $key = implode('|', [
            (string) ($row['num_etu'] ?? ''),
            (string) ($row['id_ue'] ?? ''),
            (string) ($row['id_annee_acad'] ?? ''),
            (string) ($row['session_normale'] ?? ''),
        ]);
        if ($key !== '|||') {
            if (isset($seen[$key])) {
                $errors[] = 'Doublon dans le fichier.';
            }
            $seen[$key] = true;
        }

        if ($errors === [] && $this->evaluationExists($row)) {
            $warnings[] = 'Une évaluation existe déjà : la confirmation la mettra à jour de façon idempotente.';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function evaluationExists(array $row): bool
    {
        if (empty($row['num_etu']) || empty($row['id_ue']) || empty($row['id_annee_acad']) || !in_array($row['session_normale'] ?? null, [0, 1], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            'SELECT 1 FROM evaluation_s3
             WHERE num_etu = :student AND id_ue = :ue AND id_annee_acad = :year AND session_normale = :session
             LIMIT 1'
        );
        $stmt->execute([
            ':student' => $row['num_etu'],
            ':ue' => (int) $row['id_ue'],
            ':year' => (int) $row['id_annee_acad'],
            ':session' => (int) $row['session_normale'],
        ]);
        return $stmt->fetchColumn() !== false;
    }

    /** @return array<string, int> */
    private function resolveHeaders(array $headers): array
    {
        $aliases = [
            'annee_academique' => ['annee_academique', 'annee academique', 'année académique', 'id_annee_acad'],
            'semestre' => ['semestre', 'semestre_code', 'session_etude'],
            'identifiant_etudiant' => ['identifiant_etudiant', 'identifiant étudiant', 'id_etudiant', 'matricule', 'num_etu', 'num_carte_etud'],
            'code_ue' => ['code_ue', 'code ue', 'ue'],
            'note' => ['note', 'note_obtenue_ue', 'note /20', 'note sur 20'],
            'date_evaluation' => ['date_evaluation', 'date évaluation', 'date_note', 'date'],
            'session_normale' => ['session_normale', 'session normale', 'session', 'normale'],
        ];
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$this->normalizeHeader((string) $header)] = (int) $index;
        }
        $result = [];
        foreach ($aliases as $canonical => $accepted) {
            foreach ($accepted as $alias) {
                $key = $this->normalizeHeader($alias);
                if (isset($normalizedHeaders[$key])) {
                    $result[$canonical] = $normalizedHeaders[$key];
                    break;
                }
            }
        }
        return $result;
    }

    private function normalizeHeader(string $header): string
    {
        $header = function_exists('iconv') ? (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) : $header;
        $header = strtolower(trim($header));
        return (string) preg_replace('/[^a-z0-9]+/', '_', $header);
    }

    private function parseSession(string $value): ?int
    {
        $value = strtoupper(trim($value));
        if (in_array($value, ['OUI', 'YES', '1', 'NORMALE', 'NORMAL'], true)) {
            return 1;
        }
        if (in_array($value, ['NON', 'NO', '0', 'RATTRAPAGE', 'RATTRAPAGE'], true)) {
            return 0;
        }
        return null;
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && (!is_array($errors) || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0))) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    private function resolveYearLabel(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT id_annee_acad FROM annee_academique
             WHERE CONCAT(YEAR(date_deb), "-", YEAR(date_fin)) = :label LIMIT 1'
        );
        $stmt->execute([':label' => $value]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    private function academicYearExists(int $yearId): bool
    {
        if ($yearId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT 1 FROM annee_academique WHERE id_annee_acad = :year LIMIT 1');
        $stmt->execute([':year' => $yearId]);
        return $stmt->fetchColumn() !== false;
    }

    private function hasValues(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }
}
