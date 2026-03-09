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
 * Génère des PDFs à partir du document HTML réellement sauvegardé :
 * - document unifié (couverture + corps) si présent
 * - reconstruction couverture + corps pour les anciens rapports
 * - enregistrement du PDF dans document_genere avec référence RAP-YYYY-NNNNN
 * - sans écraser le chemin HTML source stocké dans rapport_etudiants.chemin_fichier
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
                    'annee_academique' => $rapport['libelle_annee'] ?? ($rapport['id_annee_acad'] ?? null),
                ]),
                'id_utilisateur_generation' => $userId,
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement du document: ' . $e->getMessage()];
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
        $nomEtu = (string) ($etudiant['nom_etu'] ?? '');
        $prenomEtu = (string) ($etudiant['prenom_etu'] ?? '');
        $nomComplet = trim(strtoupper($nomEtu) . ' ' . strtoupper($prenomEtu));
        $matricule = (string) ($etudiant['num_carte_etud'] ?? $etudiant['matricule_etudiant'] ?? $rapport['matricule_etudiant'] ?? '');

        $entreprise = $this->escapeHtml((string) ($infoStage['nom_entreprise'] ?? 'Entreprise d\'accueil'));
        $maitreStage = trim((string) ($infoStage['nom_maitre_stage'] ?? '') . ' ' . (string) ($infoStage['prenom_maitre_stage'] ?? ''));
        $maitreStage = $this->escapeHtml($maitreStage !== '' ? strtoupper($maitreStage) : 'MAITRE DE STAGE');
        $theme = $this->escapeHtml((string) ($rapport['theme_rapport'] ?? 'Theme du rapport'));
        $nomComplet = $this->escapeHtml($nomComplet !== '' ? $nomComplet : 'ETUDIANT NON RENSEIGNE');
        $academicYear = $this->escapeHtml((string) ($rapport['libelle_annee'] ?? ($rapport['id_annee_acad'] ?? '')));
        $logoUfhb = $this->imageDataUri(__DIR__ . '/../../../public/image/logo_ufhb.png');
        $logoCiv = $this->imageDataUri(__DIR__ . '/../../../public/image/logo_civ.png');
        $logoCm = $this->imageDataUri(__DIR__ . '/../../../public/image/logoCM.png');

        $logoUfhbHtml = $logoUfhb !== '' ? '<img src="' . $logoUfhb . '" alt="Logo UFHB" style="width: 70px; height: auto; display: block; margin: 0 auto 8px;"/>' : '';
        $logoCivHtml = $logoCiv !== '' ? '<img src="' . $logoCiv . '" alt="Armoiries CI" style="width: 65px; height: auto;"/>' : '';
        $logoCmHtml = $logoCm !== '' ? '<img src="' . $logoCm . '" alt="Logo CheckMaster" style="width: 65px; height: auto; margin-left: 15px;"/>' : '';

        $matriculeHtml = $matricule !== '' ? '<p style="margin:6px 0 0; font-size:10pt;">Matricule : ' . $this->escapeHtml($matricule) . '</p>' : '';

        return <<<HTML
