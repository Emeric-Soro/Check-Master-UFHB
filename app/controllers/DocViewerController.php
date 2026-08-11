<?php
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';
require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
require_once __DIR__ . '/../Services/Document/RapportPdfGeneratorService.php';
require_once __DIR__ . '/../Services/Document/RecuGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PvCommissionGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PvFinalGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PlanningGeneratorService.php';
require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../utils/PlanningDataUtils.php';
require_once __DIR__ . '/../utils/RecuDataUtils.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Services/EditionBulletinService.php';
require_once __DIR__ . '/../Services/RedactionCompteRenduService.php';

use CheckMaster\Controllers\BaseController;
use CheckMaster\Core\Messages;
use App\Services\Document\PdfGeneratorService;
use App\Services\Document\DocumentStorageService;
use App\Services\Document\PvCommissionGeneratorService;
use App\Services\Document\PvFinalGeneratorService;
use App\Services\Document\RapportPdfGeneratorService;
use App\Services\Document\RecuGeneratorService;
use App\Support\Database as AppDatabase;
use App\Utils\PlanningDataUtils;
use App\Utils\RecuDataUtils;

/**
 * Contrôleur unifié pour la prévisualisation et le téléchargement des PDF.
 * Route : ?page=docviewer&type={TYPE}&id={ID}&action={preview|download}
 */
class DocViewerController extends BaseController
{
    private DocumentRegistry $registry;
    private DocumentStorageService $documentStorage;
    private ?AppDatabase $appDb = null;
    private ?PdfGeneratorService $pdfGenerator = null;
    private ?PlanningDataUtils $planningDataUtils = null;
    private ?RecuDataUtils $recuDataUtils = null;

    private const ALLOWED_TYPES = [
        'rapport', 'recu', 'memoire', 'pv_commission', 'pv_final',
        'pv_ecrits', 'autorisation_soutenance', 'suivi_encadrement',
        'planning', 'bulletin', 'compte_rendu', 'fiche_inscription',
    ];


    public function __construct($db = null)
    {
        $pdo = $db ?: \Database::getConnection();
        parent::__construct($pdo);
        $this->registry = new DocumentRegistry($this->pdo);
        $this->documentStorage = new DocumentStorageService($this->pdo, dirname(__DIR__, 2));
    }

    public function preview(): void
    {
        $this->serveDocument('inline');
    }

    public function download(): void
    {
        $this->serveDocument('attachment');
    }

    /**
     * Genere un exemplaire de previsualisation a la volee pour le catalogue
     * des modeles PDF. Le PDF est stream directement (disposition: inline)
     * sans etre stocke dans document_genere ni dans le systeme de fichiers.
     *
     * Route : ?page=docviewer&action=catalogue_preview&type={TYPE}&id={ID}
     */
    public function cataloguePreview(): void
    {
        if (!isset($_SESSION['id_GU'])) {
            http_response_code(403);
            echo 'Acces refuse';
            exit;
        }

        $type = trim((string) ($_GET['type'] ?? ''));
        $id   = trim((string) ($_GET['id']   ?? ''));
        $variant = trim((string) ($_GET['variant'] ?? ''));

        if ($type === '' || $id === '') {
            http_response_code(400);
            echo 'Parametres manquants';
            exit;
        }

        // L'aperçu du catalogue est toujours un rendu fictif et éphémère.
        // Il ne doit ni lire un document réel, ni créer de fichier ou de ligne
        // document_genere, ni déclencher de notification.
        $pdfBytes = $this->generateCataloguePreview($type, $id, $variant);
        if ($pdfBytes !== null) {
            $filename = 'apercu_' . preg_replace('/[^a-z0-9_-]/i', '_', $type) . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, no-store');
            header('Content-Length: ' . strlen($pdfBytes));
            echo $pdfBytes;
            exit;
        }

        http_response_code(404);
        echo 'Apercu non disponible pour ce type de document.';
        exit;
    }

