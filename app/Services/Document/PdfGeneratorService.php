<?php

declare(strict_types=1);

namespace App\Services\Document;

// TODO: À créer quand le service de configuration système sera implémenté
use DateTimeImmutable;
use RuntimeException;
use TCPDF;

final class SilentTcpdf extends TCPDF
{
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->tcpdflink = false;
    }

    public function Header()
    {
    }

    public function Footer()
    {
    }
}

/**
 * Service de génération de documents PDF basé sur TCPDF.
 *
 * Fournit des méthodes réutilisables pour créer des PDFs avec:
 * - En-têtes institutionnels standardisés (logos configurables depuis la base)
 * - Pieds de page avec numérotation
 * - Gestion du stockage organisé par type et année
 * - Rendu HTML vers PDF
 */
final class PdfGeneratorService
{
    private const DEFAULT_FONT_SIZE = 11;
    private const DEFAULT_FONT_FAMILY = 'dejavuserif';
    private const TITLE_FONT_SIZE = 18;
    private const HEADER_HEIGHT = 30; // mm
    private const MARGIN_LEFT = 15; // mm
    private const MARGIN_RIGHT = 15; // mm
    private const MARGIN_TOP = 20; // mm
    private const MARGIN_BOTTOM = 25; // mm

    public function __construct(
        private readonly string $storagePath,
        private readonly string $logoPath,
        private readonly ?object $configService = null
    ) {
    }

    /**
     * Crée un nouveau document PDF avec les paramètres standard.
     *
     * @param string $orientation 'P' (Portrait) ou 'L' (Landscape)
     * @param string $format Format de page (A4, A5, etc.)
     * @param string $title Titre du document (métadonnées)
     * @param string $author Auteur du document (métadonnées)
     * @return TCPDF Instance TCPDF configurée
     */
    public function createDocument(
        string $orientation = 'P',
        string $format = 'A4',
        string $title = '',
        ?string $author = null
    ): TCPDF {
        // Utiliser le nom de l'application depuis la config si disponible
        $appName = 'CheckMaster UFRMI';
        if ($this->configService !== null) {
            $appName = $this->configService->getString('app_name', 'CheckMaster UFRMI') ?? 'CheckMaster UFRMI';
        }

        $authorName = (string)($author ?? $appName);

        $pdf = new SilentTcpdf($orientation, 'mm', $format, true, 'UTF-8', false);
        $pdf->setFontSubsetting(true);

        // Métadonnées du document
        $pdf->SetCreator((string)$appName);
        $pdf->SetAuthor((string)$authorName);
        $pdf->SetTitle((string)$title);
        $pdf->SetSubject('Document genere par ' . (string)$appName);

        // Désactiver les en-têtes/pieds de page par défaut de TCPDF
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Marges du document
        $pdf->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(true, self::MARGIN_BOTTOM);

        // Police par défaut
        $pdf->SetFont(self::DEFAULT_FONT_FAMILY, '', self::DEFAULT_FONT_SIZE);

        return $pdf;
    }

    /**
     * Ajoute un en-tête institutionnel avec les logos configurables.
     * Utilise les logos de la configuration si disponibles, sinon fallback vers les fichiers par défaut.
     *
     * @param TCPDF $pdf Instance PDF
     * @param string $title Titre principal de l'en-tête
     * @param string|null $subtitle Sous-titre optionnel
     */
    public function addHeader(TCPDF $pdf, string $title, ?string $subtitle = null): void
    {
        // Obtenir les chemins des logos depuis la config ou utiliser les défauts
        $logos = $this->resolveLogoPaths();
        $logoUfhb = $logos['logo_universite'];
        $logoUfrMi = $logos['logo_filiere'];

        // Position actuelle Y
        $startY = $pdf->GetY();
        $logoWidth = 25.0;
        $logoHeight = 18.0;

        // Logo Université (UFHB) à gauche
        if ($logoUfhb !== '' && is_file($logoUfhb)) {
            $pdf->Image($logoUfhb, self::MARGIN_LEFT, $startY, $logoWidth, 0, '');
        } else {
            $this->drawLogoPlaceholder($pdf, self::MARGIN_LEFT, $startY, $logoWidth, $logoHeight, 'LOGO UFHB');
        }

        // Logo Filière (UFR-MI) à droite
        $pageWidth = $pdf->getPageWidth();
        $logoRightX = $pageWidth - self::MARGIN_RIGHT - $logoWidth;
        if ($logoUfrMi !== '' && is_file($logoUfrMi)) {
            $pdf->Image($logoUfrMi, $logoRightX, $startY, $logoWidth, 0, '');
        } else {
            $this->drawLogoPlaceholder($pdf, $logoRightX, $startY, $logoWidth, $logoHeight, 'LOGO UFRMI');
        }

        // Titre centré
        $pdf->SetY($startY + 5);
        $pdf->SetFont(self::DEFAULT_FONT_FAMILY, 'B', self::TITLE_FONT_SIZE);
        $pdf->Cell(0, 10, (string)$title, 0, 1, 'C');

        // Sous-titre si fourni
        if ($subtitle !== null) {
            $pdf->SetFont(self::DEFAULT_FONT_FAMILY, 'I', 12);
            $pdf->Cell(0, 8, (string)$subtitle, 0, 1, 'C');
        }

        // Ligne de séparation
        $pdf->Ln(3);
        $pdf->Line(self::MARGIN_LEFT, $pdf->GetY(), $pdf->getPageWidth() - self::MARGIN_RIGHT, $pdf->GetY());
        $pdf->Ln(5);

        // Rétablir la police par défaut
        $pdf->SetFont(self::DEFAULT_FONT_FAMILY, '', self::DEFAULT_FONT_SIZE);
    }

