<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * DocumentGeneratorService
 *
 * Service for generating PDF documents from Word templates (.docx)
 * Uses PHPWord for template processing and a Gotenberg API for PDF conversion.
 */
class DocumentGeneratorService
{
    private string $templatesPath;
    private string $tempPath;
    private string $gotenbergUrl;

    /**
     * Constructor
     *
     * @param string|null $templatesPath Path to templates directory
     * @param string|null $tempPath Path to temporary files directory
     */
    public function __construct(?string $templatesPath = null, ?string $tempPath = null, ?string $gotenbergUrl = null)
    {
        $this->templatesPath = $templatesPath ?? __DIR__ . '/../../ressources/templates/';
        $this->tempPath = $tempPath ?? sys_get_temp_dir() . '/';
        $this->gotenbergUrl = $gotenbergUrl ?? 'http://gotenberg:3000/forms/libreoffice/convert';

        // Ensure temp directory exists and is writable
        if (!is_dir($this->tempPath) && !mkdir($this->tempPath, 0777, true)) {
            throw new Exception("Temporary directory could not be created: {$this->tempPath}");
        }
        if (!is_writable($this->tempPath)) {
            throw new Exception("Temporary directory is not writable: {$this->tempPath}");
        }
    }

    /**
     * Convert HTML content to PDF using Gotenberg's Chromium engine (Pipeline A)
     * This provides pixel-perfect rendering of HTML/CSS content
     *
     * @param string $htmlContent HTML content to convert
     * @param array $options Optional parameters (paperSize, landscape, margins)
     * @return string Path to the generated PDF file
     * @throws Exception If conversion fails
     */
    public function convertHtmlToPdf(string $htmlContent, array $options = []): string
    {
        // Default options
        $paperSize = $options['paperSize'] ?? 'A4';
        $landscape = $options['landscape'] ?? false;
        $marginTop = $options['marginTop'] ?? '1';
        $marginBottom = $options['marginBottom'] ?? '1';
        $marginLeft = $options['marginLeft'] ?? '1';
        $marginRight = $options['marginRight'] ?? '1';

        // Create a temporary HTML file
        $tempHtmlFile = $this->tempPath . uniqid('html_') . '.html';
        
        // Wrap content in a complete HTML document if not already wrapped
        if (stripos($htmlContent, '<!DOCTYPE') === false) {
            $htmlContent = "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Document</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; }
        @page { size: {$paperSize} " . ($landscape ? 'landscape' : 'portrait') . "; margin: {$marginTop}cm {$marginRight}cm {$marginBottom}cm {$marginLeft}cm; }
    </style>
</head>
<body>
{$htmlContent}
</body>
</html>";
        }
        
        if (file_put_contents($tempHtmlFile, $htmlContent) === false) {
            throw new Exception("Erreur lors de la création du fichier temporaire HTML.");
        }

        try {
            $pdfPath = $this->gotenbergConvertHtmlToPdf($tempHtmlFile, $options);
            
            if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
                throw new Exception("La conversion HTML->PDF a échoué ou le fichier est vide.");
            }

            return $pdfPath;
            
        } finally {
            // Clean up temporary HTML file
            if (file_exists($tempHtmlFile)) {
                unlink($tempHtmlFile);
            }
        }
    }

    /**
     * Convert HTML to PDF using Gotenberg's Chromium engine.
     */
    private function gotenbergConvertHtmlToPdf(string $htmlFilePath, array $options): string
    {
        $gotenbergHtmlUrl = 'http://gotenberg:3000/forms/chromium/convert/html';
        
        $curl = curl_init();
        if ($curl === false) {
            throw new Exception("Impossible d'initialiser cURL.");
        }
        
        $file = new CURLFile($htmlFilePath, 'text/html', basename($htmlFilePath));
        
        $postData = [
            'files' => $file,
            'paperWidth' => $options['paperWidth'] ?? ($options['paperSize'] === 'A4' ? '8.27' : '11'),
            'paperHeight' => $options['paperHeight'] ?? ($options['paperSize'] === 'A4' ? '11.7' : '8.5'),
            'marginTop' => $options['marginTop'] ?? '1',
            'marginBottom' => $options['marginBottom'] ?? '1',
            'marginLeft' => $options['marginLeft'] ?? '1',
            'marginRight' => $options['marginRight'] ?? '1',
            'landscape' => ($options['landscape'] ?? false) ? 'true' : 'false',
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $gotenbergHtmlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $httpCode !== 200) {
            throw new Exception("Erreur du service de conversion HTML->PDF (Gotenberg). Code: {$httpCode}");
        }

        $pdfPath = $this->tempPath . uniqid('pdf_') . '.pdf';
        if (file_put_contents($pdfPath, $response) === false) {
            throw new Exception("Erreur lors de l'enregistrement du fichier PDF.");
        }

        return $pdfPath;
    }

    /**
     * Generate a PDF document from a Word template
     *
     * @param string $templateName Name of the template file (without path)
     * @param array $data Associative array of data to merge with template
     * @return string Path to the generated PDF file
     * @throws Exception If template not found or conversion fails
     */
