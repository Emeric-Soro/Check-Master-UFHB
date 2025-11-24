<?php
/**
 * Document Generator Service
 * Generates PDF documents using mPDF from HTML templates
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class DocumentGeneratorService {
    
    /**
     * Generate PDF from a view template
     * 
     * @param string $viewPath Path to the PHP view file relative to project root
     * @param array $data Associative array of data to pass to the view
     * @param string $filename Name of the PDF file to generate
     * @param string $output Output mode: 'D' = Download, 'I' = Inline, 'S' = String, 'F' = File
     * @param array $config mPDF configuration options
     * @return mixed Depends on $output parameter
     */
    public static function generatePdfFromView($viewPath, $data = [], $filename = 'document.pdf', $output = 'D', $config = []) {
        try {
            // Extract data array to variables for the view
            extract($data);
            
            // Capture the view output
            ob_start();
            include $viewPath;
            $html = ob_get_clean();
            
            // Default mPDF configuration
            $defaultConfig = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
                'default_font' => 'dejavusans'
            ];
            
            // Merge with custom config
            $pdfConfig = array_merge($defaultConfig, $config);
            
            // Create mPDF instance
            $mpdf = new Mpdf($pdfConfig);
            
            // Set document properties
            $mpdf->SetTitle($filename);
            $mpdf->SetAuthor('CheckMaster UFHB');
            $mpdf->SetCreator('CheckMaster - Université Félix Houphouët-Boigny');
            
            // Write HTML content
            $mpdf->WriteHTML($html);
            
            // Output the PDF
            return $mpdf->Output($filename, $output);
            
        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            throw new Exception("Erreur lors de la génération du PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Generate payment receipt PDF
     * 
     * @param array $data Payment data
     * @param string $filename Output filename
     * @param string $output Output mode
     * @return mixed
     */
    public static function generateReceiptPdf($data, $filename = 'recu_paiement.pdf', $output = 'D') {
        $viewPath = __DIR__ . '/../../ressources/views/pdf/recu_paiement.php';
        return self::generatePdfFromView($viewPath, $data, $filename, $output);
    }
    
    /**
     * Generate grade report PDF
     * 
     * @param array $data Grade data
     * @param string $filename Output filename
     * @param string $output Output mode
     * @return mixed
     */
    public static function generateGradeReportPdf($data, $filename = 'releve_notes.pdf', $output = 'D') {
        $viewPath = __DIR__ . '/../../ressources/views/pdf/releve_notes.php';
        return self::generatePdfFromView($viewPath, $data, $filename, $output);
    }
    
    /**
     * Generate defense report (PV) PDF
     * 
     * @param array $data Defense data
     * @param string $filename Output filename
     * @param string $output Output mode
     * @return mixed
     */
    public static function generateDefenseReportPdf($data, $filename = 'pv_soutenance.pdf', $output = 'D') {
        $viewPath = __DIR__ . '/../../ressources/views/pdf/pv_soutenance.php';
        
        // Configure for landscape if needed (optional)
        $config = [
            'format' => 'A4',
            'orientation' => 'P' // Portrait
        ];
        
        return self::generatePdfFromView($viewPath, $data, $filename, $output, $config);
    }
    
    /**
     * Generate PDF and save to file
     * 
     * @param string $viewPath Path to the PHP view file
     * @param array $data Data for the view
     * @param string $filepath Full path where to save the PDF
     * @return bool Success status
     */
    public static function savePdfToFile($viewPath, $data, $filepath) {
        try {
            self::generatePdfFromView($viewPath, $data, basename($filepath), 'F');
            return true;
        } catch (Exception $e) {
            error_log("Error saving PDF to file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate PDF and return as string
     * 
     * @param string $viewPath Path to the PHP view file
     * @param array $data Data for the view
     * @return string PDF content as string
     */
    public static function getPdfAsString($viewPath, $data) {
        return self::generatePdfFromView($viewPath, $data, 'document.pdf', Destination::STRING_RETURN);
    }
}
