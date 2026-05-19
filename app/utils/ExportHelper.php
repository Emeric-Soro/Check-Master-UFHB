<?php
/**
 * ExportHelper — utilitaire d'export CSV et Excel pour CheckMaster
 *
 * Fournit des méthodes statiques pour générer des fichiers CSV/Excel
 * avec en-têtes HTTP appropriés pour le téléchargement.
 */

class ExportHelper
{
    /**
     * Exporte un tableau de données en CSV avec téléchargement HTTP.
     *
     * @param array  $data     Tableau de lignes (tableaux associatifs)
     * @param array  $headers  En-têtes de colonnes (clé => libellé)
     * @param string $filename Nom du fichier téléchargé
     */
    public static function exportToCSV(array $data, array $headers, string $filename): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        // Ligne d'en-tête
        fputcsv($output, array_values($headers), ',', '"', '\\');

        // Lignes de données
        foreach ($data as $row) {
            $line = [];
            foreach (array_keys($headers) as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($output, $line, ',', '"', '\\');
        }

        fclose($output);
        exit;
    }

    /**
     * Exporte un tableau de données au format TSV (ouvrable dans Excel).
     *
     * @param array  $data     Tableau de lignes (tableaux associatifs)
     * @param array  $headers  En-têtes de colonnes (clé => libellé)
     * @param string $filename Nom du fichier téléchargé
     */
    public static function exportToExcel(array $data, array $headers, string $filename): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        // En-têtes
        fputcsv($output, array_values($headers), "\t", '"', '\\');

        // Données
        foreach ($data as $row) {
            $line = [];
            foreach (array_keys($headers) as $key) {
                $val = $row[$key] ?? '';
                // Échapper les guillemets et les sauts de ligne
                $val = str_replace(['"', "\r\n", "\r", "\n"], ['""', ' ', ' ', ' '], (string) $val);
                $line[] = $val;
            }
            fputcsv($output, $line, "\t", '"', '\\');
        }

        fclose($output);
        exit;
    }

    /**
     * Prépare les données pour l'export à partir d'un jeu de résultats.
     *
     * @param array $rows   Tableau de lignes (objets ou tableaux associatifs)
     * @param array $fieldMap  Mapping champ_source => libellé_export
     * @return array [data, headers]
     */
    public static function prepareFromRows(array $rows, array $fieldMap): array
    {
        $headers = $fieldMap;
        $data = [];

        foreach ($rows as $row) {
            $rowArr = is_array($row) ? $row : (array) $row;
            $line = [];
            foreach (array_keys($fieldMap) as $field) {
                $line[$field] = $rowArr[$field] ?? '';
            }
            $data[] = $line;
        }

        return [$data, $headers];
    }
}
