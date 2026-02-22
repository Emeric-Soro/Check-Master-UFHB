<?php

declare(strict_types=1);

namespace App\Services\Document;

// TODO: À créer quand l'enum StatutRapport sera implémenté
use App\Utils\PlanningDataUtils;
use DateTimeImmutable;
use RuntimeException;
use TCPDF;

/**
 * Service de génération de PDF pour les rapports de stage.
 *
 * Génère des PDFs structurés avec:
 * - Page de garde institutionnelle avec informations stage
 * - Contenu HTML du rapport (formaté et préservé)
 * - Enregistrement dans document_genere avec référence RAP-YYYY-NNNNN
 * - Mise à jour de rapport.chemin_fichier_pdf et rapport.reference_document
 */
final class RapportPdfGeneratorService
{
    private const TYPE_DOCUMENT = 'RAP';

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly PlanningDataUtils $dataUtils
    ) {
    }

    /**
     * Generate internship report PDF from HTML content.
     *
     * @param int $rapportId Report ID
     * @param int $userId User generating the document
     * @return array{success: bool, reference?: string, path?: string, error?: string}
     */
    public function generate(int $rapportId, int $userId): array
    {
        // 1. Fetch rapport with all details
        $rapport = $this->dataUtils->getRapportWithDetails($rapportId);
        if ($rapport === null) {
            return ['success' => false, 'error' => 'Rapport introuvable'];
        }

        // 2. Validate status: cannot generate PDF for drafts
        if ((string)($rapport['statut_rapport'] ?? '') === (enum_exists('App\Enum\StatutRapport') ? \App\Enum\StatutRapport::BROUILLON->value : 'brouillon')) {
            return ['success' => false, 'error' => 'Impossible de générer un PDF pour un rapport en brouillon'];
        }

        // 3. Fetch related entities
        $etudiant = $this->dataUtils->getEtudiantByNumCarte((string)($rapport['matricule_etudiant'] ?? ''));
        if ($etudiant === null) {
            return ['success' => false, 'error' => 'Étudiant introuvable'];
        }

        $candidature = $this->dataUtils->getCandidatureByEtudiant((string)($rapport['matricule_etudiant'] ?? ''));
        if ($candidature === null) {
            return ['success' => false, 'error' => 'Candidature introuvable pour cet étudiant et cette année'];
        }

        $infoStage = $this->dataUtils->getInformationsStage((string)($rapport['matricule_etudiant'] ?? ''));
        if ($infoStage === null) {
            return ['success' => false, 'error' => 'Informations de stage introuvables'];
        }

        // 4. Generate unique reference
        $reference = $this->dataUtils->generateReference(self::TYPE_DOCUMENT);

        // 5. Create PDF document
        $pdf = $this->pdfGenerator->createDocument('P', 'A4', 'Rapport de Stage', 'CheckMaster UFRMI');

        // 6. Add content pages (includes cover if a model is used)
        $pdf->AddPage();
        $this->addContentPages($pdf, $rapport);

        // 7. Add footer to all pages
        $this->addFooterToAllPages($pdf);

        // 9. Save PDF to storage
        try {
            $filename = $this->buildFilename($rapport, $etudiant);
            $fullPath = $this->pdfGenerator->save($pdf, 'rapports', $filename);
        } catch (RuntimeException $e) {
            return ['success' => false, 'error' => 'Erreur lors de la sauvegarde du PDF: ' . $e->getMessage()];
        }

        // 10. Get file size
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

        // 11. Register in document_genere
        try {
            $this->dataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => self::TYPE_DOCUMENT,
                'nom_fichier' => $filename . '.pdf',
                'chemin_fichier' => $fullPath,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : null,
                'mime_type' => 'application/pdf',
                'metadata' => json_encode([
                    'rapport_id' => $rapportId,
                    'matricule' => $rapport['matricule_etudiant'],
                    'titre' => $rapport['titre_rapport'],
                    'annee_academique' => $rapport['libelle_annee'],
                ]),
                'id_utilisateur_generation' => $userId,
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement du document: ' . $e->getMessage()];
        }

        // 12. Update rapport with path and reference
        try {
            $this->dataUtils->updateRapport($rapportId, [
                'chemin_fichier_pdf' => $fullPath,
                'reference_document' => $reference,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : null,
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour du rapport: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'reference' => $reference,
            'path' => $fullPath,
        ];
    }

    /**
     * Adds content pages from HTML report content.
     *
     * @param TCPDF $pdf PDF instance
     * @param array<string, mixed> $rapport Report data
     */
    private function addContentPages(TCPDF $pdf, array $rapport): void
    {
        // Reset font to normal for content
        $pdf->SetFont('helvetica', '', 11);

        // Render HTML content directly
        // HTML is already sanitized by HtmlPurifierService, so render as-is
        $this->pdfGenerator->writeHtml($pdf, (string) ($rapport['chemin_fichier'] ?? $rapport['contenu_html'] ?? ''));
    }

    /**
     * Adds footer to all pages in the document.
     *
     * @param TCPDF $pdf PDF instance
     */
    private function addFooterToAllPages(TCPDF $pdf): void
    {
        $totalPages = $pdf->getNumPages();

        for ($i = 1; $i <= $totalPages; $i++) {
            $pdf->setPage($i);
            $this->pdfGenerator->addFooter($pdf);
        }
    }

    /**
     * Builds filename for the report PDF.
     * Format: rapport_{matricule}_{year}_{timestamp}
     *
     * @param array<string, mixed> $rapport Report data
     * @param array<string, mixed> $etudiant Student data
     * @return string Filename without extension
     */
    private function buildFilename(array $rapport, array $etudiant): string
    {
        $year = date('Y');
        $timestamp = (new DateTimeImmutable())->format('Ymd_His');
        $matricule = $etudiant['num_carte_etud'] ?? $etudiant['matricule_etudiant'] ?? $rapport['matricule_etudiant'] ?? 'inconnu';

        return "rapport_{$matricule}_{$year}_{$timestamp}";
    }
}