    /**
     * Ajoute un pied de page standard avec numérotation.
     *
     * @param TCPDF $pdf Instance PDF
     */
    public function addFooter(TCPDF $pdf): void
    {
        // Aucun pied de page sur les rapports de stage.
    }

    /**
     * Sauvegarde le PDF dans le stockage et retourne le chemin complet.
     *
     * @param TCPDF $pdf Instance PDF à sauvegarder
     * @param string $subdir Sous-répertoire de type (recus, bulletins, etc.)
     * @param string $filename Nom du fichier (sans extension, elle sera ajoutée)
     * @return string Chemin complet du fichier sauvegardé
     * @throws RuntimeException Si l'écriture échoue
     */
    public function save(TCPDF $pdf, string $subdir, string $filename): string
    {
        // Créer le sous-répertoire avec année
        $year = (new DateTimeImmutable())->format('Y');
        $fullDir = $this->storagePath . '/' . $subdir . '/' . $year;

        if (!is_dir($fullDir)) {
            if (!mkdir($fullDir, 0755, true)) {
                throw new RuntimeException("Impossible de créer le répertoire: {$fullDir}");
            }
        }

        // Construire le chemin complet avec extension
        $fullPath = $fullDir . '/' . $filename . '.pdf';

        // Sauvegarder le PDF
        try {
            $this->runTcpdfWithoutDeprecationWarnings(static function () use ($pdf, $fullPath): void {
                $pdf->Output($fullPath, 'F');
            });
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la sauvegarde du PDF: " . $e->getMessage(), 0, $e);
        }

        return $fullPath;
    }

    /**
     * Obtient le chemin complet de stockage pour un type de document.
     *
     * @param string $type Type de document (recus, bulletins, etc.)
     * @return string Chemin complet du répertoire
     */
    public function getStoragePath(string $type): string
    {
        $year = (new DateTimeImmutable())->format('Y');
        return $this->storagePath . '/' . $type . '/' . $year;
    }

    /**
     * Écrit du contenu HTML dans le PDF.
     *
     * @param TCPDF $pdf Instance PDF
     * @param string $html Contenu HTML à rendre
     */
    public function writeHtml(TCPDF $pdf, string $html): void
    {
        $normalizedHtml = $this->normalizeHtmlForPdf($html);
        if (stripos($normalizedHtml, '<meta charset=') === false) {
            $normalizedHtml = '<meta charset="UTF-8">' . $normalizedHtml;
        }
        $this->runTcpdfWithoutDeprecationWarnings(static function () use ($pdf, $normalizedHtml): void {
            $pdf->writeHTML($normalizedHtml, true, false, true, false, '');
        });
    }

    private function normalizeHtmlForPdf(string $html): string
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

    private function runTcpdfWithoutDeprecationWarnings(callable $callback): mixed
    {
        $previousLevel = error_reporting();
        error_reporting($previousLevel & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            return $callback();
        } finally {
            error_reporting($previousLevel);
        }
    }

    /**
     * Ajoute une nouvelle page au document.
     *
     * @param TCPDF $pdf Instance PDF
     * @param bool $withHeader Si true, ajoute l'en-tête automatiquement
     */
    public function addPage(TCPDF $pdf, bool $withHeader = true): void
    {
        $pdf->AddPage();

        if ($withHeader) {
            // Note: L'appelant devra appeler addHeader() avec le titre approprié
            // car nous ne stockons pas le titre dans le service
        }
    }

