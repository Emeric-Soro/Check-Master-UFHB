<?php

/**
 * PDF Generator Service
 * Centralized service for generating PDFs from HTML templates using Dompdf
 * Handles receipts, transcripts, reports, and meeting minutes
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGeneratorService
{
    private $options;
    private $basePath;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = new Options();
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('isFontSubsettingEnabled', true);
        $this->options->set('defaultFont', 'DejaVu Sans');
        
        $this->basePath = realpath(__DIR__ . '/../../public');
    }
    
    /**
     * Generate PDF from HTML content
     * 
     * @param string $html HTML content
     * @param string $filename Output filename
     * @param string $orientation 'portrait' or 'landscape'
     * @param string $paperSize Paper size (A4, Letter, etc.)
     * @param bool $download Whether to force download (true) or display inline (false)
     * @return void
     */
    public function generateFromHtml(string $html, string $filename = 'document.pdf', string $orientation = 'portrait', string $paperSize = 'A4', bool $download = false)
    {
        $dompdf = new Dompdf($this->options);
        
        if ($this->basePath) {
            $dompdf->setBasePath($this->basePath);
        }
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paperSize, $orientation);
        $dompdf->render();
        
        $dompdf->stream($filename, ['Attachment' => $download]);
    }
    
    /**
     * Generate PDF from a view file
     * 
     * @param string $viewPath Path to the view file
     * @param array $data Data to pass to the view
     * @param string $filename Output filename
     * @param string $orientation 'portrait' or 'landscape'
     * @param string $paperSize Paper size (A4, Letter, etc.)
     * @param bool $download Whether to force download
     * @return void
     */
    public function generateFromView(string $viewPath, array $data = [], string $filename = 'document.pdf', string $orientation = 'portrait', string $paperSize = 'A4', bool $download = false)
    {
        if (!file_exists($viewPath)) {
            throw new Exception("View file not found: {$viewPath}");
        }
        
        // Extract data to make variables available in view
        extract($data);
        
        // Capture view output
        ob_start();
        include $viewPath;
        $html = ob_get_clean();
        
        $this->generateFromHtml($html, $filename, $orientation, $paperSize, $download);
    }
    
    /**
     * Generate receipt PDF (reçu de paiement)
     * 
     * @param int $receiptId Receipt ID (id_inscription or id_versement)
     * @param string $type Type of receipt ('inscription' or 'versement')
     * @return void
     */
    public function generateReceipt(int $receiptId, string $type = 'inscription')
    {
        $viewBasePath = __DIR__ . '/../../ressources/views/';
        
        if ($type === 'inscription') {
            $viewPath = $viewBasePath . 'gestion_etudiants/recu_inscription.php';
            $filename = "recu_inscription_{$receiptId}.pdf";
            $data = ['id_inscription' => $receiptId];
        } else {
            $viewPath = $viewBasePath . 'recu_versement.php';
            $filename = "recu_paiement_{$receiptId}.pdf";
            $data = ['id_versement' => $receiptId];
        }
        
        $this->generateFromView($viewPath, $data, $filename, 'landscape', 'A4', false);
    }
    
    /**
     * Generate transcript PDF (relevé de notes)
     * 
     * @param int $studentId Student ID
     * @param string $level Academic level
     * @return void
     */
    public function generateTranscript(int $studentId, string $level)
    {
        $viewPath = __DIR__ . '/../../ressources/views/releve_notes.php';
        $filename = "releve_notes_{$studentId}_{$level}.pdf";
        $data = [
            'id_etudiant' => $studentId,
            'niveau' => $level
        ];
        
        $this->generateFromView($viewPath, $data, $filename, 'portrait', 'A4', false);
    }
    
    /**
     * Generate report PDF (rapport de stage)
     * 
     * @param int $reportId Report ID
     * @param array $reportData Report data from database
     * @return void
     */
    public function generateReport(int $reportId, array $reportData)
    {
        $html = $this->buildReportHtml($reportData);
        $filename = "rapport_{$reportData['nom_rapport']}_{$reportId}.pdf";
        
        $this->generateFromHtml($html, $filename, 'portrait', 'A4', false);
    }
    
    /**
     * Generate meeting minutes PDF (compte rendu / PV)
     * 
     * @param int $minutesId Minutes ID
     * @param array $minutesData Minutes data from database
     * @return void
     */
    public function generateMinutes(int $minutesId, array $minutesData)
    {
        $html = $this->buildMinutesHtml($minutesData);
        $filename = "compte_rendu_{$minutesData['nom_CR']}_{$minutesId}.pdf";
        
        $this->generateFromHtml($html, $filename, 'portrait', 'A4', false);
    }
    
    /**
     * Save PDF to file instead of streaming
     * 
     * @param string $html HTML content
     * @param string $outputPath Full path where to save the PDF
     * @param string $orientation 'portrait' or 'landscape'
     * @param string $paperSize Paper size
     * @return bool Success status
     */
    public function savePdfToFile(string $html, string $outputPath, string $orientation = 'portrait', string $paperSize = 'A4'): bool
    {
        try {
            $dompdf = new Dompdf($this->options);
            
            if ($this->basePath) {
                $dompdf->setBasePath($this->basePath);
            }
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper($paperSize, $orientation);
            $dompdf->render();
            
            // Create directory if it doesn't exist
            $directory = dirname($outputPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Save PDF content to file
            $output = $dompdf->output();
            file_put_contents($outputPath, $output);
            
            return file_exists($outputPath) && filesize($outputPath) > 0;
        } catch (Exception $e) {
            error_log("Error saving PDF: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build HTML for report
     * 
     * @param array $reportData Report data
     * @return string HTML content
     */
    private function buildReportHtml(array $reportData): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport de Stage</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .header h1 { color: #1a5276; margin: 0; }
                .info { margin-bottom: 20px; }
                .info-row { margin: 5px 0; }
                .info-row strong { display: inline-block; width: 150px; }
                .content { margin-top: 30px; }
                .footer { margin-top: 40px; text-align: center; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Rapport de Stage</h1>
                <p>Université Félix Houphouët-Boigny</p>
            </div>
            
            <div class="info">
                <div class="info-row"><strong>Étudiant:</strong> ' . htmlspecialchars($reportData['nom_etu'] . ' ' . $reportData['prenom_etu']) . '</div>
                <div class="info-row"><strong>Email:</strong> ' . htmlspecialchars($reportData['email_etu']) . '</div>
                <div class="info-row"><strong>Titre:</strong> ' . htmlspecialchars($reportData['nom_rapport']) . '</div>
                <div class="info-row"><strong>Thème:</strong> ' . htmlspecialchars($reportData['theme_rapport']) . '</div>
                <div class="info-row"><strong>Date de dépôt:</strong> ' . date('d/m/Y', strtotime($reportData['date_depot'])) . '</div>
                <div class="info-row"><strong>Statut:</strong> ' . htmlspecialchars($reportData['statut_rapport']) . '</div>
            </div>
            
            <div class="content">
                ' . $reportData['contenu_rapport'] . '
            </div>
            
            <div class="footer">
                <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Build HTML for meeting minutes
     * 
     * @param array $minutesData Minutes data
     * @return string HTML content
     */
    private function buildMinutesHtml(array $minutesData): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Compte Rendu</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #1a5276; padding-bottom: 15px; }
                .header h1 { color: #1a5276; margin: 0; font-size: 24px; }
                .header h2 { color: #666; margin: 5px 0; font-size: 18px; font-weight: normal; }
                .info { margin-bottom: 30px; background: #f8f9fa; padding: 15px; border-radius: 5px; }
                .info-row { margin: 8px 0; }
                .info-row strong { display: inline-block; width: 120px; color: #1a5276; }
                .content { margin-top: 30px; text-align: justify; }
                .content h3 { color: #1a5276; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
                .rapports-list { margin: 20px 0; }
                .rapport-item { padding: 10px; margin: 10px 0; background: #f8f9fa; border-left: 4px solid #1a5276; }
                .footer { margin-top: 50px; text-align: center; font-size: 0.9em; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                .signature { margin-top: 40px; display: flex; justify-content: space-around; }
                .signature-block { text-align: center; }
                .signature-line { border-top: 1px solid #000; width: 200px; margin: 40px auto 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Compte Rendu de Soutenance</h1>
                <h2>Université Félix Houphouët-Boigny - MIAGE</h2>
            </div>
            
            <div class="info">
                <div class="info-row"><strong>Titre:</strong> ' . htmlspecialchars($minutesData['nom_CR']) . '</div>
                <div class="info-row"><strong>Date:</strong> ' . date('d/m/Y à H:i', strtotime($minutesData['date_CR'])) . '</div>
                <div class="info-row"><strong>Rédacteur:</strong> ' . htmlspecialchars($minutesData['nom_etu'] . ' ' . $minutesData['prenom_etu']) . '</div>
            </div>
            
            <div class="content">
                <h3>Contenu du Compte Rendu</h3>
                ' . $minutesData['contenu_CR'] . '
            </div>';
        
        // Add associated reports if available
        if (!empty($minutesData['rapports'])) {
            $html .= '
            <div class="rapports-list">
                <h3>Rapports Évalués</h3>';
            
            foreach ($minutesData['rapports'] as $rapport) {
                $html .= '
                <div class="rapport-item">
                    <strong>' . htmlspecialchars($rapport['nom_rapport']) . '</strong><br>
                    Étudiant: ' . htmlspecialchars($rapport['prenom_etu'] . ' ' . $rapport['nom_etu']) . '<br>
                    Thème: ' . htmlspecialchars($rapport['theme_rapport']) . '
                </div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '
            <div class="signature">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <p>Le Président du Jury</p>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <p>Le Secrétaire</p>
                </div>
            </div>
            
            <div class="footer">
                <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
                <p>Check Master - Système de Gestion des Soutenances</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Set custom options
     * 
     * @param string $option Option name
     * @param mixed $value Option value
     * @return void
     */
    public function setOption(string $option, $value): void
    {
        $this->options->set($option, $value);
    }
    
    /**
     * Set base path for assets
     * 
     * @param string $path Base path
     * @return void
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = $path;
    }
}