    private function serveDocument(string $disposition): void
    {
        $type = trim((string) ($_GET['type'] ?? ''));
        $id = trim((string) ($_GET['id'] ?? ''));

        if ($type === '' || $id === '' || !in_array($type, self::ALLOWED_TYPES, true)) {
            http_response_code(400);
            echo 'Parametres invalides';
            exit;
        }

        if (!$this->registry->canView($type, $id)) {
            $this->logAudit('Consultation document refusee', 'document', 'Erreur');
            http_response_code(403);
            echo 'Acces refuse';
            exit;
        }

        $storedDocument = $this->registry->getStoredDocument($type, $id);
        $filePath = null;

        if ($storedDocument === null) {
            $filePath = $this->registry->resolve($type, $id);
        }

        if ($storedDocument === null && $filePath === null) {
            $generatedPath = $this->generateDocumentIfSupported($type, $id);
            if ($generatedPath !== null) {
                $storedDocument = $this->registry->getStoredDocument($type, $id);
                $filePath = $storedDocument === null ? $generatedPath : null;
            }
        }

        if ($storedDocument === null && ($filePath === null || !is_file($filePath))) {
            $this->logAudit('Document non trouve', 'document', 'Erreur');
            http_response_code(404);
            echo 'Document non trouve';
            exit;
        }

        $actionLabel = $disposition === 'inline'
            ? 'Previsualisation document'
            : 'Telechargement document';
        $this->logAudit($actionLabel, 'document', 'Succès');
        $this->incrementConsultation($type, $id);

        if (is_array($storedDocument)) {
            $this->documentStorage->serve(
                $storedDocument,
                $disposition,
                (int) ($_SESSION['id_utilisateur'] ?? 0),
                (string) ($_SERVER['REMOTE_ADDR'] ?? '')
            );
        }

        $mimeType = $this->detectMimeTypeFromPath((string) $filePath);
        header('Content-Type: ' . $mimeType);
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');

        $filename = basename((string) $filePath);
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');

        $size = filesize((string) $filePath);
        if ($size !== false && $size > 0) {
            header('Content-Length: ' . $size);
        }

        readfile((string) $filePath);
        exit;
    }

    /**
     * Stream direct d'un fichier PDF existant.
     */
    private function streamFile(string $filePath, string $disposition, string $filename): never
    {
        $mimeType = $this->detectMimeTypeFromPath($filePath);
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');
        $size = filesize($filePath);
        if ($size !== false && $size > 0) {
            header('Content-Length: ' . $size);
        }
        readfile($filePath);
        exit;
    }

