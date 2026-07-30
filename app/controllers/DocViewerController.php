<?php
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';
require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
require_once __DIR__ . '/../Services/Document/RapportPdfGeneratorService.php';
require_once __DIR__ . '/../Services/Document/RecuGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PvCommissionGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PvFinalGeneratorService.php';
require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../utils/PlanningDataUtils.php';
require_once __DIR__ . '/../utils/RecuDataUtils.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../models/AuditLog.php';

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
}
