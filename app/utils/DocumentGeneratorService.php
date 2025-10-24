<?php

/**
 * DocumentGeneratorService
 * 
 * Service for generating PDF documents from Word templates (.docx)
 * Uses PHPWord for template processing and LibreOffice for PDF conversion
 */
class DocumentGeneratorService
{
    private string $templatesPath;
    private string $tempPath;
    private string $libreOfficePath;
    
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
        $this->libreOfficePath = 'soffice'; // Default command, can be overridden
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
        // Ensure template has .docx extension
        if (!str_ends_with($templateName, '.docx')) {
            $templateName .= '.docx';
        }
        
        $templatePath = $this->templatesPath . $templateName;
        
        // Verify template exists
        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: {$templateName}");
        }
        
        // Load template with PHPWord
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        
        // Replace simple placeholders
        $this->replacePlaceholders($templateProcessor, $data);
        
        // Handle repeating blocks (tables, lists)
        $this->handleRepeatingBlocks($templateProcessor, $data);
        
        // Generate unique filename for temporary docx
        $tempDocxFile = $this->tempPath . uniqid('doc_') . '.docx';
        
        // Save the processed document
        $templateProcessor->saveAs($tempDocxFile);
        
        // Convert to PDF using LibreOffice
        $pdfFile = $this->convertToPdf($tempDocxFile);
        
        // Clean up temporary docx file
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
            // Skip arrays (they are handled as repeating blocks)
            if (is_array($value)) {
                continue;
            }
            
            // Handle nested data with dot notation (e.g., etudiant.nom)
            if (is_object($value)) {
                continue;
            }
            
            // Convert value to string
            $stringValue = $this->formatValue($value);
            
            try {
                $template->setValue($key, $stringValue);
            } catch (Exception $e) {
                // Placeholder might not exist in template, continue
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
            // Only process arrays
            if (!is_array($value)) {
                continue;
            }
            
            // Check if this is a repeating block (array of associative arrays)
            if (!empty($value) && is_array($value[0])) {
                try {
                    // Get the first key from the first item - this will be used as the clone key
                    $firstItem = $value[0];
                    $firstKey = array_key_first($firstItem);
                    
                    if ($firstKey === null) {
                        continue;
                    }
                    
                    // Clone the row based on the first key in the data
                    $template->cloneRow($firstKey, count($value));
                    
                    // Fill in the data for each row
                    foreach ($value as $index => $item) {
                        $rowNumber = $index + 1;
                        foreach ($item as $itemKey => $itemValue) {
                            $placeholder = "{$itemKey}#{$rowNumber}";
                            $template->setValue($placeholder, $this->formatValue($itemValue));
                        }
                    }
                } catch (Exception $e) {
                    // Block might not exist in template, continue
                    error_log("Repeating block for array '{$key}' not found in template: " . $e->getMessage());
                }
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
        if ($value === null) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return (string) $value;
    }
    
    /**
     * Convert a Word document to PDF using LibreOffice
     * 
     * @param string $docxPath Path to the .docx file
     * @return string Path to the generated PDF file
     * @throws Exception If conversion fails
     */
    private function convertToPdf(string $docxPath): string
    {
        if (!file_exists($docxPath)) {
            throw new Exception("Document not found: {$docxPath}");
        }
        
        // Output directory for PDF (same as temp directory)
        $outputDir = dirname($docxPath);
        
        // Build LibreOffice command
        $command = sprintf(
            '%s --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellcmd($this->libreOfficePath),
            escapeshellarg($outputDir),
            escapeshellarg($docxPath)
        );
        
        // Execute conversion
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        // Check if conversion was successful
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        
        if ($returnVar !== 0 || !file_exists($pdfPath)) {
            $errorMessage = "LibreOffice conversion failed. ";
            $errorMessage .= "Return code: {$returnVar}. ";
            $errorMessage .= "Output: " . implode("\n", $output);
            throw new Exception($errorMessage);
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
        
        if (!is_dir($this->templatesPath)) {
            return $templates;
        }
        
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
        // Validate upload
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error: " . $uploadedFile['error']);
        }
        
        // Validate file type
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $uploadedFile['tmp_name']);
        finfo_close($fileInfo);
        
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream' // Sometimes .docx files are detected as this
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid file type. Only .docx files are allowed.");
        }
        
        // Ensure template name has .docx extension
        if (!str_ends_with($templateName, '.docx')) {
            $templateName .= '.docx';
        }
        
        // Sanitize template name
        $templateName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $templateName);
        
        $destinationPath = $this->templatesPath . $templateName;
        
        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            throw new Exception("Failed to save uploaded file.");
        }
        
        return true;
    }
}