    private function detectMimeTypeFromPath(string $path): string
    {
        if ($path !== '' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mimeType) && $mimeType !== '') {
                    return $mimeType;
                }
            }
        }

        return 'application/pdf';
    }

    private function logAudit(string $action, string $table, string $status): void
    {
        try {
            $userId = (int) ($_SESSION['id_utilisateur'] ?? 0);
            if ($userId > 0) {
                $audit = new AuditLog($this->pdo);
                $audit->logAction($userId, $action, $table, $status);
            }
        } catch (\Throwable $e) {
            error_log('[DocViewerController] Audit log failed: ' . $e->getMessage());
        }
    }

    private function incrementConsultation(string $type, string $id): void
    {
        $codes = $this->registry->getTypeCodes($type);
        if ($codes === []) {
            return;
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($codes), '?'));
            $sql = "UPDATE document_genere
                    SET nb_consultations = nb_consultations + 1
                    WHERE reference = ?
                       OR (id_source = ? AND type_document IN ($placeholders))
                    LIMIT 1";
            $params = array_merge([$id, $id], $codes);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\Throwable) {
        }
    }

    private function generateDocumentIfSupported(string $type, string $id): ?string
    {
        $userId = (int) ($_SESSION['id_utilisateur'] ?? 0);

        try {
            $result = match ($type) {
                'rapport' => ctype_digit($id)
                    ? $this->getRapportGenerator()->generate((int) $id, $userId)
                    : null,
                'recu' => $this->getRecuGenerator()->generate($id, $userId),
                'pv_commission' => ctype_digit($id)
                    ? $this->getPvCommissionGenerator()->generate((int) $id, $userId)
                    : null,
                'pv_final' => $this->getPvFinalGenerator()->generate($id, $userId),
                default => null,
            };

            if (!is_array($result) || !($result['success'] ?? false)) {
                return null;
            }

            $path = $result['path'] ?? null;
            return is_string($path) && $path !== '' && is_file($path) ? $path : null;
        } catch (\Throwable $e) {
            error_log('[DocViewerController] Generation fallback failed for ' . $type . '/' . $id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generation a la volee d'un apercu PDF sans stockage.
     * Delègue aux vrais générateurs de documents (avec données fictives) pour
     * afficher le rendu exact du modèle. Retourne les octets PDF bruts ou null.
     *
     * @return string|null Contenu PDF binaire
     */
    private function generateCataloguePreview(string $type, string $id, string $variant = ''): ?string
    {
        try {
            $pdfGenerator = $this->getPdfGenerator();

            return match ($type) {
                'rapport'       => $this->getRapportGenerator()->generatePreview(),
                'recu'          => $this->getRecuGenerator()->generatePreview(),
                'pv_commission' => $this->getPvCommissionGenerator()->generatePreview(),
                'pv_final'      => $this->getPvFinalGenerator()->generatePreview(),
                'planning'      => $this->getPlanningGenerator()->generatePreview(),
                'pv_ecrits'     => $this->generateM2S1TemplatePreview($pdfGenerator, 'pv_ecrits'),
                'autorisation_soutenance' => $this->generateM2S1TemplatePreview($pdfGenerator, 'autorisation_soutenance'),
                'suivi_encadrement' => $this->generateM2S1TemplatePreview($pdfGenerator, $variant === 'suivi_directeur' ? 'suivi_directeur' : 'suivi_encadreur'),
                'bulletin'      => $this->generateBulletinPreview($pdfGenerator),
                'compte_rendu'  => $this->generateCompteRenduPreview($pdfGenerator),
                'fiche_inscription' => $this->generateFicheInscriptionPreview($pdfGenerator),
                'memoire'       => $this->generateMemoirePreview($pdfGenerator),
                default         => null,
            };
        } catch (\Throwable $e) {
            error_log('[DocViewerController::cataloguePreview] Rendu fictif impossible pour ' . $type . ': ' . $e->getMessage());
            return null;
        }
    }

    private function generateM2S1TemplatePreview(PdfGeneratorService $pdfGenerator, string $type): string
    {
        $pdf = $pdfGenerator->createDocument('P', 'A4', 'Modèle M2/S1', 'CheckMaster UFRMI');
        $pdf->AddPage();
        $title = match ($type) {
            'pv_ecrits' => 'PROCÈS-VERBAL DES ÉPREUVES ÉCRITES — M2/S1',
            'autorisation_soutenance' => 'DEMANDE D’AUTORISATION DE SOUTENANCE',
            'suivi_directeur' => 'FICHE DE SUIVI — DIRECTEUR DE MÉMOIRE',
            default => 'FICHE DE SUIVI — ENCADREUR PÉDAGOGIQUE',
        };
        $content = match ($type) {
            'pv_ecrits' => '<div class="section"><b>Étudiant :</b> KOUASSI Jean-Baptiste &nbsp;&nbsp; <b>Matricule :</b> CM-2026-00042<br/><b>Statut :</b> Nouveau &nbsp;&nbsp; <b>Année :</b> 2025-2026</div><h3>ÉPREUVES ÉCRITES — M2/S1</h3><table><thead><tr><th>Code UE</th><th>Intitulé</th><th colspan="3">Session normale</th><th colspan="3">Rattrapage</th></tr><tr><th></th><th></th><th>Note</th><th>Crédits</th><th>Total</th><th>Note</th><th>Crédits</th><th>Total</th></tr></thead><tbody><tr><td>UE-M2-01</td><td>Unité d’enseignement exemple</td><td>15,00</td><td>4</td><td>3,00</td><td>—</td><td>4</td><td>—</td></tr><tr><td colspan="2"><b>TOTAL / MOYENNE</b></td><td colspan="3"><b>15,00 / 20</b></td><td colspan="3">—</td></tr></tbody></table><h3>SOUTENANCE DU MÉMOIRE</h3><div class="box">Sujet du mémoire : ................................................................................................<br/><br/>Résultat final : ....................................................................................................</div><div class="signature">Composition et signatures du jury :<br/><br/>................................................................................................</div>',
            'autorisation_soutenance' => '<div class="box"><b>Nom et prénoms :</b> KOUASSI Jean-Baptiste<br/><b>Niveau :</b> Master 2 &nbsp;&nbsp; <b>Option :</b> MIAGE / GI<br/><b>Année académique :</b> 2025-2026 &nbsp;&nbsp; <b>Contact :</b> etudiant@exemple.ci</div><h3>INFORMATIONS DU STAGE ET DU MÉMOIRE</h3><table><tr><td><b>Entreprise d’accueil</b></td><td>Entreprise exemple</td></tr><tr><td><b>Période de stage</b></td><td>01/01/2026 au 30/06/2026</td></tr><tr><td><b>Thème</b></td><td>Thème du mémoire</td></tr><tr><td><b>Maître de stage</b></td><td>Nom du maître de stage</td></tr></table><h3>AVIS ET SIGNATURES</h3><table><tr><th>Maître de stage</th><th>Encadreur pédagogique</th><th>Directeur de mémoire</th></tr><tr><td class="blank"></td><td class="blank"></td><td class="blank"></td></tr></table>',
            'suivi_directeur', 'suivi_encadreur' => '<div class="box"><b>Étudiant :</b> KOUASSI Jean-Baptiste<br/><b>Matricule :</b> CM-2026-00042<br/><b>Année académique :</b> 2025-2026</div><h3>SUIVI — ' . ($type === 'suivi_directeur' ? 'DIRECTEUR DE MÉMOIRE' : 'ENCADREUR PÉDAGOGIQUE') . '</h3><table><thead><tr><th>N°</th><th>Date du rendez-vous</th><th>Signature de l’impétrant</th><th>Signature de l’encadrant</th></tr></thead><tbody><tr><td>1</td><td>................................</td><td class="blank"></td><td class="blank"></td></tr><tr><td>2</td><td>................................</td><td class="blank"></td><td class="blank"></td></tr><tr><td>3</td><td>................................</td><td class="blank"></td><td class="blank"></td></tr></tbody></table><p>Fait à Abidjan, le ................................</p>',
        };
        $html = '<style>body{font-family:dejavusans;font-size:9pt;color:#1f2937}.title{text-align:center;font-size:15pt;font-weight:bold;margin-bottom:14px}.box,.section{border:0.5pt solid #64748b;padding:8px;margin-bottom:10px}.section,h3{background-color:#e8eef5;padding:6px;margin-top:10px}table{width:100%;border-collapse:collapse}th,td{border:0.4pt solid #64748b;padding:5px;text-align:center;vertical-align:middle}th{background-color:#dbe5ef}.blank{height:30px}.signature{margin-top:18px}</style><div class="title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>' . $content;
        $pdfGenerator->writeHtml($pdf, $html);
        return (string) $pdf->Output('', 'S');
    }

    /**
     * Aperçu bulletin de notes (format HTML avec en-tête UFHB + tableau de notes).
     */
    private function generateBulletinPreview(PdfGeneratorService $pdfGenerator): string
    {
        $pdf = $pdfGenerator->createDocument('P', 'A4', 'Bulletin de Notes', 'CheckMaster UFRMI');
        $pdf->AddPage();

        $logos = $pdfGenerator->resolveLogoPaths();
        $logoUfhb = $logos['logo_universite'] ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height:50px;width:auto;" />' : '';
        $logoFiliere = $logos['logo_filiere'] ? '<img src="' . htmlspecialchars($logos['logo_filiere']) . '" style="max-height:50px;width:auto;" />' : '';

        $html = <<<HTML
<style>
body { font-family: helvetica; font-size: 10pt; color: #000; }
table { border-collapse: collapse; width: 100%; }
th { background-color: #1e3a5f; color: #fff; font-size: 9pt; padding: 5px; border: 0.5pt solid #1e3a5f; }
td { font-size: 9pt; padding: 5px; border: 0.5pt solid #999; }
.header-table td { border: none; vertical-align: middle; }
.section-title { background-color: #1e3a5f; color: #fff; font-weight: bold; font-size: 10pt; padding: 5px 8px; margin: 8px 0 4px; }
.total-row { background-color: #f0f0f0; font-weight: bold; }
</style>

<table class="header-table" width="100%">
<tr>
    <td width="20%" align="center">{$logoUfhb}</td>
    <td width="60%" align="center">
        <strong style="font-size:12pt;">UNIVERSITE FELIX HOUPHOUET-BOIGNY</strong><br/>
        UFR MATHEMATIQUES ET INFORMATIQUE<br/>
        <strong>BULLETIN DE NOTES</strong>
    </td>
    <td width="20%" align="center">{$logoFiliere}</td>
</tr>
</table>

<br/>
<table width="100%" border="0">
<tr>
    <td width="50%"><strong>Étudiant :</strong> KOUASSI Jean-Baptiste</td>
    <td width="50%"><strong>Matricule :</strong> CM-2026-00042</td>
</tr>
<tr>
    <td><strong>Filière :</strong> MIAGE-GI — Master 2</td>
    <td><strong>Année académique :</strong> 2025-2026</td>
</tr>
</table>

<br/>
<div class="section-title">SEMESTRE 3 — Unités d'Enseignement</div>
<table>
<thead>
<tr>
    <th width="45%">Unité / Matière</th>
    <th width="10%" align="center">Coeff.</th>
    <th width="15%" align="center">Note /20</th>
    <th width="15%" align="center">Moy. Pond.</th>
    <th width="15%" align="center">Résultat</th>
</tr>
</thead>
<tbody>
<tr><td>UE 301 — Systèmes d'Information Avancés</td><td align="center">3</td><td align="center">15.50</td><td align="center">46.50</td><td align="center">Admis</td></tr>
<tr><td>UE 302 — Génie Logiciel et Architecture</td><td align="center">3</td><td align="center">14.00</td><td align="center">42.00</td><td align="center">Admis</td></tr>
<tr><td>UE 303 — Base de Données Réparties</td><td align="center">2</td><td align="center">13.50</td><td align="center">27.00</td><td align="center">Admis</td></tr>
<tr><td>UE 304 — Sécurité Informatique</td><td align="center">2</td><td align="center">16.00</td><td align="center">32.00</td><td align="center">Admis</td></tr>
<tr><td>UE 305 — Management de Projet SI</td><td align="center">2</td><td align="center">15.00</td><td align="center">30.00</td><td align="center">Admis</td></tr>
<tr class="total-row"><td><strong>TOTAL SEMESTRE</strong></td><td align="center"><strong>12</strong></td><td align="center">—</td><td align="center"><strong>177.50</strong></td><td align="center"><strong>Admis</strong></td></tr>
<tr class="total-row"><td colspan="2"><strong>Moyenne générale</strong></td><td colspan="3" align="center"><strong>14,79 / 20 — Mention : Bien</strong></td></tr>
</tbody>
</table>

<br/>
<p style="font-size:8pt; color:#555; text-align:right;">Délivré par le Service Scolarité — Filières Professionnalisées MIAGE-GI — 2025-2026</p>
HTML;

        $pdfGenerator->writeHtml($pdf, $html);
        return (string) $pdf->Output('', 'S');
    }

    /**
     * Aperçu compte rendu de soutenance (structure institutionnelle + avis du jury).
     */
    private function generateCompteRenduPreview(PdfGeneratorService $pdfGenerator): string
    {
        $pdf = $pdfGenerator->createDocument('P', 'A4', 'Compte Rendu de Soutenance', 'CheckMaster UFRMI');
        $pdf->AddPage();

        $logos = $pdfGenerator->resolveLogoPaths();
        $logoUfhb = $logos['logo_universite'] ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height:50px;width:auto;" />' : '';
        $logoFiliere = $logos['logo_filiere'] ? '<img src="' . htmlspecialchars($logos['logo_filiere']) . '" style="max-height:50px;width:auto;" />' : '';

        $html = <<<HTML
<style>
body { font-family: times; font-size: 11pt; color: #000; line-height: 1.5; }
table { border-collapse: collapse; width: 100%; }
.header-table td { border: none; vertical-align: middle; }
.title-box { border: 1.5pt solid #000; padding: 10px; text-align: center; margin: 20px 0; }
.section { margin-top: 16px; }
.section-title { font-weight: bold; font-size: 12pt; text-decoration: underline; }
.field { margin: 8px 0; }
.signature { margin-top: 50px; text-align: right; padding-right: 40px; }
</style>

<table class="header-table" width="100%">
<tr>
    <td width="25%" align="left">{$logoUfhb}</td>
    <td width="50%" align="center" style="font-size:10pt; line-height:1.4;">
        REPUBLIQUE DE COTE D'IVOIRE<br/>
        <strong>UNIVERSITE FELIX HOUPHOUET-BOIGNY</strong><br/>
        UFR MATHEMATIQUES ET INFORMATIQUE
    </td>
    <td width="25%" align="right">{$logoFiliere}</td>
</tr>
</table>

<div class="title-box">
    <strong style="font-size:14pt;">COMPTE RENDU DE SOUTENANCE</strong><br/>
    <span style="font-size:11pt;">FILIÈRES PROFESSIONNALISÉES MIAGE-GI</span><br/>
    <span style="font-size:10pt;">Réf. : CR-2026-00042 | Date : {$date}</span>
</div>

<div class="section">
    <div class="field"><strong>Étudiant :</strong> KOUASSI Jean-Baptiste</div>
    <div class="field"><strong>Matricule :</strong> CM-2026-00042</div>
    <div class="field"><strong>Thème :</strong> Conception et développement d'une plateforme de suivi académique intégrée</div>
    <div class="field"><strong>Date de soutenance :</strong> {$date}</div>
    <div class="field"><strong>Heure :</strong> 08h00 — <strong>Salle :</strong> Amphi A</div>
</div>

<div class="section">
    <div class="section-title">Composition du jury</div>
    <div class="field">Président : Prof. YAPI Gnagne Serge</div>
    <div class="field">Examinateur : Dr. AKA Sylvie</div>
    <div class="field">Directeur de mémoire : Dr. BROU Kofi Emmanuel</div>
    <div class="field">Encadreur pédagogique : M. KONAN Etienne</div>
    <div class="field">Maître de stage : M. ASSI Kouamé</div>
</div>

<div class="section">
    <div class="section-title">Avis du jury</div>
    <p style="text-align:justify;">
        Le jury a examiné le travail présenté par l'impétrant et délibéré. Après l'exposé et les échanges,
        le jury a rendu la décision suivante :
    </p>
    <p style="text-align:center; font-size:14pt; font-weight:bold; border: 1pt solid #000; padding: 8px;">
        ADMIS — Note finale : 15,09/20 — Mention : BIEN
    </p>
</div>

<div class="signature">
    <strong>Le Président du jury</strong><br/><br/>
    ___________________________
</div>
HTML;

        $date = date('d/m/Y');
        $html = str_replace('{$date}', $date, $html);

        $pdfGenerator->writeHtml($pdf, $html);
        return (string) $pdf->Output('', 'S');
    }

    /**
     * Aperçu fiche d'inscription (format officiel UFHB avec état des versements).
     */
    private function generateFicheInscriptionPreview(PdfGeneratorService $pdfGenerator): string
    {
        $pdf = $pdfGenerator->createDocument('P', 'A4', 'Fiche d\'inscription', 'CheckMaster UFRMI');
        $pdf->AddPage();

        $logos = $pdfGenerator->resolveLogoPaths();
        $logoUfhb = $logos['logo_universite'] ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height:50px;width:auto;" />' : '';
        $logoFiliere = $logos['logo_filiere'] ? '<img src="' . htmlspecialchars($logos['logo_filiere']) . '" style="max-height:50px;width:auto;" />' : '';

        $html = <<<HTML
<style>
body { font-family: helvetica; font-size: 10pt; color: #000; }
table { border-collapse: collapse; width: 100%; }
.header-table td { border: none; vertical-align: middle; }
th { background-color: #1e3a5f; color: #fff; font-size: 9pt; padding: 5px 8px; border: 0.5pt solid #1e3a5f; }
td.lbl { font-weight: bold; background-color: #f5f5f5; width: 35%; border: 0.5pt solid #999; padding: 5px 8px; }
td.val { border: 0.5pt solid #999; padding: 5px 8px; }
.title-box { text-align: center; margin: 14px 0; }
</style>

<table class="header-table" width="100%">
<tr>
    <td width="20%" align="center">{$logoUfhb}</td>
    <td width="60%" align="center" style="font-size:10pt;">
        <strong>UNIVERSITE FELIX HOUPHOUET-BOIGNY</strong><br/>
        UFR MATHEMATIQUES ET INFORMATIQUE<br/>
        Filières Professionnalisées MIAGE-GI
    </td>
    <td width="20%" align="center">{$logoFiliere}</td>
</tr>
</table>

<div class="title-box">
    <strong style="font-size:14pt;">FICHE D'INSCRIPTION</strong><br/>
    <span style="font-size:11pt;">Année académique 2025-2026</span>
</div>

<table>
<tr><th colspan="2">INFORMATIONS PERSONNELLES</th></tr>
<tr><td class="lbl">Nom</td><td class="val">KOUASSI</td></tr>
<tr><td class="lbl">Prénom(s)</td><td class="val">Jean-Baptiste</td></tr>
<tr><td class="lbl">Matricule</td><td class="val">CM-2026-00042</td></tr>
<tr><td class="lbl">Date de naissance</td><td class="val">15/03/2000 — ABIDJAN</td></tr>
<tr><td class="lbl">Nationalité</td><td class="val">Ivoirienne</td></tr>
<tr><td class="lbl">Email</td><td class="val">j.kouassi@etud.ufrmi.edu.ci</td></tr>
<tr><td class="lbl">Téléphone</td><td class="val">07 00 00 00 00</td></tr>
</table>

<br/>
<table>
<tr><th colspan="2">INFORMATIONS ACADÉMIQUES</th></tr>
<tr><td class="lbl">Formation</td><td class="val">Master 2 Informatique — Option MIAGE-GI</td></tr>
<tr><td class="lbl">Niveau</td><td class="val">M2 — Semestre 3</td></tr>
<tr><td class="lbl">Année académique</td><td class="val">2025-2026</td></tr>
<tr><td class="lbl">Date d'inscription</td><td class="val">15/09/2025</td></tr>
<tr><td class="lbl">Statut</td><td class="val"><strong>INSCRIT(E)</strong></td></tr>
</table>

<br/>
<table>
<tr><th colspan="4">ÉTAT DES PAIEMENTS</th></tr>
<tr>
    <th>Nature</th><th>Montant dû</th><th>Montant payé</th><th>Reste</th>
</tr>
<tr><td class="val">Droits d'inscription</td><td class="val">50 000 FCFA</td><td class="val">50 000 FCFA</td><td class="val" style="color:green;"><strong>SOLDÉ</strong></td></tr>
<tr><td class="val">Frais de scolarité</td><td class="val">250 000 FCFA</td><td class="val">150 000 FCFA</td><td class="val" style="color:orange;"><strong>100 000 FCFA</strong></td></tr>
</table>

<br/>
<p style="font-size:8pt; color:#555;">Fiche générée par CheckMaster — Filières Professionnalisées MIAGE-GI</p>
HTML;

        $pdfGenerator->writeHtml($pdf, $html);
        return (string) $pdf->Output('', 'S');
    }

    /**
     * Aperçu mémoire (page de titre officielle + résumé).
     */
    private function generateMemoirePreview(PdfGeneratorService $pdfGenerator): string
    {
        $pdf = $pdfGenerator->createDocument('P', 'A4', 'Mémoire de fin de cycle', 'CheckMaster UFRMI');
        $pdf->AddPage();

        $logos = $pdfGenerator->resolveLogoPaths();
        $logoUfhb = $logos['logo_universite'] ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height:70px;width:auto;display:block;margin:0 auto 8px;" />' : '';
        $logoCiv  = $logos['logo_civ'] ?? '';
        $logoCivHtml = ($logoCiv !== '' && is_file($logoCiv))
            ? '<img src="' . htmlspecialchars($logoCiv) . '" style="max-height:60px;width:auto;" />'
            : '';

        $html = <<<HTML
<style>
body { font-family: 'dejavuserif', serif; font-size: 11pt; color: #111827; }
.cover { width: 210mm; min-height: 297mm; padding: 20mm 22mm 18mm; box-sizing: border-box; }
.center { text-align: center; }
.theme-box { border: 2px solid #0f4666; border-radius: 12px; background: #f6fbff; padding: 10mm 9mm; text-align: center; margin: 10mm 0; }
.bordered-cell { border: 1.5px solid #111827; padding: 10mm 8mm; text-align: center; }
</style>

<div class="cover">
<table width="100%" border="0" style="border-collapse:collapse; margin-bottom:8mm;">
    <tr>
        <td width="50%" style="border:none; font-size:10pt; line-height:1.45;">
            MINISTERE DE L'ENSEIGNEMENT SUPERIEUR<br/>ET DE LA RECHERCHE SCIENTIFIQUE
        </td>
        <td width="50%" align="right" style="border:none; font-size:10pt; line-height:1.45;">
            REPUBLIQUE DE COTE D'IVOIRE<br/>UNION - DISCIPLINE - TRAVAIL
        </td>
    </tr>
</table>

<table width="100%" border="0" style="border-collapse:collapse; margin-bottom:12mm;">
    <tr>
        <td width="50%" align="center" style="border:none;">
            {$logoUfhb}
            <div style="font-size:11pt; font-weight:bold; color:#0f4666;">UNIVERSITE FELIX HOUPHOUET BOIGNY</div>
            <div style="font-size:10pt; margin-top:4px;">UFR MATHEMATIQUES ET INFORMATIQUE<br/>FILIERES PROFESSIONNALISEES MIAGE-GI</div>
        </td>
        <td width="50%" align="center" style="border:none;">
            {$logoCivHtml}
            <div style="font-size:11pt; font-weight:bold; margin-top:8px;">MÉMOIRE DE FIN DE CYCLE</div>
            <div style="font-size:10pt;">Pour l'obtention du Diplôme d'Ingénieur</div>
        </td>
    </tr>
</table>

<div class="theme-box">
    <p style="margin:0 0 8px; font-size:11pt; font-weight:bold; color:#0f4666; text-transform:uppercase; letter-spacing:0.04em;">Thème</p>
    <p style="margin:0; font-size:14pt; font-weight:bold; line-height:1.6; text-transform:uppercase;">
        CONCEPTION ET DÉVELOPPEMENT D'UNE PLATEFORME DE SUIVI ACADÉMIQUE INTÉGRÉE
    </p>
</div>

<table width="100%" style="border-collapse:collapse; margin-top:12mm;">
    <tr>
        <td class="bordered-cell" width="50%">
            <p style="margin:0 0 8px; font-size:10.5pt; font-weight:bold;">PRÉSENTÉ PAR</p>
            <p style="margin:0; font-size:12pt; font-weight:bold;">KOUASSI JEAN-BAPTISTE</p>
            <p style="margin:6px 0 0; font-size:10pt;">Matricule : CM-2026-00042</p>
        </td>
        <td class="bordered-cell" width="50%">
            <p style="margin:0 0 8px; font-size:10.5pt; font-weight:bold;">DIRECTEUR DE MÉMOIRE</p>
            <p style="margin:0; font-size:12pt; font-weight:bold;">Dr. BROU KOFI EMMANUEL</p>
        </td>
    </tr>
</table>

<div style="margin-top:14mm; text-align:center; font-size:10pt; color:#475569;">Année académique 2025-2026</div>
</div>
HTML;

        $pdfGenerator->writeHtml($pdf, $html);
        return (string) $pdf->Output('', 'S');
    }

    private function getAppDatabase(): AppDatabase
    {
        if (!$this->appDb instanceof AppDatabase) {
            $this->appDb = new AppDatabase();
        }

        return $this->appDb;
    }

    private function getPdfGenerator(): PdfGeneratorService
    {
        if (!$this->pdfGenerator instanceof PdfGeneratorService) {
            $this->pdfGenerator = new PdfGeneratorService(
                $this->storagePath(),
                $this->logoPath()
            );
        }

        return $this->pdfGenerator;
    }

    private function getPlanningDataUtils(): PlanningDataUtils
    {
        if (!$this->planningDataUtils instanceof PlanningDataUtils) {
            $this->planningDataUtils = new PlanningDataUtils($this->getAppDatabase());
        }

        return $this->planningDataUtils;
    }

    private function getRecuDataUtils(): RecuDataUtils
    {
        if (!$this->recuDataUtils instanceof RecuDataUtils) {
            $this->recuDataUtils = new RecuDataUtils($this->getAppDatabase());
        }

        return $this->recuDataUtils;
    }

    private function getRapportGenerator(): RapportPdfGeneratorService
    {
        return new RapportPdfGeneratorService($this->getPdfGenerator(), $this->getPlanningDataUtils());
    }

    private function getRecuGenerator(): RecuGeneratorService
    {
        return new RecuGeneratorService($this->getPdfGenerator(), $this->getRecuDataUtils(), $this->getAppDatabase());
    }

    private function getPvCommissionGenerator(): PvCommissionGeneratorService
    {
        return new PvCommissionGeneratorService($this->getPdfGenerator(), $this->getPlanningDataUtils(), $this->getAppDatabase());
    }

    private function getPvFinalGenerator(): PvFinalGeneratorService
    {
        return new PvFinalGeneratorService($this->getPdfGenerator(), $this->getPlanningDataUtils());
    }

    private function getPlanningGenerator(): \App\Services\Document\PlanningGeneratorService
    {
        return new \App\Services\Document\PlanningGeneratorService(
            $this->getPdfGenerator(),
            $this->getPlanningDataUtils(),
            $this->getAppDatabase()
        );
    }
}
