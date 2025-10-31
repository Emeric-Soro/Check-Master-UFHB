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
        // Enhanced report with better formatting and more details
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport de Stage</title>
            <style>
                @page { margin: 2cm; }
                body { 
                    font-family: "Times New Roman", Times, serif; 
                    margin: 0; 
                    padding: 0;
                    line-height: 1.8; 
                    font-size: 12pt;
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px; 
                    border-bottom: 3px solid #1a5276; 
                    padding-bottom: 20px; 
                }
                .header img { max-width: 120px; margin-bottom: 10px; }
                .header h1 { 
                    color: #1a5276; 
                    margin: 10px 0; 
                    font-size: 24pt;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                .header .subtitle { 
                    color: #666; 
                    font-size: 14pt;
                    font-style: italic;
                }
                .info { 
                    margin: 30px 0;
                    padding: 20px;
                    background: #f8f9fa; 
                    border-left: 5px solid #1a5276;
                    border-radius: 3px;
                }
                .info-section { margin-bottom: 25px; }
                .info-section h3 {
                    color: #1a5276;
                    font-size: 14pt;
                    margin-bottom: 15px;
                    border-bottom: 2px solid #ddd;
                    padding-bottom: 5px;
                }
                .info-row { 
                    margin: 10px 0;
                    padding: 5px 0;
                }
                .info-row strong { 
                    display: inline-block; 
                    width: 180px;
                    color: #1a5276;
                    font-weight: bold;
                }
                .content { 
                    margin-top: 40px;
                    text-align: justify;
                    text-indent: 30px;
                }
                .content h2 {
                    color: #1a5276;
                    font-size: 16pt;
                    margin-top: 30px;
                    margin-bottom: 15px;
                    border-bottom: 2px solid #1a5276;
                    padding-bottom: 8px;
                }
                .content h3 {
                    color: #1a5276;
                    font-size: 14pt;
                    margin-top: 20px;
                    margin-bottom: 10px;
                }
                .content p {
                    margin-bottom: 15px;
                }
                .evaluations {
                    margin: 30px 0;
                    padding: 20px;
                    background: #fff3cd;
                    border-left: 5px solid #ffc107;
                    border-radius: 3px;
                }
                .evaluations h3 {
                    color: #856404;
                    margin-top: 0;
                }
                .evaluation-item {
                    margin: 15px 0;
                    padding: 10px;
                    background: white;
                    border-radius: 3px;
                }
                .footer { 
                    margin-top: 60px; 
                    text-align: center; 
                    font-size: 10pt; 
                    color: #666;
                    border-top: 2px solid #ddd;
                    padding-top: 20px;
                }
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 120pt;
                    color: rgba(200, 200, 200, 0.3);
                    z-index: -1;
                }
            </style>
        </head>
        <body>';
        
        // Add watermark for draft status
        if (isset($reportData['statut_rapport']) && in_array($reportData['statut_rapport'], ['brouillon', 'en_cours', 'draft'])) {
            $html .= '<div class="watermark">BROUILLON</div>';
        }
        
        $html .= '
            <div class="header">
                <h1>Rapport de Stage</h1>
                <div class="subtitle">Université Félix Houphouët-Boigny</div>
                <div class="subtitle">Année Académique ' . (date('Y') - 1) . '-' . date('Y') . '</div>
            </div>
            
            <div class="info">
                <div class="info-section">
                    <h3>Informations sur l\'Étudiant</h3>
                    <div class="info-row"><strong>Nom et Prénoms:</strong> ' . htmlspecialchars($reportData['nom_etu'] . ' ' . $reportData['prenom_etu']) . '</div>
                    <div class="info-row"><strong>Email:</strong> ' . htmlspecialchars($reportData['email_etu']) . '</div>';
        
        if (isset($reportData['matricule'])) {
            $html .= '<div class="info-row"><strong>Matricule:</strong> ' . htmlspecialchars($reportData['matricule']) . '</div>';
        }
        
        $html .= '
                </div>
                
                <div class="info-section">
                    <h3>Informations sur le Rapport</h3>
                    <div class="info-row"><strong>Titre:</strong> ' . htmlspecialchars($reportData['nom_rapport']) . '</div>
                    <div class="info-row"><strong>Thème:</strong> ' . htmlspecialchars($reportData['theme_rapport']) . '</div>
                    <div class="info-row"><strong>Date de dépôt:</strong> ' . date('d/m/Y', strtotime($reportData['date_depot'])) . '</div>
                    <div class="info-row"><strong>Statut:</strong> <span style="color: ' . $this->getStatusColor($reportData['statut_rapport']) . ';">' . htmlspecialchars($this->formatStatus($reportData['statut_rapport'])) . '</span></div>
                </div>';
        
        // Add supervisor information if available
        if (isset($reportData['encadrant_nom']) || isset($reportData['directeur_nom'])) {
            $html .= '<div class="info-section"><h3>Encadrement</h3>';
            
            if (isset($reportData['encadrant_nom'])) {
                $html .= '<div class="info-row"><strong>Encadrant pédagogique:</strong> ' . htmlspecialchars($reportData['encadrant_prenom'] . ' ' . $reportData['encadrant_nom']) . '</div>';
            }
            
            if (isset($reportData['directeur_nom'])) {
                $html .= '<div class="info-row"><strong>Directeur de mémoire:</strong> ' . htmlspecialchars($reportData['directeur_prenom'] . ' ' . $reportData['directeur_nom']) . '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Add content
        if (!empty($reportData['contenu_rapport'])) {
            $html .= '<div class="content">' . $reportData['contenu_rapport'] . '</div>';
        }
        
        // Add evaluations if available
        if (isset($reportData['evaluations']) && !empty($reportData['evaluations'])) {
            $html .= '<div class="evaluations">
                        <h3>Évaluations</h3>';
            
            foreach ($reportData['evaluations'] as $eval) {
                $html .= '<div class="evaluation-item">
                            <strong>Évaluateur:</strong> ' . htmlspecialchars($eval['prenom'] . ' ' . $eval['nom']) . '<br>
                            <strong>Note:</strong> ' . htmlspecialchars($eval['note']) . '/20<br>
                            <strong>Date:</strong> ' . date('d/m/Y', strtotime($eval['date_evaluation'])) . '<br>
                            <strong>Commentaire:</strong> ' . htmlspecialchars($eval['commentaire']) . '
                          </div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '
            <div class="footer">
                <p><strong>Check Master - Système de Gestion des Soutenances</strong></p>
                <p>Document généré automatiquement le ' . date('d/m/Y à H:i') . '</p>
                <p>Ce document est confidentiel et ne peut être reproduit sans autorisation</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Get color for status display
     */
    private function getStatusColor(string $status): string
    {
        $colors = [
            'validé' => '#28a745',
            'approuvé' => '#28a745',
            'en_attente' => '#ffc107',
            'en_cours' => '#17a2b8',
            'rejeté' => '#dc3545',
            'brouillon' => '#6c757d',
            'draft' => '#6c757d'
        ];
        
        return $colors[$status] ?? '#6c757d';
    }
    
    /**
     * Format status for display
     */
    private function formatStatus(string $status): string
    {
        $statuses = [
            'validé' => 'Validé',
            'approuvé' => 'Approuvé',
            'en_attente' => 'En Attente',
            'en_cours' => 'En Cours',
            'rejeté' => 'Rejeté',
            'brouillon' => 'Brouillon',
            'draft' => 'Brouillon'
        ];
        
        return $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
    
    /**
     * Build HTML for meeting minutes
     * 
     * @param array $minutesData Minutes data
     * @return string HTML content
     */
    /**
     * Build HTML for meeting minutes (PV/Compte Rendu)
     * 
     * @param array $minutesData Minutes data
     * @return string HTML content
     */
    private function buildMinutesHtml(array $minutesData): string
    {
        // Enhanced PV with official format and better structure
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Procès-Verbal de Soutenance</title>
            <style>
                @page { margin: 2.5cm; }
                body { 
                    font-family: "Times New Roman", Times, serif; 
                    margin: 0; 
                    padding: 0;
                    line-height: 1.8; 
                    font-size: 12pt;
                    color: #000;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px; 
                    border-bottom: 4px double #1a5276; 
                    padding-bottom: 25px; 
                }
                .header .logo-section {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }
                .header h1 { 
                    color: #1a5276; 
                    margin: 15px 0 5px 0; 
                    font-size: 22pt;
                    text-transform: uppercase;
                    letter-spacing: 3px;
                    font-weight: bold;
                }
                .header .subtitle { 
                    color: #333; 
                    font-size: 13pt;
                    margin: 8px 0;
                    font-weight: bold;
                }
                .header .reference {
                    text-align: right;
                    font-size: 10pt;
                    margin-top: 15px;
                    color: #666;
                }
                .info-box { 
                    margin: 25px 0;
                    padding: 20px;
                    background: #f8f9fa; 
                    border: 2px solid #1a5276;
                    border-radius: 5px;
                }
                .info-box h3 {
                    color: #1a5276;
                    font-size: 14pt;
                    margin: 0 0 15px 0;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #1a5276;
                }
                .info-row { 
                    margin: 10px 0;
                    padding: 5px 0;
                }
                .info-row strong { 
                    display: inline-block; 
                    width: 160px;
                    color: #1a5276;
                    font-weight: bold;
                }
                .section { 
                    margin: 30px 0;
                    page-break-inside: avoid;
                }
                .section-title {
                    color: #1a5276;
                    font-size: 15pt;
                    font-weight: bold;
                    margin: 25px 0 15px 0;
                    padding: 10px 0 10px 15px;
                    background: #f0f0f0;
                    border-left: 5px solid #1a5276;
                }
                .content { 
                    margin: 20px 0;
                    text-align: justify;
                    line-height: 2;
                }
                .content p {
                    margin-bottom: 15px;
                    text-indent: 30px;
                }
                .content h4 {
                    color: #1a5276;
                    font-size: 13pt;
                    margin: 20px 0 10px 0;
                    font-weight: bold;
                }
                .rapports-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .rapports-table th {
                    background: #1a5276;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                }
                .rapports-table td {
                    padding: 10px 12px;
                    border: 1px solid #ddd;
                }
                .rapports-table tr:nth-child(even) {
                    background: #f8f9fa;
                }
                .jury-section {
                    margin: 30px 0;
                    padding: 20px;
                    background: #fff3cd;
                    border-left: 5px solid #ffc107;
                }
                .jury-member {
                    margin: 10px 0;
                    padding: 8px;
                }
                .deliberation {
                    margin: 30px 0;
                    padding: 20px;
                    background: #d4edda;
                    border: 2px solid #28a745;
                    border-radius: 5px;
                }
                .deliberation h3 {
                    color: #155724;
                    margin-top: 0;
                }
                .signatures { 
                    margin-top: 60px;
                    page-break-inside: avoid;
                }
                .signature-table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 20px 0;
                }
                .signature-cell {
                    text-align: center;
                    vertical-align: top;
                }
                .signature-line { 
                    border-top: 2px solid #000; 
                    width: 200px; 
                    margin: 60px auto 10px;
                }
                .signature-label {
                    font-weight: bold;
                    color: #1a5276;
                }
                .footer { 
                    margin-top: 40px; 
                    padding-top: 20px;
                    border-top: 2px solid #ddd;
                    font-size: 9pt; 
                    color: #666;
                    text-align: center;
                }
                .page-number {
                    position: fixed;
                    bottom: 1cm;
                    right: 1cm;
                    font-size: 10pt;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>PROCÈS-VERBAL DE SOUTENANCE</h1>
                <div class="subtitle">Université Félix Houphouët-Boigny</div>
                <div class="subtitle">UFR Mathématiques et Informatique</div>
                <div class="subtitle">Master MIAGE</div>
                <div class="reference">
                    Réf: PV-' . date('Y') . '-' . str_pad($minutesData['id_CR'] ?? '001', 4, '0', STR_PAD_LEFT) . '<br>
                    Année Académique ' . (date('Y') - 1) . '-' . date('Y') . '
                </div>
            </div>
            
            <div class="info-box">
                <h3>Informations Générales</h3>
                <div class="info-row"><strong>Titre de la séance:</strong> ' . htmlspecialchars($minutesData['nom_CR']) . '</div>
                <div class="info-row"><strong>Date et heure:</strong> ' . date('d/m/Y à H:i', strtotime($minutesData['date_CR'])) . '</div>
                <div class="info-row"><strong>Lieu:</strong> ' . (isset($minutesData['lieu']) ? htmlspecialchars($minutesData['lieu']) : 'Salle de soutenance, UFHB') . '</div>
                <div class="info-row"><strong>Secrétaire de séance:</strong> ' . htmlspecialchars($minutesData['prenom_etu'] . ' ' . $minutesData['nom_etu']) . '</div>
            </div>';
        
        // Add jury members if available
        if (isset($minutesData['jury_members']) && !empty($minutesData['jury_members'])) {
            $html .= '
            <div class="jury-section">
                <div class="section-title">COMPOSITION DU JURY</div>';
            
            foreach ($minutesData['jury_members'] as $member) {
                $html .= '<div class="jury-member">
                            <strong>' . htmlspecialchars($member['prenom'] . ' ' . $member['nom']) . '</strong><br>
                            ' . htmlspecialchars($member['fonction']) . ' - ' . htmlspecialchars($member['role_jury']) . '
                          </div>';
            }
            
            $html .= '</div>';
        }
        
        // Main content
        $html .= '
            <div class="section">
                <div class="section-title">COMPTE RENDU DE LA SÉANCE</div>
                <div class="content">' . $minutesData['contenu_CR'] . '</div>
            </div>';
        
        // Add associated reports table if available
        if (!empty($minutesData['rapports'])) {
            $html .= '
            <div class="section">
                <div class="section-title">TRAVAUX ÉVALUÉS</div>
                <table class="rapports-table">
                    <thead>
                        <tr>
                            <th>Titre du Rapport</th>
                            <th>Étudiant</th>
                            <th>Thème</th>
                            <th>Encadrant</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($minutesData['rapports'] as $rapport) {
                $html .= '<tr>
                            <td><strong>' . htmlspecialchars($rapport['nom_rapport']) . '</strong></td>
                            <td>' . htmlspecialchars($rapport['prenom_etu'] . ' ' . $rapport['nom_etu']) . '</td>
                            <td>' . htmlspecialchars($rapport['theme_rapport'] ?? 'N/A') . '</td>
                            <td>' . (isset($rapport['enc_nom']) ? htmlspecialchars($rapport['enc_prenom'] . ' ' . $rapport['enc_nom']) : 'N/A') . '</td>
                          </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>';
        }
        
        // Add deliberation section if available
        if (isset($minutesData['deliberation'])) {
            $html .= '
            <div class="deliberation">
                <h3>DÉLIBÉRATION DU JURY</h3>
                <p>' . htmlspecialchars($minutesData['deliberation']) . '</p>
            </div>';
        }
        
        // Signatures section
        $html .= '
            <div class="signatures">
                <p style="margin-bottom: 30px;"><em>Fait à Abidjan, le ' . date('d/m/Y') . '</em></p>
                <table class="signature-table">
                    <tr>
                        <td class="signature-cell">
                            <div class="signature-line"></div>
                            <div class="signature-label">Le Président du Jury</div>
                        </td>
                        <td class="signature-cell">
                            <div class="signature-line"></div>
                            <div class="signature-label">Le Rapporteur</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="signature-cell">
                            <div class="signature-line"></div>
                            <div class="signature-label">Le Secrétaire de Séance</div>
                        </td>
                        <td class="signature-cell">
                            <div class="signature-line"></div>
                            <div class="signature-label">Le Directeur de l\'UFR</div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="footer">
                <p><strong>Université Félix Houphouët-Boigny</strong></p>
                <p>22 BP 582 Abidjan 22 - Côte d\'Ivoire | Tél: +225 22 44 08 95</p>
                <p><em>Document officiel généré automatiquement par Check Master</em></p>
                <p>Généré le ' . date('d/m/Y à H:i') . ' | Ce document ne peut être reproduit sans autorisation</p>
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