<div style="font-family:'Times New Roman', serif; width:210mm; min-height:297mm; padding:20mm 22mm 18mm; box-sizing:border-box; background:#ffffff; color:#111827;">
    <table style="width:100%; border:none; margin-bottom:8mm; border-collapse:collapse;">
        <tr>
            <td style="width:50%; vertical-align:top; border:none; padding:0; font-size:10pt; line-height:1.45;">
                MINISTERE DE L'ENSEIGNEMENT SUPERIEUR<br/>ET DE LA RECHERCHE SCIENTIFIQUE
            </td>
            <td style="width:50%; text-align:right; vertical-align:top; border:none; padding:0; font-size:10pt; line-height:1.45;">
                REPUBLIQUE DE COTE D'IVOIRE<br/>UNION - DISCIPLINE - TRAVAIL
            </td>
        </tr>
    </table>

    <table style="width:100%; border:none; margin:0 0 12mm; border-collapse:collapse;">
        <tr>
            <td style="width:50%; vertical-align:top; border:none; padding:0 10mm 0 0; text-align:center;">
                {$logoUfhbHtml}
                <div style="font-size:11pt; font-weight:bold; color:#0f4666; line-height:1.5;">UNIVERSITE FELIX HOUPHOUET BOIGNY</div>
                <div style="font-size:10pt; line-height:1.55; margin-top:6px;">UFR MATHEMATIQUES ET INFORMATIQUE<br/>FILIERES PROFESSIONNALISEES MIAGE-GI</div>
            </td>
            <td style="width:50%; vertical-align:top; border:none; padding:0 0 0 10mm; text-align:center;">
                <div style="margin-bottom:10px;">{$logoCivHtml}{$logoCmHtml}</div>
                <div style="font-size:11pt; font-weight:bold; line-height:1.5; color:#0f172a;">{$entreprise}</div>
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin:0 0 10mm;">
        <p style="margin:0 0 6px; font-size:11pt;">RAPPORT DE STAGE POUR L'OBTENTION DU</p>
        <p style="margin:0; font-size:13pt; font-weight:bold; font-style:italic; color:#0f4666;">Diplome d'Ingenieur de conception en informatique</p>
        <p style="margin:6px 0 0; font-size:10pt; font-style:italic;">Option Methodes Informatiques Appliquees a la Gestion des Entreprises</p>
    </div>

    <div style="margin:0 auto 12mm; border-radius:18px; border:2px solid #0f4666; background:#f6fbff; padding:10mm 9mm; text-align:center;">
        <p style="margin:0 0 8px; font-size:11pt; font-weight:bold; text-transform:uppercase; letter-spacing:0.04em; color:#0f4666;">Theme</p>
        <p style="margin:0; font-size:14pt; font-weight:bold; line-height:1.6; text-transform:uppercase;">{$theme}</p>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-top:12mm;">
        <tr>
            <td style="width:50%; border:1.5px solid #111827; padding:12mm 8mm; vertical-align:top; text-align:center;">
                <p style="margin:0 0 8px; font-size:10.5pt; font-weight:bold;">SOUTENU PAR</p>
                <p style="margin:0; font-size:12pt; font-weight:bold; line-height:1.6;">{$nomComplet}</p>
                {$matriculeHtml}
            </td>
            <td style="width:50%; border:1.5px solid #111827; padding:12mm 8mm; vertical-align:top; text-align:center;">
                <p style="margin:0 0 8px; font-size:10.5pt; font-weight:bold;">MAITRE DE STAGE</p>
                <p style="margin:0; font-size:12pt; font-weight:bold; line-height:1.6;">{$maitreStage}</p>
            </td>
        </tr>
    </table>

    <div style="margin-top:14mm; text-align:center; font-size:10pt; color:#475569;">Annee academique {$academicYear}</div>
</div>
HTML;
    }

    private function resolveStoredReportHtml(array $rapport): string
    {
        if (!empty($rapport['contenu_html']) && trim((string) $rapport['contenu_html']) !== '') {
            return (string) $rapport['contenu_html'];
        }

        $htmlPath = $this->resolveStoredHtmlPath((string) ($rapport['chemin_fichier'] ?? ''));
        if ($htmlPath !== null) {
            $contenuHtml = file_get_contents($htmlPath);
            if ($contenuHtml !== false && trim($contenuHtml) !== '') {
                return $contenuHtml;
            }
        }

        $fallbackPath = __DIR__ . '/../../../ressources/uploads/rapports/rapport_' . (int) ($rapport['id_rapport'] ?? 0) . '.html';
        if (is_file($fallbackPath) && is_readable($fallbackPath)) {
            $contenuHtml = file_get_contents($fallbackPath);
            if ($contenuHtml !== false && trim($contenuHtml) !== '') {
                return $contenuHtml;
            }
        }

        throw new RuntimeException('Contenu du rapport introuvable ou vide.');
    }

    private function resolveStoredHtmlPath(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\/', $storedPath) === 1) {
            $candidate = $storedPath;
        } else {
            $candidate = __DIR__ . '/../../../ressources/uploads/rapports/' . basename($storedPath);
        }

        if (strtolower((string) pathinfo($candidate, PATHINFO_EXTENSION)) !== 'html') {
            return null;
        }

        return is_file($candidate) && is_readable($candidate) ? $candidate : null;
    }

    private function isUnifiedReportDocument(string $html): bool
    {
        return str_contains($html, 'data-cm-report-document="1"')
            || str_contains($html, 'cm-report-editor-document');
    }

    private function buildLegacyDocumentHtml(array $rapport, ?array $etudiant, ?array $infoStage, string $bodyHtml): string
    {
        $bodyHtml = trim($this->normalizeStoredHtml($bodyHtml));
        if ($bodyHtml === '') {
            throw new RuntimeException('Contenu du rapport introuvable ou vide.');
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
            . $this->generateCoverPageHTML($rapport, $etudiant, $infoStage)
            . '<div style="page-break-before: always;"></div>'
            . '<div style="font-family:\'Times New Roman\', serif; width:210mm; min-height:297mm; padding:18mm 20mm 20mm; box-sizing:border-box; background:#ffffff; color:#111827;">'
            . $bodyHtml
            . '</div>'
            . '</body></html>';
    }

    private function normalizeStoredHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = str_replace(
            ["'Times New Roman', serif", "\"Times New Roman\", serif", "Times New Roman"],
            ["'dejavuserif', serif", "\"dejavuserif\", serif", "dejavuserif"],
            $html
        );

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function imageDataUri(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }

        $binary = file_get_contents($path);
        if ($binary === false) {
            return '';
        }

        $mime = function_exists('mime_content_type') ? (string) mime_content_type($path) : 'image/png';
        if ($mime === '' || !str_starts_with($mime, 'image/')) {
            $mime = 'image/png';
        }

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
