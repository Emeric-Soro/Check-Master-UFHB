<?php

require_once __DIR__ . '/../../vendor/autoload.php';

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
    public function __construct(?string $templatesPath = null, ?string $tempPath = null)
    {
        $this->templatesPath = $templatesPath ?? __DIR__ . '/../../ressources/templates/';
        $this->tempPath = $tempPath ?? sys_get_temp_dir() . '/';
        $this->gotenbergUrl = 'http://gotenberg:3000/forms/libreoffice/convert';
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
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12pt; 
            line-height: 1.6; 
            color: #000;
        }
        @page { 
            size: {$paperSize}; 
            margin: {$marginTop}cm {$marginRight}cm {$marginBottom}cm {$marginLeft}cm;
        }
    </style>
</head>
<body>
{$htmlContent}
</body>
</html>";
        }
        
        $writeResult = file_put_contents($tempHtmlFile, $htmlContent);
        if ($writeResult === false) {
            error_log("Impossible d'écrire le fichier HTML temporaire: " . $tempHtmlFile);
            throw new Exception("Erreur lors de la création du fichier temporaire.");
        }

        try {
            // Use Gotenberg's Chromium HTML to PDF endpoint
            $gotenbergHtmlUrl = 'http://gotenberg:3000/forms/chromium/convert/html';
            
            error_log("DocumentGeneratorService: Conversion HTML->PDF démarrée. Taille HTML: " . strlen($htmlContent) . " bytes");
            
            $curl = curl_init();
            if ($curl === false) {
                throw new Exception("Impossible d'initialiser cURL.");
            }
            
            $file = new CURLFile($tempHtmlFile, 'text/html', basename($tempHtmlFile));
            
            $postData = [
                'files' => $file,
                'paperWidth' => $paperSize === 'A4' ? '8.27' : '11',
                'paperHeight' => $paperSize === 'A4' ? '11.7' : '8.5',
                'marginTop' => $marginTop,
                'marginBottom' => $marginBottom,
                'marginLeft' => $marginLeft,
                'marginRight' => $marginRight,
                'landscape' => $landscape ? 'true' : 'false',
                'printBackground' => 'true',
                'preferCssPageSize' => 'false'
            ];

            curl_setopt_array($curl, [
                CURLOPT_URL => $gotenbergHtmlUrl,
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

            // Clean up temporary HTML file
            if (file_exists($tempHtmlFile)) {
                unlink($tempHtmlFile);
            }

            if ($response === false || $curlErrno !== 0) {
                error_log("Erreur cURL vers Gotenberg (HTML->PDF). Errno: {$curlErrno}, Message: " . preg_replace('/[\r\n]+/', ' ', $error));
                throw new Exception("Le service de génération de documents est temporairement indisponible. Veuillez réessayer plus tard.");
            }

            if ($httpCode !== 200) {
                $sanitizedResponse = preg_replace('/[\r\n]+/', ' ', substr($response, 0, 500));
                error_log("Gotenberg a retourné une erreur (Code: {$httpCode}): " . $sanitizedResponse);
                throw new Exception("Le service de génération de documents a retourné une erreur (Code: {$httpCode}). Veuillez vérifier les logs du serveur.");
            }

            // Validate response is not empty
            if (empty($response)) {
                error_log("Gotenberg a retourné une réponse vide (Code: {$httpCode})");
                throw new Exception("Le service de génération a retourné une réponse vide.");
            }

            // Save PDF to temporary file
            $pdfPath = $this->tempPath . uniqid('pdf_') . '.pdf';
            $writeResult = file_put_contents($pdfPath, $response);

            if ($writeResult === false) {
                error_log("Impossible d'écrire le fichier PDF: " . $pdfPath);
                throw new Exception("Erreur lors de l'enregistrement du fichier PDF.");
            }

            if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
                error_log("La conversion HTML->PDF a réussi mais le fichier n'a pas pu être créé ou est vide. Chemin: " . $pdfPath);
                throw new Exception("La conversion a échoué : le fichier PDF final est invalide.");
            }

            error_log("DocumentGeneratorService: Conversion HTML->PDF réussie. Taille PDF: " . filesize($pdfPath) . " bytes");
            return $pdfPath;
            
        } catch (Exception $e) {
            // Clean up on error
            if (file_exists($tempHtmlFile)) {
                unlink($tempHtmlFile);
            }
            throw $e;
        }
    }

    /**
     * Generate a PDF document from a Word template
     *
     * @param string $templateName Name of the template file (without path)
     * @param array $data Associative array of data to merge with template
     * @return string Path to the generated PDF file
     * @throws Exception If template not found or conversion fails
     */
    public function generateFromTemplate(string $templateName, array $data): string
    {
        if (!str_ends_with($templateName, '.docx')) {
            $templateName .= '.docx';
        }

        $templatePath = $this->templatesPath . $templateName;

        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: {$templateName}");
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        $this->replacePlaceholders($templateProcessor, $data);
        $this->handleRepeatingBlocks($templateProcessor, $data);

        $tempDocxFile = $this->tempPath . uniqid('doc_') . '.docx';
        $templateProcessor->saveAs($tempDocxFile);

        $pdfFile = $this->convertToPdf($tempDocxFile);

        if (file_exists($tempDocxFile)) {
            unlink($tempDocxFile);
        }

        return $pdfFile;
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
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $stringValue = $this->formatValue($value);

            try {
                $template->setValue($key, $stringValue);
            } catch (Exception $e) {
                error_log("Placeholder '{$key}' not found in template: " . $e->getMessage());
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
            if (!is_array($value) || empty($value) || !is_array($value[0])) {
                continue;
            }

            try {
                $firstItem = $value[0];
                $firstKey = array_key_first($firstItem);

                if ($firstKey === null) {
                    continue;
                }

                $template->cloneRow($firstKey, count($value));

                foreach ($value as $index => $item) {
                    $rowNumber = $index + 1;
                    foreach ($item as $itemKey => $itemValue) {
                        $placeholder = "{$itemKey}#{$rowNumber}";
                        $template->setValue($placeholder, $this->formatValue($itemValue));
                    }
                }
            } catch (Exception $e) {
                error_log("Repeating block for array '{$key}' not found in template: " . $e->getMessage());
            }
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
        if ($value === null) return '';
        if (is_bool($value)) return $value ? 'Oui' : 'Non';
        return (string) $value;
    }

    /**
     * Convert a Word document to PDF using the Gotenberg API
     *
     * @param string $docxPath Path to the .docx file
     * @return string Path to the generated PDF file
     * @throws Exception If conversion fails
     */
    private function convertToPdf(string $docxPath): string
    {
        if (!file_exists($docxPath)) {
            error_log("DocumentGeneratorService: Fichier DOCX source introuvable: {$docxPath}");
            throw new Exception("Document source non trouvé : {$docxPath}");
        }

        $fileSize = filesize($docxPath);
        error_log("DocumentGeneratorService: Conversion DOCX->PDF démarrée. Fichier: " . basename($docxPath) . ", Taille: {$fileSize} bytes");

        $curl = curl_init();
        if ($curl === false) {
            error_log("DocumentGeneratorService: Impossible d'initialiser cURL");
            throw new Exception("Impossible d'initialiser cURL.");
        }

        $mimeType = mime_content_type($docxPath);
        if ($mimeType === false) {
            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

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
            error_log("DocumentGeneratorService: Erreur cURL vers Gotenberg. Errno: {$curlErrno}, Message: " . preg_replace('/[\r\n]+/', ' ', $error));
            throw new Exception("Erreur de communication avec le service de conversion de documents.");
        }

        if ($httpCode !== 200) {
            $sanitizedResponse = preg_replace('/[\r\n]+/', ' ', substr($response, 0, 500));
            error_log("DocumentGeneratorService: Gotenberg a retourné une erreur (Code: {$httpCode}): " . $sanitizedResponse);
            throw new Exception("Le service de conversion a retourné une erreur (Code: {$httpCode}). Veuillez vérifier les logs du serveur.");
        }

        if (empty($response)) {
            error_log("DocumentGeneratorService: Gotenberg a retourné une réponse vide (Code: {$httpCode})");
            throw new Exception("Le service de conversion a retourné une réponse vide.");
        }

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        $writeResult = file_put_contents($pdfPath, $response);

        if ($writeResult === false) {
            error_log("DocumentGeneratorService: Impossible d'écrire le fichier PDF: " . $pdfPath);
            throw new Exception("Erreur lors de l'enregistrement du fichier PDF.");
        }

        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            error_log("DocumentGeneratorService: Le fichier PDF est vide ou n'existe pas. Chemin: " . $pdfPath);
            throw new Exception("La conversion a échoué : le fichier PDF final est invalide.");
        }

        $pdfSize = filesize($pdfPath);
        error_log("DocumentGeneratorService: Conversion DOCX->PDF réussie. Taille PDF: {$pdfSize} bytes");

        return $pdfPath;
    }

    /**
     * Clean up a temporary PDF file
     *
     * @param string $pdfPath Path to the PDF file to delete
     * @return bool True if deleted successfully
     */
    public function cleanupTempFile(string $pdfPath): bool
    {
        if (file_exists($pdfPath)) {
            return unlink($pdfPath);
        }
        return true;
    }

    /**
     * Get list of available templates
     *
     * @return array Array of template filenames
     */
    public function listTemplates(): array
    {
        $templates = [];
        if (!is_dir($this->templatesPath)) return $templates;

        $files = scandir($this->templatesPath);
        foreach ($files as $file) {
            if (str_ends_with($file, '.docx') && !str_starts_with($file, '.')) {
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
    public function getTemplatePath(string $templateName): string
    {
        if (!str_ends_with($templateName, '.docx')) {
            $templateName .= '.docx';
        }
        return $this->templatesPath . $templateName;
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
        if (file_exists($templatePath)) {
            return unlink($templatePath);
        }
        return false;
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
            throw new Exception("Upload error: " . $uploadedFile['error']);
        }

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $uploadedFile['tmp_name']);
        finfo_close($fileInfo);

        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid file type. Only .docx files are allowed.");
        }

        if (!str_ends_with($templateName, '.docx')) {
            $templateName .= '.docx';
        }

        $templateName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $templateName);
        $destinationPath = $this->templatesPath . $templateName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            throw new Exception("Failed to save uploaded file.");
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
        if (!class_exists('ZipArchive')) {
            error_log("DocumentGeneratorService: Extension PHP ZipArchive non disponible");
            throw new Exception("L'extension PHP ZipArchive est requise mais n'est pas activée.");
        }

        if (!file_exists($docxPath)) {
            error_log("DocumentGeneratorService: Fichier modèle introuvable: {$docxPath}");
            throw new Exception("Fichier modèle non trouvé : {$docxPath}");
        }

        error_log("DocumentGeneratorService: Conversion DOCX->HTML démarrée. Fichier: " . basename($docxPath));

        $curl = curl_init();
        if ($curl === false) {
            error_log("DocumentGeneratorService: Impossible d'initialiser cURL");
            throw new Exception("Impossible d'initialiser cURL.");
        }

        $mimeType = mime_content_type($docxPath);
        if ($mimeType === false) {
            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        $file = new CURLFile($docxPath, $mimeType, basename($docxPath));
        
        $postData = [
            'files' => $file,
        ];

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
            error_log("DocumentGeneratorService: Erreur cURL vers Gotenberg (DOCX->HTML). Errno: {$curlErrno}, Message: " . preg_replace('/[\r\n]+/', ' ', $error));
            throw new Exception("Erreur de communication avec le service de conversion.");
        }

        if ($httpCode !== 200) {
            $sanitizedResponse = preg_replace('/[\r\n]+/', ' ', substr($response, 0, 500));
            error_log("DocumentGeneratorService: Gotenberg a retourné une erreur (Code: {$httpCode}): " . $sanitizedResponse);
            throw new Exception("Le service de conversion a retourné une erreur (Code: {$httpCode}).");
        }

        if (empty($response)) {
            error_log("DocumentGeneratorService: Gotenberg a retourné une réponse vide (Code: {$httpCode})");
            throw new Exception("Le service de conversion a retourné une réponse vide.");
        }

        // Gotenberg renvoie un zip contenant le fichier HTML, il faut le décompresser
        $tempZipFile = $this->tempPath . uniqid('gotenberg_html_') . '.zip';
        $writeResult = file_put_contents($tempZipFile, $response);

        if ($writeResult === false) {
            error_log("DocumentGeneratorService: Impossible d'écrire le fichier ZIP temporaire: " . $tempZipFile);
            throw new Exception("Erreur lors de l'enregistrement du fichier temporaire.");
        }

        $zip = new ZipArchive;
        if ($zip->open($tempZipFile) === TRUE) {
            // Gotenberg nomme le fichier HTML 'index.html' dans l'archive
            $htmlContent = $zip->getFromName('index.html');
            $zip->close();
            unlink($tempZipFile);

            if ($htmlContent === false) {
                error_log("DocumentGeneratorService: Le fichier index.html n'a pas été trouvé dans l'archive ZIP");
                throw new Exception("Le fichier index.html n'a pas été trouvé dans l'archive retournée par Gotenberg.");
            }
            
            error_log("DocumentGeneratorService: Conversion DOCX->HTML réussie. Taille HTML: " . strlen($htmlContent) . " bytes");
            return $htmlContent;
        } else {
            if (file_exists($tempZipFile)) {
                unlink($tempZipFile);
            }
            error_log("DocumentGeneratorService: Impossible d'ouvrir l'archive ZIP retournée par Gotenberg");
            throw new Exception("Impossible d'ouvrir l'archive ZIP retournée par le service de conversion.");
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
        // Clean filename
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        if (!str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }

        if ($download) {
            // Direct download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($output, "\xEF\xBB\xBF");
            
            // Write headers
            fputcsv($output, $headers, ';');
            
            // Write data rows
            foreach ($data as $row) {
                // Ensure row has values in same order as headers
                $orderedRow = [];
                foreach ($headers as $header) {
                    $orderedRow[] = $row[$header] ?? '';
                }
                fputcsv($output, $orderedRow, ';');
            }
            
            fclose($output);
            exit;
        } else {
            // Save to file
            $csvPath = $this->tempPath . $filename;
            $output = fopen($csvPath, 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($output, "\xEF\xBB\xBF");
            
            // Write headers
            fputcsv($output, $headers, ';');
            
            // Write data rows
            foreach ($data as $row) {
                $orderedRow = [];
                foreach ($headers as $header) {
                    $orderedRow[] = $row[$header] ?? '';
                }
                fputcsv($output, $orderedRow, ';');
            }
            
            fclose($output);
            return $csvPath;
        }
    }
}