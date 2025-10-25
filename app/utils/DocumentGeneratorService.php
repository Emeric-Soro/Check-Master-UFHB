<?php

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

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

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
     * @param \PhpOffice\PhpWord\TemplateProcessor $template
     * @param array $data
     */
    private function replacePlaceholders(\PhpOffice\PhpWord\TemplateProcessor $template, array $data): void
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
     * @param \PhpOffice\PhpWord\TemplateProcessor $template
     * @param array $data
     */
    private function handleRepeatingBlocks(\PhpOffice\PhpWord\TemplateProcessor $template, array $data): void
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
            throw new Exception("Document source non trouvé : {$docxPath}");
        }

        $curl = curl_init();
        $file = new CURLFile($docxPath, mime_content_type($docxPath), basename($docxPath));
        $postData = ['files' => $file];

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->gotenbergUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception("Erreur cURL vers Gotenberg : " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Gotenberg a retourné une erreur (Code: {$httpCode}): " . $response);
        }

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        file_put_contents($pdfPath, $response);

        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            throw new Exception("La conversion a réussi mais le fichier PDF n'a pas pu être créé ou est vide.");
        }

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
}