if (substr_compare($templateName, '.docx', -5) !== 0) {
            $templateName .= '.docx';
        }

        $templatePath = $this->templatesPath . $templateName;

        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: {$templateName}");
        }

        try {
            $templateProcessor = new TemplateProcessor($templatePath);

            $this->replacePlaceholders($templateProcessor, $data);
            $this->handleRepeatingBlocks($templateProcessor, $data);

            $tempDocxFile = $this->tempPath . uniqid('doc_') . '.docx';
            $templateProcessor->saveAs($tempDocxFile);

            $pdfFile = $this->convertToPdf($tempDocxFile);

            // Clean up the temporary .docx file
            if (file_exists($tempDocxFile)) {
                unlink($tempDocxFile);
            }

            return $pdfFile;
        } catch (Exception $e) {
            // Ensure temp file is cleaned up on error as well
            if (isset($tempDocxFile) && file_exists($tempDocxFile)) {
                unlink($tempDocxFile);
            }
            // Re-throw the exception to be handled by the caller
            throw $e;
        }
    }

    /**
     * Replace simple placeholders in the template
     *
     * @param TemplateProcessor $template
     * @param array $data
     */
    private function replacePlaceholders(TemplateProcessor $template, array $data): void
    {
        foreach ($data as $key => $value) {
            // Skip arrays and objects, as they are handled by handleRepeatingBlocks
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $stringValue = $this->formatValue($value);

            try {
                // Check if a placeholder with this key exists before setting the value
                // Note: PHPWord's TemplateProcessor doesn't have a direct `hasPlaceholder` method.
                // We rely on its internal handling, but we can log if a key is not found.
                $template->setValue($key, $stringValue);
            } catch (Exception $e) {
                // Log that a placeholder was in the data but not found in the template
                error_log("Placeholder '{$key}' was provided in data but not found in the template. Message: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle repeating blocks (tables, lists) in the template
     *
     * @param TemplateProcessor $template
     * @param array $data
     */
    private function handleRepeatingBlocks(TemplateProcessor $template, array $data): void
    {
        foreach ($data as $key => $value) {
            if (!is_array($value) || empty($value)) {
                continue;
            }

            // Check if it's a simple array of strings (for lists)
            if (is_string($value[0])) {
                $this->handleSimpleList($template, $key, $value);
                continue;
            }

            // Check if it's an array of associative arrays (for tables)
            if (is_array($value[0])) {
                $this->handleTableBlock($template, $key, $value);
            }
        }
    }

    /**
     * Handle simple lists (e.g., a list of comments)
     */
    private function handleSimpleList(TemplateProcessor $template, string $blockName, array $items): void
    {
        try {
            $template->cloneBlock($blockName, count($items), true, true);
            foreach ($items as $index => $item) {
                $placeholder = "{$blockName}#" . ($index + 1);
                $template->setValue($placeholder, $this->formatValue($item));
            }
        } catch (Exception $e) {
            error_log("Simple list block '{$blockName}' not found in template: " . $e->getMessage());
        }
    }

    /**
     * Handle table blocks (repeating rows)
     */
    private function handleTableBlock(TemplateProcessor $template, string $tableName, array $rows): void
    {
        try {
            if (empty($rows)) {
                return;
            }
            
            $firstRow = $rows[0];
            $firstKey = array_key_first($firstRow);

            if ($firstKey === null) {
                return;
            }

            // Clone the row based on the first placeholder found in the first item
            $template->cloneRow($firstKey, count($rows));

            // Iterate through each row of data
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;
                // Iterate through each key/value pair in the row
                foreach ($row as $itemKey => $itemValue) {
                    $placeholder = "{$itemKey}#{$rowNumber}";
                    $template->setValue($placeholder, $this->formatValue($itemValue));
                }
            }
        } catch (Exception $e) {
            error_log("Table block for '{$tableName}' (using key '{$firstKey}') not found in template: " . $e->getMessage());
        }
    }

    /**
     * Format a value for template replacement
     *
     * @param mixed $value
     * @return string
     */
    private function formatValue($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        // Sanitize string values to prevent issues with special XML characters
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return (string) $value;
    }

    private function convertToPdf(string $docxPath): string
    {
        if (!file_exists($docxPath)) {
            throw new Exception("Document source not found: {$docxPath}");
        }

        // Use a local converter (like LibreOffice) as a primary, fallback to Gotenberg
        $pdfPath = $this->localConvertToPdf($docxPath);

        if ($pdfPath && file_exists($pdfPath) && filesize($pdfPath) > 0) {
            error_log("DocumentGeneratorService: Conversion DOCX->PDF réussie (local).");
            return $pdfPath;
        }

        // Fallback to Gotenberg if local conversion fails
        error_log("DocumentGeneratorService: Local conversion failed, trying Gotenberg.");
        return $this->gotenbergConvertToPdf($docxPath);
    }

    /**
     * Convert DOCX to PDF using a local LibreOffice/OpenOffice installation.
     * This requires shell access and the office suite to be installed on the server.
     */
    private function localConvertToPdf(string $docxPath): ?string
    {
        if (!is_executable(escapeshellcmd('soffice'))) {
            error_log("LibreOffice 'soffice' command not found or not executable.");
            return null;
        }

        $outputDir = dirname($docxPath);
        $command = "soffice --headless --convert-to pdf " . escapeshellarg($docxPath) . " --outdir " . escapeshellarg($outputDir);

        shell_exec($command);

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);

        if (file_exists($pdfPath) && filesize($pdfPath) > 0) {
            return $pdfPath;
        }

        error_log("Local PDF conversion failed. Command: " . $command);
        return null;
    }

    /**
     * Convert a Word document to PDF using the Gotenberg API
     */
    private function gotenbergConvertToPdf(string $docxPath): string
    {
        $fileSize = filesize($docxPath);
        error_log("DocumentGeneratorService: Conversion DOCX->PDF (Gotenberg) démarrée. Fichier: " . basename($docxPath) . ", Taille: {$fileSize} bytes");

        $curl = curl_init();
        if ($curl === false) {
            throw new Exception("Impossible d'initialiser cURL.");
        }

        $mimeType = mime_content_type($docxPath) ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = new CURLFile($docxPath, $mimeType, basename($docxPath));
        $postData = ['files' => $file];

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->gotenbergUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $curlErrno !== 0) {
            throw new Exception("Erreur de communication avec le service de conversion (Gotenberg).");
        }

        if ($httpCode !== 200) {
            throw new Exception("Le service de conversion (Gotenberg) a retourné une erreur (Code: {$httpCode}).");
        }

        if (empty($response)) {
            throw new Exception("Le service de conversion (Gotenberg) a retourné une réponse vide.");
        }

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        if (file_put_contents($pdfPath, $response) === false) {
            throw new Exception("Erreur lors de l'enregistrement du fichier PDF.");
        }

        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            throw new Exception("La conversion a échoué : le fichier PDF final est invalide.");
        }

        return $pdfPath;
    }

    /**
     * Clean up a temporary PDF file
     *
     * @param string $pdfPath Path to the PDF file to delete
     * @return bool True if deleted successfully
     */
    public function cleanupTempFile(string $filePath): bool
    {
        // Security check: ensure the file is within the temp directory
        if (strpos(realpath($filePath), realpath($this->tempPath)) !== 0) {
            error_log("Attempt to delete file outside of temp directory: {$filePath}");
            return false;
        }

        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true; // File doesn't exist, so it's "cleaned up"
    }

    /**
     * Get list of available templates
     *
     * @return array Array of template filenames
     */
    public function listTemplates(): array
    {
        if (!is_dir($this->templatesPath)) {
            return [];
        }

        $files = scandir($this->templatesPath);
        $templates = [];
        foreach ($files as $file) {
            if (is_file($this->templatesPath . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'docx') {
                $templates[] = $file;
            }
        }
        return $templates;
    }

    /**
     * Get template file path
     *
     * @param string $templateName
     * @return string
     */
    public function getTemplatePath(string $templateName): ?string
    {
        if (substr_compare($templateName, '.docx', -5) !== 0) {
            $templateName .= '.docx';
        }
        
        $path = $this->templatesPath . $templateName;
        
        if (file_exists($path)) {
            return $path;
        }
        
        return null;
    }

    /**
     * Delete a template
     *
     * @param string $templateName
     * @return bool
     */
    public function deleteTemplate(string $templateName): bool
    {
        $templatePath = $this->getTemplatePath($templateName);
        if ($templatePath && file_exists($templatePath)) {
            // Security check: ensure the file is within the templates directory
            if (strpos(realpath($templatePath), realpath($this->templatesPath)) !== 0) {
                error_log("Attempt to delete file outside of templates directory: {$templatePath}");
                return false;
            }
            return unlink($templatePath);
        }
        return false; // File not found or path is null
    }

    /**
     * Save an uploaded template file
     *
     * @param array $uploadedFile The $_FILES array element
     * @param string $templateName Desired template name (without .docx)
     * @return bool
     * @throws Exception If upload fails
     */
    public function saveUploadedTemplate(array $uploadedFile, string $templateName): bool
    {
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error with code: " . $uploadedFile['error']);
        }

        // Validate file type based on MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile['tmp_name']);
        
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/msword', // .doc
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid file type. Only .docx files are allowed.");
        }

        // Sanitize filename
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', basename($templateName));
        if (substr_compare($sanitizedName, '.docx', -5) !== 0) {
            $sanitizedName .= '.docx';
        }

        $destinationPath = $this->templatesPath . $sanitizedName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            throw new Exception("Failed to save uploaded file to destination.");
        }

        return true;
    }

    /**
     * Convertit un document Word (.docx) en HTML en utilisant Gotenberg.
     *
     * @param string $docxPath Chemin vers le fichier .docx source.
     * @return string Le contenu HTML du document.
     * @throws Exception Si la conversion échoue ou si l'extension ZipArchive n'est pas disponible.
     */
    public function convertDocxToHtml(string $docxPath): string
    {
        if (!file_exists($docxPath)) {
            throw new Exception("Fichier modèle non trouvé : {$docxPath}");
        }

        // This functionality is complex and better handled by a dedicated service like Gotenberg.
        // The existing implementation using Gotenberg is solid.
        // For robustness, we can add more detailed error logging.

        $gotenbergUrl = 'http://gotenberg:3000/forms/libreoffice/convert';

        $curl = curl_init();
        if ($curl === false) {
            throw new Exception("Impossible d'initialiser cURL.");
        }

        $file = new CURLFile($docxPath, mime_content_type($docxPath) ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', basename($docxPath));
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $gotenbergUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['files' => $file],
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $httpCode !== 200) {
            error_log("Gotenberg DOCX->HTML conversion failed. Code: {$httpCode}, Error: {$error}");
            throw new Exception("Erreur du service de conversion DOCX->HTML.");
        }

        // Gotenberg returns a zip file containing index.html
        $tempZipFile = $this->tempPath . uniqid('gotenberg_html_') . '.zip';
        file_put_contents($tempZipFile, $response);

        $zip = new ZipArchive;
        if ($zip->open($tempZipFile) === TRUE) {
            $htmlContent = $zip->getFromName('index.html');
            $zip->close();
            unlink($tempZipFile);

            if ($htmlContent === false) {
                throw new Exception("Le fichier index.html n'a pas été trouvé dans l'archive de conversion.");
            }
            return $htmlContent;
        } else {
            unlink($tempZipFile);
            throw new Exception("Impossible d'ouvrir l'archive de conversion.");
        }
    }

    /**
     * Export data to CSV with UTF-8 BOM and semicolon separator for Excel compatibility
     * 
     * @param array $data Array of associative arrays (rows)
     * @param array $headers Column headers
     * @param string $filename Output filename (without path)
     * @param bool $download If true, send file for download. If false, return file path
     * @return string|void File path if $download is false, otherwise sends file and exits
     * @throws Exception If export fails
     */
    public function exportToCsv(array $data, array $headers, string $filename, bool $download = true)
    {
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', basename($filename));
        if (substr_compare($filename, '.csv', -4) !== 0) {
            $filename .= '.csv';
        }

        $csvPath = $this->tempPath . $filename;
        $output = fopen($csvPath, 'w');

        if ($output === false) {
            throw new Exception("Could not open temporary file for CSV export.");
        }

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($output, $headers, ';');
        
        // Write data rows
        foreach ($data as $row) {
            $orderedRow = [];
            foreach ($headers as $header) {
                // Use the key from headers to pull data from the row
                $orderedRow[] = $row[$header] ?? '';
            }
            fputcsv($output, $orderedRow, ';');
        }
        
        fclose($output);

        if ($download) {
            // Send file for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($csvPath));
            readfile($csvPath);
            
            // Clean up the temp file after download
            $this->cleanupTempFile($csvPath);
            exit;
        } else {
            // Return the path to the saved file
            return $csvPath;
        }
    }
}