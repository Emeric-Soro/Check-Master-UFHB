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
        if ((string) ($rapport['statut_rapport'] ?? '') === (enum_exists('App\Enum\StatutRapport') ? \App\Enum\StatutRapport::BROUILLON->value : 'brouillon')) {
            return ['success' => false, 'error' => 'Impossible de générer un PDF pour un rapport en brouillon'];
        }

        // 3. Fetch related entities
        $etudiant = $this->dataUtils->getEtudiantByNumCarte((string) ($rapport['matricule_etudiant'] ?? ''));
        if ($etudiant === null) {
            return ['success' => false, 'error' => 'Étudiant introuvable'];
        }

        $candidature = $this->dataUtils->getCandidatureByEtudiant((string) ($rapport['matricule_etudiant'] ?? ''));
        // Non bloquant : la candidature n'est pas nécessaire pour la génération du PDF

        $infoStage = $this->dataUtils->getInformationsStage((string) ($rapport['matricule_etudiant'] ?? ''));
        // Non bloquant : les infos de stage enrichissent le PDF mais ne sont pas obligatoires

        // 4. Generate unique reference
        $reference = $this->dataUtils->generateReference(self::TYPE_DOCUMENT);

        // 5. Create PDF document
        $pdf = $this->pdfGenerator->createDocument('P', 'A4', 'Rapport de Stage', 'CheckMaster UFRMI');

        // 6. Add content pages (includes cover page generated from data)
        $pdf->AddPage();
        $this->addContentPages($pdf, $rapport, $etudiant, $infoStage);

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
                    'titre' => $rapport['theme_rapport'],
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
     * @param array<string, mixed>|null $etudiant Student data
     * @param array<string, mixed>|null $infoStage Internship info
     */
    private function addContentPages(TCPDF $pdf, array $rapport, ?array $etudiant, ?array $infoStage): void
    {
        // Reset font to normal for content
        $pdf->SetFont('helvetica', '', 11);

        // 1. Générer la page de couverture
        $coverPageHtml = $this->generateCoverPageHTML($rapport, $etudiant, $infoStage);
        $this->pdfGenerator->writeHtml($pdf, $coverPageHtml);

        // 2. Ajouter un saut de page après la couverture
        $pdf->AddPage();

        // 3. Lire le contenu du fichier HTML (qui ne contient plus la page de couverture)
        $contenuHtml = '';
        if (!empty($rapport['chemin_fichier'])) {
            // chemin_fichier contient le nom du fichier (ex: rapport_1.html)
            // Le fichier est stocké dans ressources/uploads/rapports/
            $cheminComplet = __DIR__ . '/../../../ressources/uploads/rapports/' . $rapport['chemin_fichier'];

            if (file_exists($cheminComplet)) {
                $contenuHtml = file_get_contents($cheminComplet);
                if ($contenuHtml === false) {
                    $contenuHtml = '';
                }
            }
        }

        // Fallback sur contenu_html si le fichier n'existe pas ou est vide
        if (empty($contenuHtml) && !empty($rapport['contenu_html'])) {
            $contenuHtml = $rapport['contenu_html'];
        }

        // 4. Render HTML content
        // HTML is already sanitized by HtmlPurifierService, so render as-is
        $this->pdfGenerator->writeHtml($pdf, (string) $contenuHtml);
    }

    /**
     * Generates the cover page HTML for the report.
     *
     * @param array<string, mixed> $rapport Report data
     * @param array<string, mixed>|null $etudiant Student data
     * @param array<string, mixed>|null $infoStage Internship info
     * @return string Cover page HTML
     */
    private function generateCoverPageHTML(array $rapport, ?array $etudiant, ?array $infoStage): string
    {
        $baseUrl = 'http://localhost:8000/checkmaster.ufrmi-ufhb-ci/public';

        $nomEtu = $etudiant['nom_etu'] ?? '';
        $prenomEtu = $etudiant['prenom_etu'] ?? '';
        $nomComplet = trim(strtoupper($nomEtu) . ' ' . strtoupper($prenomEtu));
        $genreEtu = $etudiant['genre_etu'] ?? 'M';
        $civilite = $genreEtu === 'F' ? 'Mme' : 'M.';

        $entreprise = $infoStage['nom_entreprise'] ?? 'Entreprise d\'accueil';
        $maitreStage = $infoStage['nom_maitre_stage'] ?? '';
        $theme = $rapport['theme_rapport'] ?? 'Thème du rapport';

        return <<<HTML
<div style="font-family: 'Times New Roman', serif; width: 210mm; min-height: 297mm; padding: 20mm 25mm; box-sizing: border-box; background: white;">
    <table style="width: 100%; border: none; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%; text-align: left; font-size: 10pt; vertical-align: top; border: none; padding: 0;">
                MINISTERE DE L'ENSEIGNEMENT SUPERIEUR<br/>ET DE LA RECHERCHE SCIENTIFIQUE
            </td>
            <td style="width: 50%; text-align: right; font-size: 10pt; vertical-align: top; border: none; padding: 0;">
                REPUBLIQUE DE COTE D'IVOIRE<br/>UNION - DISCIPLINE - TRAVAIL
            </td>
        </tr>
    </table>
    
    <table style="width: 100%; border: none; margin: 15px 0 20px 0;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top; border: none; padding: 10px;">
                <img src="{$baseUrl}/image/logo_ufhb.png" alt="Logo UFHB" style="width: 70px; height: auto;"/><br/><br/>
                <span style="font-size: 11pt; font-weight: bold; color: #1a5276;">UNIVERSITE FELIX HOUPHOUET BOIGNY</span><br/><br/>
                <span style="font-size: 10pt;">UFR MATHEMATIQUES ET INFORMATIQUE</span><br/>
                <span style="font-size: 10pt;">FILIERES PROFESSIONNALISEES MIAGE-GI</span>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top; border: none; padding: 10px;">
                <img src="{$baseUrl}/image/logo_civ.png" alt="Armoiries CI" style="width: 65px; height: auto;"/>
                <img src="{$baseUrl}/image/logoCM.png" alt="Logo Entreprise" style="width: 65px; height: auto; margin-left: 15px;"/><br/><br/>
                <span style="font-size: 11pt; font-weight: bold;">{$entreprise}</span>
            </td>
        </tr>
    </table>
    
    <div style="text-align: center; margin: 25px 0 15px 0;">
        <p style="font-size: 11pt; margin: 0 0 8px 0;">Memoire de fin de cycle pour l'obtention du :</p>
        <p style="font-size: 12pt; font-weight: bold; font-style: italic; margin: 0 0 5px 0;">
            Diplome d'Ingenieur de conception en informatique
        </p>
        <p style="font-size: 10pt; font-style: italic; margin: 0;">
            Option Methodes Informatiques Appliquees a la Gestion des Entreprises
        </p>
    </div>
    
    <div style="text-align: center; margin: 25px 0;">
        <p style="font-size: 11pt; font-weight: bold; margin: 0 0 12px 0;">Theme :</p>
        <div style="background-color: #1B5E20; color: white; padding: 18px 25px; margin: 0 auto; width: 95%; text-align: center;">
            <p style="font-size: 13pt; font-weight: bold; text-transform: uppercase; line-height: 1.5; margin: 0; text-align: center;">
                {$theme}
            </p>
        </div>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
        <tr>
            <td style="width: 50%; padding: 20px; border: 2px solid #000; text-align: center; vertical-align: top;">
                <p style="font-size: 11pt; margin: 0 0 10px 0;">SOUTENU PAR :</p>
                <p style="font-size: 11pt; font-weight: bold; margin: 0;">{$civilite} {$nomComplet}</p>
            </td>
            <td style="width: 50%; padding: 20px; border: 2px solid #000; text-align: center; vertical-align: top;">
                <p style="font-size: 11pt; font-weight: bold; margin: 0 0 15px 0; text-align: center;">MAITRE DE STAGE</p>
                <p style="font-size: 11pt; font-weight: bold; margin: 0; text-align: center;">{$maitreStage}</p>
            </td>
        </tr>
    </table>
</div>
HTML;
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
