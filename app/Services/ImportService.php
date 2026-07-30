<?php

declare(strict_types=1);

namespace CheckMaster\Services;

use PDO;
use CheckMaster\Core\AppConfig;
use CheckMaster\Core\Messages;
use CheckMaster\Models\BaseModel;

/**
 * Service d'import de qualité.
 * Import CSV/Excel avec validation, nettoyage, rapport d'erreurs.
 * Utilise AppConfig pour les chemins, Messages pour les textes.
 */
class ImportService
{
    private PDO $pdo;

    /** @var array<string> Erreurs accumulées pendant l'import */
    private array $errors = [];

    /** @var array<string> Avertissements non bloquants */
    private array $warnings = [];

    /** @var int Nombre de lignes importées avec succès */
    private int $importedCount = 0;

    /** @var int Nombre de lignes ignorées (doublons, erreurs) */
    private int $skippedCount = 0;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── Import CSV ───────────────────────────────────────

    /**
     * Importer un fichier CSV avec validation.
     *
     * @param string $filePath Chemin du fichier CSV
     * @param array $columnMapping Mapping colonne_csv => colonne_bdd
     * @param string $table Table cible
     * @param string $uniqueKey Colonne pour détecter les doublons (vide = pas de dédup)
     * @param callable|null $validator Fonction de validation par ligne (retourne true ou message d'erreur)
     * @return array{success: bool, imported: int, skipped: int, errors: array, warnings: array}
     */
    public function importCsv(
        string $filePath,
        array $columnMapping,
        string $table,
        string $uniqueKey = '',
        ?callable $validator = null
    ): array {
        $this->reset();

        if (!is_file($filePath)) {
            $this->errors[] = Messages::get('error.file_not_found');
            return $this->buildResult(false);
        }

        if (!is_readable($filePath)) {
            $this->errors[] = Messages::get('error.file_read');
            return $this->buildResult(false);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->errors[] = Messages::get('error.file_open');
            return $this->buildResult(false);
        }

        // Détecter le séparateur (point-virgule ou virgule)
        $firstLine = fgets($handle);
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        // Lire l'en-tête
        $headers = fgetcsv($handle, 0, $separator);
        if ($headers === false) {
            fclose($handle);
            $this->errors[] = Messages::get('error.invalid_input') . ' : en-tête manquant';
            return $this->buildResult(false);
        }

        // Valider que les colonnes mappées existent
        $headerIndex = array_flip(array_map('trim', $headers));
        foreach ($columnMapping as $csvCol => $dbCol) {
            if (!isset($headerIndex[$csvCol])) {
                $this->warnings[] = "Colonne '$csvCol' introuvable dans le fichier CSV";
            }
        }

        $lineNumber = 1;
        $this->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $separator)) !== false) {
                $lineNumber++;

                // Ignorer les lignes vides
                if (empty(array_filter($row))) {
                    continue;
                }

                // Construire les données de la ligne
                $data = [];
                foreach ($columnMapping as $csvCol => $dbCol) {
                    $idx = $headerIndex[$csvCol] ?? null;
                    if ($idx !== null && isset($row[$idx])) {
                        $data[$dbCol] = trim((string) $row[$idx]);
                    }
                }

                // Validation personnalisée
                if ($validator !== null) {
                    $validationResult = $validator($data, $lineNumber);
                    if ($validationResult !== true) {
                        $this->errors[] = "Ligne $lineNumber : $validationResult";
                        $this->skippedCount++;
                        continue;
                    }
                }

                // Déduplication
                if ($uniqueKey !== '' && isset($data[$uniqueKey])) {
                    if ($this->recordExists($table, $uniqueKey, $data[$uniqueKey])) {
                        $this->warnings[] = "Ligne $lineNumber : doublon ($uniqueKey = {$data[$uniqueKey]})";
                        $this->skippedCount++;
                        continue;
                    }
                }

                // Insertion
                try {
                    $this->insertRow($table, $data);
                    $this->importedCount++;
                } catch (\Throwable $e) {
                    $this->errors[] = "Ligne $lineNumber : " . $e->getMessage();
                    $this->skippedCount++;
                }
            }

            $this->commit();
            fclose($handle);

            return $this->buildResult(true);
        } catch (\Throwable $e) {
            $this->rollBack();
            fclose($handle);
            $this->errors[] = Messages::get('error.import_failed') . ' : ' . $e->getMessage();
            return $this->buildResult(false);
        }
    }

    // ─── Import depuis un tableau PHP ─────────────────────

    /**
     * Importer des données depuis un tableau PHP.
     *
     * @param array $rows Tableau associatif des lignes
     * @param string $table Table cible
     * @param string $uniqueKey Colonne pour dédup
     * @param callable|null $validator Validation par ligne
     * @return array{success: bool, imported: int, skipped: int, errors: array, warnings: array}
     */
    public function importArray(
        array $rows,
        string $table,
        string $uniqueKey = '',
        ?callable $validator = null
    ): array {
        $this->reset();

        if (empty($rows)) {
            $this->warnings[] = Messages::get('export.no_data');
            return $this->buildResult(true);
        }

        $this->beginTransaction();

        try {
            foreach ($rows as $index => $data) {
                $lineNumber = $index + 1;

                // Validation
                if ($validator !== null) {
                    $validationResult = $validator($data, $lineNumber);
                    if ($validationResult !== true) {
                        $this->errors[] = "Ligne $lineNumber : $validationResult";
                        $this->skippedCount++;
                        continue;
                    }
                }

                // Déduplication
                if ($uniqueKey !== '' && isset($data[$uniqueKey])) {
                    if ($this->recordExists($table, $uniqueKey, $data[$uniqueKey])) {
                        $this->warnings[] = "Ligne $lineNumber : doublon ignoré";
                        $this->skippedCount++;
                        continue;
                    }
                }

                try {
                    $this->insertRow($table, $data);
                    $this->importedCount++;
                } catch (\Throwable $e) {
                    $this->errors[] = "Ligne $lineNumber : " . $e->getMessage();
                    $this->skippedCount++;
                }
            }

            $this->commit();
            return $this->buildResult(true);
        } catch (\Throwable $e) {
            $this->rollBack();
            $this->errors[] = Messages::get('error.import_failed') . ' : ' . $e->getMessage();
            return $this->buildResult(false);
        }
    }

    // ─── Méthodes internes ────────────────────────────────

    private function recordExists(string $table, string $column, mixed $value): bool
    {
        $safeTable = $this->validateIdentifier($table);
        $safeColumn = $this->validateIdentifier($column);

        $stmt = $this->pdo->prepare("SELECT 1 FROM $safeTable WHERE $safeColumn = :val LIMIT 1");
        $stmt->execute([':val' => $value]);
        return $stmt->fetch() !== false;
    }

    private function insertRow(string $table, array $data): int
    {
        $safeTable = $this->validateIdentifier($table);
        $columns = array_keys($data);
        $safeColumns = array_map([$this, 'validateIdentifier'], $columns);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = "INSERT INTO $safeTable (" . implode(', ', $safeColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    private function validateIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException("Identifiant SQL invalide : $identifier");
        }
        return $identifier;
    }

    private function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    private function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    private function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function reset(): void
    {
        $this->errors = [];
        $this->warnings = [];
        $this->importedCount = 0;
        $this->skippedCount = 0;
    }

    private function buildResult(bool $success): array
    {
        return [
            'success'  => $success && empty($this->errors),
            'imported' => $this->importedCount,
            'skipped'  => $this->skippedCount,
            'errors'   => $this->errors,
            'warnings' => $this->warnings,
            'message'  => $this->buildMessage(),
        ];
    }

    private function buildMessage(): string
    {
        if (!empty($this->errors)) {
            return Messages::get('error.import_failed') . ' (' . $this->importedCount . ' importés, ' . $this->skippedCount . ' ignorés)';
        }
        if ($this->importedCount > 0) {
            return Messages::get('success.imported') . " ($this->importedCount lignes)";
        }
        return Messages::get('export.no_data');
    }

    // ─── Accesseurs ───────────────────────────────────────

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    // ─── Validateurs prédéfinis ───────────────────────────

    /**
     * Validateur pour les étudiants.
     */
    public static function etudiantValidator(): callable
    {
        return function (array $data, int $line): string|true {
            if (empty($data['nom_etu'] ?? '')) {
                return "Nom manquant";
            }
            if (empty($data['prenom_etu'] ?? '')) {
                return "Prénom manquant";
            }
            if (!empty($data['email_etu'] ?? '') && !filter_var($data['email_etu'], FILTER_VALIDATE_EMAIL)) {
                return "Email invalide : " . $data['email_etu'];
            }
            return true;
        };
    }

    /**
     * Validateur pour les enseignants.
     */
    public static function enseignantValidator(): callable
    {
        return function (array $data, int $line): string|true {
            if (empty($data['nom_enseignant'] ?? '')) {
                return "Nom manquant";
            }
            if (empty($data['prenom_enseignant'] ?? '')) {
                return "Prénom manquant";
            }
            if (!empty($data['mail_enseignant'] ?? '') && !filter_var($data['mail_enseignant'], FILTER_VALIDATE_EMAIL)) {
                return "Email invalide : " . $data['mail_enseignant'];
            }
            return true;
        };
    }

    /**
     * Validateur pour les notes.
     */
    public static function noteValidator(): callable
    {
        return function (array $data, int $line): string|true {
            if (empty($data['num_etu'] ?? '')) {
                return "Numéro étudiant manquant";
            }
            if (!isset($data['valeur_note']) || !is_numeric($data['valeur_note'])) {
                return "Note invalide";
            }
            $note = (float) $data['valeur_note'];
            if ($note < 0 || $note > 20) {
                return "Note hors limites (0-20) : $note";
            }
            return true;
        };
    }
}
