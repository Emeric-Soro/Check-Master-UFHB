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
     * @return array{success: bool, reference?: string, path?: string, filename?: string, size?: int|null, error?: string}
     */
    public function generate(int $rapportId, int $userId): array
    {
        // 1. Fetch rapport with all details
        $rapport = $this->dataUtils->getRapportWithDetails($rapportId);
        if ($rapport === null) {
            return ['success' => false, 'error' => 'Rapport introuvable'];
        }

        // 2. Note: Allow PDF generation for all statuses (including drafts) for preview purposes
        // The PDF will be generated temporarily and may not be persisted if status is brouillon

        // 3. Fetch related entities
        $etudiant = $this->dataUtils->getEtudiantByNumCarte((string) ($rapport['matricule_etudiant'] ?? ''));
        if ($etudiant === null) {
            return ['success' => false, 'error' => 'Étudiant introuvable'];
        }

        $infoStage = $this->dataUtils->getInformationsStage((string) ($rapport['matricule_etudiant'] ?? ''));
        // Non bloquant : les infos de stage enrichissent le PDF mais ne sont pas obligatoires

        // 4. Generate unique reference
        $reference = $this->dataUtils->generateReference(self::TYPE_DOCUMENT);

        // 5. Create PDF document
        $pdf = $this->pdfGenerator->createDocument('P', 'A4', 'Rapport de Stage', 'CheckMaster UFRMI');

        // 6. Add content pages (unified document or legacy cover + body)
        try {
            $pdf->AddPage();
            $this->addContentPages($pdf, $rapport, $etudiant, $infoStage);
        } catch (RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

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
                'id_source' => (string) $rapportId,
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
            'filename' => basename($fullPath),
            'size' => $fileSize !== false ? (int) $fileSize : null,
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
        $pdf->SetFont('dejavuserif', '', 11);
        $rawHtml = $this->resolveStoredReportHtml($rapport);
        $rawHtml = $this->normalizeStoredHtml($rawHtml);

        if ($this->isUnifiedReportDocument($rawHtml)) {
            $htmlToRender = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
                . $rawHtml
                . '</body></html>';
        } else {
            $htmlToRender = $this->buildLegacyDocumentHtml($rapport, $etudiant, $infoStage, $rawHtml);
        }

        $this->pdfGenerator->writeHtml($pdf, $htmlToRender);
    }

    /**
     * Extracts body content from a JS-generated unified report document.
     * The body section is wrapped in an element with data-cm-report-body="1".
     */
    private function extractBodyFromUnifiedHtml(string $html): string
    {
        $domBody = $this->extractBodyFromUnifiedHtmlDom($html);
        if ($domBody !== '') {
            return $domBody;
        }

        // Method 1: content inside data-cm-report-body="1" attribute
        // The body section ends just before the closing </div> of the outer wrapper.
        if (preg_match('/data-cm-report-body="1"[^>]*>([\s\S]*?)<\/section>/i', $html, $m)) {
            $body = trim($m[1]);
            if ($body !== '') {
                return $body;
            }
        }

        // Method 2: extract everything after the empty page-break div
        // Structure: <div data-cm-report-page-break="1" ...></div><section ...>BODY</section></div>
        if (preg_match('/data-cm-report-page-break="1"[^>]*><\/div>([\s\S]*)/i', $html, $m)) {
            $rest = trim($m[1]);
            // Strip outer <section> opening tag and trailing </section></div>
            $rest = preg_replace('/^<section[^>]*>/is', '', $rest) ?? $rest;
            $rest = preg_replace('/<\/section>\s*<\/div>\s*$/is', '', $rest) ?? $rest;
            $body = trim($rest);
            if ($body !== '') {
                return $body;
            }
        }

        // Fallback: return the full HTML (TCPDF will attempt to render it)
        return $html;
    }

    private function extractBodyFromUnifiedHtmlDom(string $html): string
    {
        if (!class_exists(\DOMDocument::class) || trim($html) === '') {
            return '';
        }

        $wrapped = '<!DOCTYPE html><html><body>' . $html . '</body></html>';
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML($wrapped);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return '';
        }

        $xpath = new \DOMXPath($dom);
        $bodyNode = $xpath->query('//*[@data-cm-report-body="1"]');
        if ($bodyNode !== false && $bodyNode->length > 0) {
            return trim($this->innerHtml($bodyNode->item(0)));
        }

        $pageBreakNode = $xpath->query('//*[@data-cm-report-page-break="1"]');
        if ($pageBreakNode !== false && $pageBreakNode->length > 0) {
            $cursor = $pageBreakNode->item($pageBreakNode->length - 1)->nextSibling;
            $chunks = [];
            while ($cursor !== null) {
                if ($cursor->nodeType === XML_ELEMENT_NODE || $cursor->nodeType === XML_TEXT_NODE) {
                    $chunks[] = $dom->saveHTML($cursor) ?: '';
                }
                $cursor = $cursor->nextSibling;
            }
            $afterBreak = trim(implode('', $chunks));
            if ($afterBreak !== '') {
                return $afterBreak;
            }
        }

        return '';
    }

    private function innerHtml(?\DOMNode $node): string
    {
        if ($node === null) {
            return '';
        }

        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
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
        $nomEtu = (string) ($etudiant['nom_etu'] ?? '');
        $prenomEtu = (string) ($etudiant['prenom_etu'] ?? '');
        $nomComplet = trim(strtoupper($nomEtu) . ' ' . strtoupper($prenomEtu));
        $matricule = (string) ($etudiant['num_carte_etud'] ?? $etudiant['matricule_etudiant'] ?? $rapport['matricule_etudiant'] ?? '');

        $entreprise = $this->escapeHtml((string) ($infoStage['nom_entreprise'] ?? 'Entreprise d\'accueil'));
        $maitreStage = trim((string) ($infoStage['nom_maitre_stage'] ?? '') . ' ' . (string) ($infoStage['prenom_maitre_stage'] ?? ''));
        $maitreStage = $this->escapeHtml($maitreStage !== '' ? strtoupper($maitreStage) : 'MAITRE DE STAGE');
        $theme = $this->escapeHtml((string) ($rapport['theme_rapport'] ?? 'Thème du rapport'));
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

    <div style="margin-top:14mm; text-align:center; font-size:10pt; color:#475569;">Année académique {$academicYear}</div>
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

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
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