    /**
     * Résout les chemins des logos depuis la configuration ou utilise les défauts.
     *
     * @return array{logo_universite: string, logo_filiere: string, logo_mairie: string}
     */
    public function resolveLogoPaths(): array
    {
        $defaults = [
            'logo_universite' => $this->resolveDefaultLogoPath('logo_universite'),
            'logo_filiere' => $this->resolveDefaultLogoPath('logo_filiere'),
            'logo_mairie' => $this->resolveDefaultLogoPath('logo_mairie'),
        ];

        // Si on a un service de configuration, utiliser les logos configurés
        if ($this->configService !== null) {
            $logos = $this->configService->getLogosForDocuments($this->logoPath);
            return [
                'logo_universite' => $this->resolveConfiguredPath((string) ($logos['logo_universite'] ?? ''), $defaults['logo_universite']),
                'logo_filiere' => $this->resolveConfiguredPath((string) ($logos['logo_filiere'] ?? ''), $defaults['logo_filiere']),
                'logo_mairie' => $this->resolveConfiguredPath((string) ($logos['logo_mairie'] ?? ''), $defaults['logo_mairie']),
            ];
        }

        return $defaults;
    }

    /**
     * Retourne le chemin vers le logo de la mairie/tutelle.
     * Utile pour les documents nécessitant ce logo spécifique.
     *
     * @return string Chemin du logo ou chaîne vide si non disponible
     */
    public function getLogoMairiePath(): string
    {
        $logos = $this->resolveLogoPaths();
        return $logos['logo_mairie'];
    }

    private function drawLogoPlaceholder(TCPDF $pdf, float $x, float $y, float $w, float $h, string $label): void
    {
        $pdf->SetDrawColor(140, 140, 140);
        $pdf->Rect($x, $y, $w, $h);
        $pdf->SetXY($x, $y + ($h / 2) - 2);
        $pdf->SetFont(self::DEFAULT_FONT_FAMILY, '', 6);
        $pdf->Cell($w, 4, $label, 0, 0, 'C');
        $pdf->SetFont(self::DEFAULT_FONT_FAMILY, '', self::DEFAULT_FONT_SIZE);
    }

    private function resolveConfiguredPath(string $configuredPath, string $fallback): string
    {
        $configuredPath = trim($configuredPath);
        if ($configuredPath !== '') {
            $direct = $this->toExistingPath($configuredPath);
            if ($direct !== '') {
                return $direct;
            }
        }

        return $fallback;
    }

    private function resolveDefaultLogoPath(string $kind): string
    {
        $projectRoot = $this->guessProjectRoot();
        $candidateDirs = array_unique(array_filter([
            $this->logoPath,
            $projectRoot . '/public/assets/img',
            $projectRoot . '/public/assets/images/logos',
            $projectRoot . '/public/uploads/logos',
            $projectRoot . '/public/image',
            $projectRoot . '/public/images',
        ], static fn (string $path): bool => $path !== ''));

        $filenames = match ($kind) {
            'logo_universite' => ['logo_ufhb.png', 'logo_ufhb.jpg', 'logo_universite.png', 'logo_universite.jpg', 'ufhb.png', 'ufhb.jpg'],
            'logo_filiere' => ['logo_mi.png', 'logo_ufrmi.png', 'logo_ufr_mi.png', 'logo_filiere.png', 'logo_filiere.jpg', 'ufrmi.png', 'ufrmi.jpg'],
            default => ['logo_mairie.png', 'logo_mairie.jpg', 'mairie.png', 'mairie.jpg'],
        };
        foreach ($candidateDirs as $dir) {
            foreach ($filenames as $name) {
                $path = rtrim($dir, '/\\') . '/' . $name;
                $existing = $this->toExistingPath($path);
                if ($existing !== '') {
                    return $existing;
                }
            }
        }

        return '';
    }

    private function toExistingPath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1 || str_starts_with($normalized, '/')) {
            return is_file($normalized) ? $normalized : '';
        }

        $projectRoot = $this->guessProjectRoot();
        $candidates = [
            $normalized,
            $projectRoot . '/' . ltrim($normalized, '/'),
            $projectRoot . '/public/' . ltrim($normalized, '/'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', $candidate);
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function guessProjectRoot(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 3));
    }
}
