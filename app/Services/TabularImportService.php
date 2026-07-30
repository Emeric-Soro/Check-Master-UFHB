<?php

declare(strict_types=1);

namespace CheckMaster\Services;

require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use DateTimeImmutable;
use CheckMaster\Core\Messages;
use Enseignant;
use PersAdmin;
use InvalidArgumentException;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class TabularImportService
{
    private PDO $db;
    private GestionEtudiantService $gestionEtudiantService;
    private Enseignant $enseignantModel;
    private PersAdmin $persAdminModel;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->gestionEtudiantService = new GestionEtudiantService($db);
        $this->enseignantModel = new Enseignant($db);
        $this->persAdminModel = new PersAdmin($db);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFieldDefinitions(string $entity): array
    {
        $definitions = $this->getEntityDefinitions();
        if (!isset($definitions[$entity])) {
            throw new InvalidArgumentException('Entité d\'import inconnue.');
        }

        return $definitions[$entity]['fields'];
    }

    /**
     * @return array<string, mixed>
     */
    public function parseUploadedFile(array $file, string $entity): array
    {
        $definitions = $this->getEntityDefinitions();
        if (!isset($definitions[$entity])) {
            return ['success' => false, 'message' => 'Type d\'import non pris en charge.'];
        }

        if (empty($file) || !isset($file['tmp_name'], $file['error'])) {
            return ['success' => false, 'message' => 'Aucun fichier reçu.'];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Le téléversement du fichier a échoué.'];
        }

        $originalName = (string) ($file['name'] ?? 'import');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
            return ['success' => false, 'message' => 'Formats acceptés: CSV, XLS, XLSX.'];
        }

        $rawRows = $extension === 'csv'
            ? $this->parseCsvFile((string) $file['tmp_name'])
            : $this->parseSpreadsheetFile((string) $file['tmp_name']);

        if ($rawRows['success'] !== true) {
            return $rawRows;
        }

        $rows = $rawRows['rows'] ?? [];
        if (!is_array($rows) || count($rows) < 2) {
            return ['success' => false, 'message' => 'Le fichier ne contient aucune donnée exploitable.'];
        }

        $fields = $definitions[$entity]['fields'];
        $headerRow = array_map([$this, 'stringifyCell'], (array) $rows[0]);
        $mappedColumns = $this->resolveMappedColumns($headerRow, $fields);

        if (count($mappedColumns) === 0) {
            return [
                'success' => false,
                'message' => 'Les colonnes attendues n\'ont pas été reconnues. Vérifiez l\'en-tête du fichier.',
            ];
        }

        $parsedRows = [];
        $skippedExistingCount = 0;
        $seenIdentifiers = [];
        for ($index = 1, $rowCount = count($rows); $index < $rowCount; $index++) {
            $row = (array) $rows[$index];
            if (!$this->rowHasValues($row)) {
                continue;
            }

            $normalized = [];
            foreach ($fields as $field) {
                $fieldName = (string) ($field['name'] ?? '');
                if ($fieldName === '') {
                    continue;
                }

                $columnIndex = $mappedColumns[$fieldName] ?? null;
                $rawValue = $columnIndex !== null ? ($row[$columnIndex] ?? '') : '';
                $normalized[$fieldName] = $this->normalizeImportedValue($rawValue, (string) ($field['type'] ?? 'text'));
            }

            if (!$this->rowHasValues($normalized)) {
                continue;
            }

            $identifier = $this->buildEntityIdentifier($entity, $normalized);
            if ($identifier !== null) {
                if (isset($seenIdentifiers[$identifier])) {
                    continue;
                }

                if ($this->entityAlreadyExists($entity, $normalized)) {
                    $skippedExistingCount++;
                    continue;
                }

                $seenIdentifiers[$identifier] = true;
            }

            $parsedRows[] = $normalized;
        }

        if (count($parsedRows) === 0) {
            $message = $skippedExistingCount > 0
                ? 'Toutes les lignes du fichier existent déjà en base.'
                : 'Aucune ligne de données valide n\'a été détectée après lecture du fichier.';
            return ['success' => false, 'message' => $message];
        }

        $message = count($parsedRows) . ' ligne(s) chargée(s) depuis le fichier.';
        if ($skippedExistingCount > 0) {
            $message .= ' ' . $skippedExistingCount . ' ligne(s) déjà présentes en base ont été ignorées.';
        }

        return [
            'success' => true,
            'message' => $message,
            'rows' => $parsedRows,
            'filename' => $originalName,
            'matched_headers' => $mappedColumns,
            'skipped_existing_count' => $skippedExistingCount,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    public function importRows(string $entity, array $rows, int $userId): array
    {
        $definitions = $this->getEntityDefinitions();
        if (!isset($definitions[$entity])) {
            return ['success' => false, 'message' => 'Type d\'import non pris en charge.'];
        }

        $successCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row) || !$this->rowHasValues($row)) {
                continue;
            }

            try {
                $result = match ($entity) {
                    'etudiants' => $this->persistStudentRow($row, $userId),
                    'enseignants' => $this->persistTeacherRow($row),
                    'personnel_admin' => $this->persistAdminRow($row),
                    default => ['success' => false, 'message' => 'Entité d\'import non gérée.'],
                };

                if (!($result['success'] ?? false)) {
                    $errors[] = [
                        'line' => $index + 1,
                        'message' => (string) ($result['message'] ?? 'Erreur d\'import inconnue.'),
                    ];
                    continue;
                }

                $successCount++;
            } catch (\Throwable $exception) {
                $errors[] = [
                    'line' => $index + 1,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $errorCount = count($errors);
        $message = $successCount . ' ligne(s) importée(s)';
        if ($errorCount > 0) {
            $message .= ', ' . $errorCount . ' erreur(s).';
        } else {
            $message .= ' sans erreur.';
        }

        return [
            'success' => $successCount > 0 && $errorCount === 0,
            'message' => $message,
            'summary' => [
                'total' => count($rows),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getEntityDefinitions(): array
    {
        return [
            'etudiants' => [
                'fields' => [
                    ['name' => 'num_ident_etud', 'label' => 'Identifiant MESRS', 'type' => 'text', 'required' => false, 'aliases' => ['num_ident_etud', 'num_ident_etu', 'identifiant_mesrs', 'id_mesrs', 'num_identifiant']],
                    ['name' => 'num_carte_etud', 'label' => 'N° Carte Etudiant', 'type' => 'text', 'required' => true, 'aliases' => ['num_carte_etud', 'num_carte_etu', 'num_etu', 'matricule']],
                    ['name' => 'nom_etu', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'aliases' => ['nom_etu', 'nom']],
                    ['name' => 'prenom_etu', 'label' => 'Prénom', 'type' => 'text', 'required' => true, 'aliases' => ['prenom_etu', 'prenoms_etu', 'prenom']],
                    ['name' => 'date_naiss_etu', 'label' => 'Date de naissance', 'type' => 'date', 'required' => true, 'aliases' => ['date_naiss_etu', 'date_naissance', 'date_naiss']],
                    ['name' => 'id_genre', 'label' => 'Genre', 'type' => 'genre', 'required' => true, 'aliases' => ['id_genre', 'genre', 'genre_etu']],
                    ['name' => 'email_etu', 'label' => 'E-mail', 'type' => 'email', 'required' => true, 'aliases' => ['email_etu', 'email', 'mail_etu']],
                    ['name' => 'promotion_etu', 'label' => 'Promotion', 'type' => 'text', 'required' => true, 'aliases' => ['promotion_etu', 'promotion', 'id_annee_acad']],
                ],
            ],
            'enseignants' => [
                'fields' => [
                    ['name' => 'id_enseignant', 'label' => 'N° Matricule', 'type' => 'text', 'required' => true, 'aliases' => ['id_enseignant', 'matricule', 'matricule_enseignant']],
                    ['name' => 'nom_enseignant', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'aliases' => ['nom_enseignant', 'nom']],
                    ['name' => 'prenom_enseignant', 'label' => 'Prénom', 'type' => 'text', 'required' => true, 'aliases' => ['prenom_enseignant', 'prenom']],
                    ['name' => 'tel_enseignant', 'label' => 'Téléphone', 'type' => 'text', 'required' => false, 'aliases' => ['tel_enseignant', 'telephone', 'telephone_enseignant']],
                    ['name' => 'mail_enseignant', 'label' => 'E-mail', 'type' => 'email', 'required' => false, 'aliases' => ['mail_enseignant', 'email', 'email_enseignant']],
                    ['name' => 'id_specialite', 'label' => 'Spécialité', 'type' => 'text', 'required' => false, 'aliases' => ['id_specialite', 'specialite']],
                    ['name' => 'id_genre', 'label' => 'Genre', 'type' => 'genre', 'required' => false, 'aliases' => ['id_genre', 'genre']],
                    ['name' => 'type_enseignant', 'label' => 'Type enseignant', 'type' => 'text', 'required' => false, 'aliases' => ['type_enseignant', 'id_type_enseignant', 'type']],
                    ['name' => 'id_etablissement_origin', 'label' => 'Etablissement origine', 'type' => 'text', 'required' => false, 'aliases' => ['id_etablissement_origin', 'id_etablissement_origine', 'etablissement_origine']],
                ],
            ],
            'personnel_admin' => [
                'fields' => [
                    ['name' => 'id_pers_admin', 'label' => 'ID', 'type' => 'text', 'required' => false, 'aliases' => ['id_pers_admin', 'matricule', 'matricule_pers_admin']],
                    ['name' => 'nom_pers_admin', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'aliases' => ['nom_pers_admin', 'nom']],
                    ['name' => 'prenom_pers_admin', 'label' => 'Prénom', 'type' => 'text', 'required' => true, 'aliases' => ['prenom_pers_admin', 'prenom']],
                    ['name' => 'id_genre', 'label' => 'Genre', 'type' => 'genre', 'required' => false, 'aliases' => ['id_genre', 'genre']],
                    ['name' => 'email_pers_admin', 'label' => 'E-mail', 'type' => 'email', 'required' => true, 'aliases' => ['email_pers_admin', 'email']],
                    ['name' => 'tel_pers_admin', 'label' => 'Téléphone', 'type' => 'admin_phone', 'required' => true, 'aliases' => ['tel_pers_admin', 'telephone']],
                    ['name' => 'poste', 'label' => 'Poste', 'type' => 'text', 'required' => true, 'aliases' => ['poste', 'id_fonction', 'fonction']],
                    ['name' => 'date_embauche', 'label' => 'Date d\'embauche', 'type' => 'date', 'required' => true, 'aliases' => ['date_embauche', 'date_emb', 'embauche']],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCsvFile(string $path): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return ['success' => false, 'message' => Messages::get('error.file_read_csv')];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return ['success' => false, 'message' => 'Le fichier CSV est vide.'];
        }

        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]) ?? (string) $row[0];
            }
            $rows[] = $row;
        }

        fclose($handle);

        return ['success' => true, 'rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseSpreadsheetFile(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $rows = [];
            for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                $row = [];
                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $cell = $sheet->getCellByColumnAndRow($columnIndex, $rowIndex);
                    $row[] = $cell !== null ? $cell->getFormattedValue() : '';
                }
                $rows[] = $row;
            }

            return ['success' => true, 'rows' => $rows];
        } catch (\Throwable $exception) {
            return ['success' => false, 'message' => 'Lecture du fichier Excel impossible: ' . $exception->getMessage()];
        }
    }

    /**
     * @param array<int, string> $headerRow
     * @param array<int, array<string, mixed>> $fields
     * @return array<string, int>
     */
    private function resolveMappedColumns(array $headerRow, array $fields): array
    {
        $normalizedHeaders = [];
        foreach ($headerRow as $index => $header) {
            $normalizedHeaders[$index] = $this->normalizeHeader($header);
        }

        $mapped = [];
        foreach ($fields as $field) {
            $fieldName = (string) ($field['name'] ?? '');
            if ($fieldName === '') {
                continue;
            }

            $aliases = array_map([$this, 'normalizeHeader'], (array) ($field['aliases'] ?? [$fieldName]));
            foreach ($normalizedHeaders as $index => $header) {
                if ($header !== '' && in_array($header, $aliases, true)) {
                    $mapped[$fieldName] = $index;
                    break;
                }
            }
        }

        return $mapped;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
        return trim($value, '_');
    }

    /**
     * @param mixed $value
     */
    private function stringifyCell($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @param mixed $value
     */
    private function normalizeImportedValue($value, string $type): string
    {
        $value = $this->stringifyCell($value);
        if ($value === '') {
            return '';
        }

        return match ($type) {
            'date' => $this->normalizeDateValue($value),
            'genre' => $this->normalizeGenreValue($value),
            'admin_phone' => $this->normalizeAdminTelephone($value),
            default => $value,
        };
    }

    private function normalizeDateValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $value = str_replace(['.', '/'], '-', $value);
        $date = DateTimeImmutable::createFromFormat('d-m-Y', $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d', $value)
            ?: DateTimeImmutable::createFromFormat('d-m-y', $value);

        return $date ? $date->format('Y-m-d') : trim($value);
    }

    private function normalizeGenreValue(string $value): string
    {
        $normalized = strtolower(trim($value));
        return match ($normalized) {
            'm', 'masculin', 'masculine', 'male' => 'M',
            'f', 'feminin', 'feminin e', 'feminin', 'female', 'femelle' => 'F',
            'n', 'neutre' => 'N',
            default => strtoupper(substr($normalized, 0, 1)),
        };
    }

    /**
     * @param array<int|string, mixed> $row
     */
    private function rowHasValues(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function persistStudentRow(array $row, int $userId): array
    {
        $numCarte = trim((string) ($row['num_carte_etud'] ?? ''));
        $existing = $numCarte !== '' ? $this->gestionEtudiantService->getEtudiantById($numCarte) : null;
        $promotion = trim((string) ($row['promotion_etu'] ?? ''));
        if ($promotion === '') {
            $promotion = (string) (\AcademicYear::getSelectedIdFromSession() ?? '');
        }

        $payload = [
            'num_etu' => $numCarte !== '' ? $numCarte : (string) ($existing->num_carte_etud ?? ''),
            'nom_etu' => trim((string) ($row['nom_etu'] ?? ($existing->nom_etu ?? ''))),
            'prenom_etu' => trim((string) ($row['prenom_etu'] ?? ($existing->prenom_etu ?? ''))),
            'date_naiss_etu' => trim((string) ($row['date_naiss_etu'] ?? ($existing->date_naiss_etu ?? ''))),
            'id_genre' => trim((string) ($row['id_genre'] ?? ($existing->id_genre ?? $existing->genre_etu ?? ''))),
            'genre_etu' => trim((string) ($row['id_genre'] ?? ($existing->id_genre ?? $existing->genre_etu ?? ''))),
            'email_etu' => trim((string) ($row['email_etu'] ?? ($existing->email_etu ?? ''))),
            'promotion_etu' => $promotion !== '' ? $promotion : (string) ($existing->promotion_etu ?? ''),
            'id_annee_acad' => $promotion !== '' ? $promotion : (string) ($existing->promotion_etu ?? ''),
            'identifiant_mesrs' => trim((string) ($row['num_ident_etud'] ?? ($existing->identifiant_mesrs ?? ''))),
            'num_ident_etud' => trim((string) ($row['num_ident_etud'] ?? ($existing->identifiant_mesrs ?? ''))),
        ];

        if ($existing) {
            $payload['old_num_etu'] = (string) ($existing->num_carte_etud ?? $numCarte);
            return $this->gestionEtudiantService->modifierEtudiant($payload, $userId);
        }

        return $this->gestionEtudiantService->ajouterEtudiant($payload, $userId);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function persistTeacherRow(array $row): array
    {
        $id = trim((string) ($row['id_enseignant'] ?? ''));
        if ($id === '') {
            return ['success' => false, 'message' => 'Le matricule enseignant est obligatoire.'];
        }

        $existing = $this->enseignantModel->getEnseignantById($id);
        $nom = trim((string) ($row['nom_enseignant'] ?? ($existing->nom_enseignant ?? '')));
        $prenom = trim((string) ($row['prenom_enseignant'] ?? ($existing->prenom_enseignant ?? '')));
        if ($nom === '' || $prenom === '') {
            return ['success' => false, 'message' => 'Les colonnes nom_enseignant et prenom_enseignant sont obligatoires.'];
        }

        $email = trim((string) ($row['mail_enseignant'] ?? ($existing->mail_enseignant ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'message' => 'Adresse email enseignant invalide.'];
        }

        $telephone = trim((string) ($row['tel_enseignant'] ?? ($existing->tel_enseignant ?? '')));
        $idSpecialite = $this->resolveSpecialiteValue($row['id_specialite'] ?? ($existing->id_specialite ?? null));
        $genre = $this->resolveGenreNullable($row['id_genre'] ?? ($existing->id_genre ?? null));
        $typeEnseignant = $this->resolveTypeEnseignantValue($row['type_enseignant'] ?? ($existing->type_enseignant ?? null));
        $idEtablissement = $this->resolveIntegerOrNull($row['id_etablissement_origin'] ?? ($existing->id_etablissement_origin ?? null));

        if ($existing) {
            $sql = 'UPDATE enseignants
                    SET nom_enseignant = :nom,
                        prenom_enseignant = :prenom,
                        tel_enseignant = :telephone,
                        mail_enseignant = :email,
                        id_specialite = :id_specialite,
                        id_genre = :id_genre,
                        type_enseignant = :type_enseignant,
                        id_etablissement_origin = :id_etablissement_origin
                    WHERE id_enseignant = :id';
        } else {
            $sql = 'INSERT INTO enseignants (
                        id_enseignant, nom_enseignant, prenom_enseignant, tel_enseignant,
                        mail_enseignant, id_specialite, id_genre, type_enseignant, id_etablissement_origin
                    ) VALUES (
                        :id, :nom, :prenom, :telephone, :email, :id_specialite, :id_genre, :type_enseignant, :id_etablissement_origin
                    )';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':telephone', $telephone !== '' ? $telephone : null);
        $stmt->bindValue(':email', $email !== '' ? $email : null);
        $stmt->bindValue(':id_specialite', $idSpecialite, $idSpecialite === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_genre', $genre !== '' ? $genre : null);
        $stmt->bindValue(':type_enseignant', $typeEnseignant, $typeEnseignant === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_etablissement_origin', $idEtablissement, $idEtablissement === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function persistAdminRow(array $row): array
    {
        $rawId = trim((string) ($row['id_pers_admin'] ?? ''));
        $existing = null;
        if ($rawId !== '' && ctype_digit($rawId)) {
            $existing = $this->persAdminModel->getPersAdminById((int) $rawId);
        }

        $nom = trim((string) ($row['nom_pers_admin'] ?? ($existing->nom_pers_admin ?? '')));
        $prenom = trim((string) ($row['prenom_pers_admin'] ?? ($existing->prenom_pers_admin ?? '')));
        $email = trim((string) ($row['email_pers_admin'] ?? ($existing->email_pers_admin ?? '')));
        $telephone = $this->normalizeAdminTelephone((string) ($row['tel_pers_admin'] ?? ($existing->tel_pers_admin ?? '')));
        $poste = $this->resolveFonctionValue($row['poste'] ?? ($existing->poste ?? ''));
        $dateEmbauche = $this->normalizeDateValue(trim((string) ($row['date_embauche'] ?? ($existing->date_embauche ?? ''))));
        $genre = $this->resolveGenreNullable($row['id_genre'] ?? ($existing->id_genre ?? null));

        if ($nom === '' || $prenom === '' || $email === '' || $telephone === '' || $poste === '' || $dateEmbauche === '') {
            return ['success' => false, 'message' => 'Les colonnes nom, prénom, email, téléphone, poste et date_embauche sont obligatoires.'];
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'message' => 'Adresse email du personnel administratif invalide.'];
        }

        if ($existing) {
            $sql = 'UPDATE personnel_admin
                    SET nom_pers_admin = :nom,
                        prenom_pers_admin = :prenom,
                        id_genre = :id_genre,
                        email_pers_admin = :email,
                        tel_pers_admin = :telephone,
                        poste = :poste,
                        date_embauche = :date_embauche
                    WHERE id_pers_admin = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int) $rawId, PDO::PARAM_INT);
        } elseif ($rawId !== '' && ctype_digit($rawId)) {
            $sql = 'INSERT INTO personnel_admin (
                        id_pers_admin, nom_pers_admin, prenom_pers_admin, id_genre,
                        email_pers_admin, tel_pers_admin, poste, date_embauche
                    ) VALUES (
                        :id, :nom, :prenom, :id_genre, :email, :telephone, :poste, :date_embauche
                    )';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int) $rawId, PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO personnel_admin (
                        nom_pers_admin, prenom_pers_admin, id_genre,
                        email_pers_admin, tel_pers_admin, poste, date_embauche
                    ) VALUES (
                        :nom, :prenom, :id_genre, :email, :telephone, :poste, :date_embauche
                    )';
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':id_genre', $genre !== '' ? $genre : null);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':telephone', $telephone);
        $stmt->bindValue(':poste', $poste);
        $stmt->bindValue(':date_embauche', $dateEmbauche);
        $stmt->execute();

        return ['success' => true];
    }

    /**
     * @param mixed $value
     */
    private function resolveIntegerOrNull($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        $resolved = (int) $value;
        return $resolved > 0 ? $resolved : null;
    }

    /**
     * @param mixed $value
     */
    private function resolveGenreNullable($value): string
    {
        $value = trim((string) $value);
        return $value === '' ? '' : $this->normalizeGenreValue($value);
    }

    /**
     * @param mixed $value
     */
    private function resolveSpecialiteValue($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $stmt = $this->db->prepare('SELECT id_specialite FROM specialite WHERE LOWER(lib_specialite) = LOWER(?) LIMIT 1');
        $stmt->execute([$value]);
        $resolved = $stmt->fetchColumn();
        return $resolved !== false ? (int) $resolved : null;
    }

    /**
     * @param mixed $value
     */
    private function resolveTypeEnseignantValue($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $stmt = $this->db->prepare('SELECT id_type_enseignant FROM type_enseignant WHERE LOWER(libelle) = LOWER(?) LIMIT 1');
        $stmt->execute([$value]);
        $resolved = $stmt->fetchColumn();
        return $resolved !== false ? (int) $resolved : null;
    }

    /**
     * @param mixed $value
     */
    private function resolveFonctionValue($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 2) {
            return strtoupper($value);
        }

        $stmt = $this->db->prepare('SELECT id_fonction FROM fonction WHERE LOWER(lib_fonction) = LOWER(?) LIMIT 1');
        $stmt->execute([$value]);
        $resolved = $stmt->fetchColumn();
        return $resolved !== false ? strtoupper((string) $resolved) : $value;
    }

    private function normalizeAdminTelephone(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (ctype_digit($value) && strlen($value) < 10) {
            return str_pad($value, 10, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function entityAlreadyExists(string $entity, array $row): bool
    {
        return match ($entity) {
            'etudiants' => $this->studentExists($row),
            'enseignants' => $this->teacherExists($row),
            'personnel_admin' => $this->adminExists($row),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildEntityIdentifier(string $entity, array $row): ?string
    {
        return match ($entity) {
            'etudiants' => $this->normalizeIdentifierValue((string) ($row['num_carte_etud'] ?? ''))
                ?: $this->normalizeIdentifierValue((string) ($row['num_ident_etud'] ?? '')),
            'enseignants' => $this->normalizeIdentifierValue((string) ($row['id_enseignant'] ?? '')),
            'personnel_admin' => $this->normalizeIdentifierValue((string) ($row['id_pers_admin'] ?? ''))
                ?: $this->normalizeIdentifierValue((string) ($row['email_pers_admin'] ?? '')),
            default => null,
        };
    }

    private function normalizeIdentifierValue(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : mb_strtolower($value, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function studentExists(array $row): bool
    {
        $numCarte = trim((string) ($row['num_carte_etud'] ?? ''));
        $numIdent = trim((string) ($row['num_ident_etud'] ?? ''));
        if ($numCarte === '' && $numIdent === '') {
            return false;
        }

        $sql = 'SELECT 1 FROM etudiants WHERE num_carte_etud = :num_carte';
        $params = [':num_carte' => $numCarte];
        if ($numIdent !== '') {
            $sql .= ' OR num_ident_etud = :num_ident';
            $params[':num_ident'] = $numIdent;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function teacherExists(array $row): bool
    {
        $id = trim((string) ($row['id_enseignant'] ?? ''));
        if ($id === '') {
            return false;
        }

        return $this->enseignantModel->getEnseignantById($id) !== null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function adminExists(array $row): bool
    {
        $id = trim((string) ($row['id_pers_admin'] ?? ''));
        if ($id !== '' && ctype_digit($id) && $this->persAdminModel->getPersAdminById((int) $id)) {
            return true;
        }

        $email = trim((string) ($row['email_pers_admin'] ?? ''));
        if ($email === '') {
            return false;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM personnel_admin WHERE LOWER(email_pers_admin) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }
}
