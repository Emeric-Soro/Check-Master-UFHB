<?php

declare(strict_types=1);

namespace CheckMaster\Services;

use PDO;
use CheckMaster\Core\AppConfig;
use CheckMaster\Core\Messages;
use CheckMaster\Models\BaseModel;

/**
 * Service d'export de qualité.
 * Export CSV/Excel/JSON avec formatage, filtres, pagination.
 * Utilise AppConfig pour les chemins, Messages pour les textes.
 */
class ExportService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── Export CSV ───────────────────────────────────────

    /**
     * Exporter des données en CSV.
     *
     * @param array $data Tableau de tableaux associatifs
     * @param array $columns Colonnes à exporter [clé_bdd => label_affiché]
     * @param string $filename Nom du fichier (sans extension)
     * @param string $delimiter Séparateur CSV
     */
    public function exportCsv(
        array $data,
        array $columns,
        string $filename = 'export',
        string $delimiter = ';'
    ): void {
        if (empty($data)) {
            $this->sendError(Messages::get('export.no_data'));
            return;
        }

        $filename = $this->sanitizeFilename($filename) . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // BOM UTF-8 pour Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // En-tête
        fputcsv($output, array_values($columns), $delimiter);

        // Données
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($columns as $key => $label) {
                $csvRow[] = $this->formatCellValue($row[$key] ?? '');
            }
            fputcsv($output, $csvRow, $delimiter);
        }

        fclose($output);
        exit;
    }

    // ─── Export JSON ──────────────────────────────────────

    /**
     * Exporter des données en JSON.
     *
     * @param array $data Données
     * @param string $filename Nom du fichier
     * @param bool $pretty Formater le JSON
     */
    public function exportJson(
        array $data,
        string $filename = 'export',
        bool $pretty = false
    ): void {
        $filename = $this->sanitizeFilename($filename) . '.json';

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        echo json_encode($data, $flags);
        exit;
    }

    // ─── Export Excel (PhpSpreadsheet) ────────────────────

    /**
     * Exporter des données en Excel (.xlsx).
     * Nécessite phpoffice/phpspreadsheet.
     *
     * @param array $data Données
     * @param array $columns Colonnes [clé_bdd => label_affiché]
     * @param string $filename Nom du fichier
     * @param string $sheetName Nom de la feuille
     */
    public function exportExcel(
        array $data,
        array $columns,
        string $filename = 'export',
        string $sheetName = 'Données'
    ): void {
        if (empty($data)) {
            $this->sendError(Messages::get('export.no_data'));
            return;
        }

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            // Fallback CSV si PhpSpreadsheet n'est pas installé
            $this->exportCsv($data, $columns, $filename);
            return;
        }

        $filename = $this->sanitizeFilename($filename) . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        // En-tête
        $col = 1;
        foreach ($columns as $key => $label) {
            $sheet->setCellValueByColumnAndRow($col, 1, $label);
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $col++;
        }

        // Style en-tête
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '3B82F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('1:' . count($columns))->applyFromArray($headerStyle);

        // Données
        $rowNum = 2;
        foreach ($data as $row) {
            $col = 1;
            foreach ($columns as $key => $label) {
                $value = $row[$key] ?? '';
                $sheet->setCellValueByColumnAndRow($col, $rowNum, $this->formatCellValue($value));
                $col++;
            }
            $rowNum++;
        }

        // Auto-filter
        $sheet->setAutoFilter('A1:' . $this->columnLetter(count($columns)) . ($rowNum - 1));

        // Téléchargement
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ─── Export depuis une requête SQL ─────────────────────

    /**
     * Exporter le résultat d'une requête SQL.
     *
     * @param string $sql Requête SELECT
     * @param array $params Paramètres liés
     * @param array $columns Colonnes à exporter
     * @param string $format Format (csv, json, excel)
     * @param string $filename Nom du fichier
     */
    public function exportFromQuery(
        string $sql,
        array $params,
        array $columns,
        string $format = 'csv',
        string $filename = 'export'
    ): void {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        match ($format) {
            'json'  => $this->exportJson($data, $filename),
            'excel' => $this->exportExcel($data, $columns, $filename),
            default => $this->exportCsv($data, $columns, $filename),
        };
    }

    // ─── Export depuis un Model ────────────────────────────

    /**
     * Exporter les données d'un model.
     *
     * @param BaseModel $model Le model source
     * @param array $columns Colonnes à exporter
     * @param array $criteria Critères de filtrage
     * @param string $format Format d'export
     * @param string $filename Nom du fichier
     */
    public function exportFromModel(
        BaseModel $model,
        array $columns,
        array $criteria = [],
        string $format = 'csv',
        string $filename = 'export'
    ): void {
        $data = empty($criteria) ? $model->findAll() : $model->findBy($criteria);

        match ($format) {
            'json'  => $this->exportJson($data, $filename),
            'excel' => $this->exportExcel($data, $columns, $filename),
            default => $this->exportCsv($data, $columns, $filename),
        };
    }

    // ─── Helpers ──────────────────────────────────────────

    /**
     * Nettoyer le nom de fichier.
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_') . '_' . date('Y-m-d');
    }

    /**
     * Formater une valeur de cellule pour l'export.
     */
    private function formatCellValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return (string) $value;
    }

    /**
     * Convertir un numéro de colonne en lettre (1=A, 2=B, ..., 27=AA).
     */
    private function columnLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $column--;
            $letter = chr(65 + ($column % 26)) . $letter;
            $column = intdiv($column, 26);
        }
        return $letter;
    }

    /**
     * Envoyer une réponse d'erreur.
     */
    private function sendError(string $message): void
    {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Colonnes prédéfinies ─────────────────────────────

    /**
     * Colonnes d'export pour les étudiants.
     */
    public static function etudiantColumns(): array
    {
        return [
            'num_etu'          => 'Numéro',
            'nom_etu'          => 'Nom',
            'prenom_etu'       => 'Prénom',
            'email_etu'        => 'Email',
            'tel_etu'          => 'Téléphone',
            'date_naiss_etu'   => 'Date de naissance',
            'lieu_naiss_etu'   => 'Lieu de naissance',
        ];
    }

    /**
     * Colonnes d'export pour les enseignants.
     */
    public static function enseignantColumns(): array
    {
        return [
            'id_enseignant'    => 'ID',
            'nom_enseignant'   => 'Nom',
            'prenom_enseignant' => 'Prénom',
            'mail_enseignant'  => 'Email',
            'tel_enseignant'   => 'Téléphone',
        ];
    }

    /**
     * Colonnes d'export pour les notes.
     */
    public static function noteColumns(): array
    {
        return [
            'num_etu'          => 'Numéro étudiant',
            'nom_etu'          => 'Nom',
            'prenom_etu'       => 'Prénom',
            'valeur_note'      => 'Note',
            'lib_cours'        => 'Cours',
            'lib_semestre'     => 'Semestre',
        ];
    }

    /**
     * Colonnes d'export pour les inscriptions.
     */
    public static function inscriptionColumns(): array
    {
        return [
            'num_etu'          => 'Numéro étudiant',
            'nom_etu'          => 'Nom',
            'prenom_etu'       => 'Prénom',
            'lib_niveau_etude' => 'Niveau',
            'lib_specialite'   => 'Spécialité',
            'annee_academique' => 'Année académique',
            'date_inscription' => 'Date d\'inscription',
        ];
    }

    /**
     * Colonnes d'export pour les soutenances.
     */
    public static function soutenanceColumns(): array
    {
        return [
            'id_soutenance'    => 'ID',
            'num_etu'          => 'Numéro étudiant',
            'nom_etu'          => 'Nom',
            'prenom_etu'       => 'Prénom',
            'date_soutenance'  => 'Date',
            'heure_soutenance' => 'Heure',
            'lib_salle'        => 'Salle',
            'note_soutenance'  => 'Note',
        ];
    }

    /**
     * Colonnes d'export pour les rapports.
     */
    public static function rapportColumns(): array
    {
        return [
            'id_rapport'       => 'ID',
            'num_etu'          => 'Numéro étudiant',
            'nom_etu'          => 'Nom',
            'prenom_etu'       => 'Prénom',
            'titre_rapport'    => 'Titre',
            'statut_rapport'   => 'Statut',
            'date_depot'       => 'Date de dépôt',
        ];
    }
}
