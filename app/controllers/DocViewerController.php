<?php
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';
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

use App\Services\Document\PdfGeneratorService;
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
class DocViewerController
{
    private $db;
    private DocumentRegistry $registry;
    private ?AppDatabase $appDb = null;
    private ?PdfGeneratorService $pdfGenerator = null;
    private ?PlanningDataUtils $planningDataUtils = null;
    private ?RecuDataUtils $recuDataUtils = null;

    private const ALLOWED_TYPES = [
        'rapport', 'recu', 'pv_commission', 'pv_final',
        'planning', 'bulletin', 'compte_rendu',
    ];

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->registry = new DocumentRegistry($this->db);
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

        $filePath = $this->registry->resolve($type, $id);
        if ($filePath === null) {
            $filePath = $this->generateDocumentIfSupported($type, $id);
        }

        if ($filePath === null || !is_file($filePath)) {
            $this->logAudit('Document non trouve', 'document', 'Erreur');
            http_response_code(404);
            echo 'Document non trouve';
            exit;
        }

        header('Content-Type: application/pdf');
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');

        $filename = basename($filePath);
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');

        $size = filesize($filePath);
        if ($size !== false && $size > 0) {
            header('Content-Length: ' . $size);
        }

        $actionLabel = $disposition === 'inline'
            ? 'Previsualisation document'
            : 'Telechargement document';
        $this->logAudit($actionLabel, 'document', 'Succes');
        $this->incrementConsultation($type, $id);

        readfile($filePath);
        exit;
    }

    private function logAudit(string $action, string $table, string $status): void
    {
        try {
            $userId = (int) ($_SESSION['id_utilisateur'] ?? 0);
            if ($userId > 0) {
                $audit = new AuditLog($this->db);
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
            $stmt = $this->db->prepare($sql);
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
                __DIR__ . '/../../storage/documents',
                __DIR__ . '/../../public/image/logo_ufhb.png'
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
