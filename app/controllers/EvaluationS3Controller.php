<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Core/Csrf.php';
require_once __DIR__ . '/../Services/UeReferentielService.php';
require_once __DIR__ . '/../Services/EvaluationS3Service.php';
require_once __DIR__ . '/../Services/EvaluationS3ImportService.php';
require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PvEpreuvesEcritesGeneratorService.php';
require_once __DIR__ . '/../Services/Document/SoutenanceFormPdfService.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Services\EvaluationS3ImportService;
use CheckMaster\Services\EvaluationS3Service;
use CheckMaster\Services\UeReferentielService;
use App\Services\Document\PdfGeneratorService;
use App\Services\Document\PvEpreuvesEcritesGeneratorService;
use App\Services\Document\SoutenanceFormPdfService;

final class EvaluationS3Controller
{
    private EvaluationS3Service $service;
    private EvaluationS3ImportService $importService;
    private UeReferentielService $ueService;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->ueService = new UeReferentielService($pdo);
        $this->service = new EvaluationS3Service($pdo, $this->ueService);
        $this->importService = new EvaluationS3ImportService($pdo, $this->service, $this->ueService);
    }

    public function index(): void
    {
        if (!canView('gestion_notes_evaluations')) {
            $GLOBALS['messageErreur'] = 'Vous n’avez pas l’autorisation de consulter les évaluations M2/S1.';
            return;
        }

        $action = (string) ($_GET['action'] ?? '');
        if ($action === 'generer_pv_ecrits') {
            $this->generatePvEcrits();
        }
        if (in_array($action, ['generer_autorisation', 'generer_suivi_directeur', 'generer_suivi_encadreur'], true)) {
            $this->generateSoutenanceForm($action);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $csrfToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
            if (!Csrf::validate($csrfToken)) {
                $this->redirectWithError('Session expirée. Veuillez recharger la page.');
            }
            if (!canEdit('gestion_notes_evaluations')) {
                $this->redirectWithError('Vous n’avez pas l’autorisation de modifier les évaluations.');
            }
            if ($action === 'enregistrer_evaluations_s3') {
                $this->saveEvaluations();
            }
            if ($action === 'import_evaluations_s3') {
                $this->previewImport();
            }
            if ($action === 'confirmer_import_evaluations_s3') {
                $this->confirmImport();
            }
        }

        try {
            $data = $this->service->getIndexData($_GET);
            $batchId = isset($_GET['batch']) && is_numeric($_GET['batch']) ? (int) $_GET['batch'] : 0;
            if ($batchId > 0) {
                try {
                    $data['import_batch'] = $this->importService->getBatch($batchId);
                } catch (Throwable $e) {
                    $data['import_error'] = $e->getMessage();
                }
            }
            $GLOBALS['evaluationS3Data'] = $data;
        } catch (Throwable $e) {
            error_log('EvaluationS3Controller::index: ' . $e->getMessage());
            $GLOBALS['messageErreur'] = 'Le référentiel M2/S1 n’est pas encore installé. Exécutez la migration SQL prévue.';
            $GLOBALS['evaluationS3Data'] = [
                'years' => [], 'year_id' => 0, 'students' => [], 'student' => null,
                'ues' => [], 'grid' => ['rows' => [], 'expected_credits' => 0],
                'semester_aliases' => [], 'import_batch' => null,
            ];
        }
    }

    private function saveEvaluations(): never
    {
        $student = trim((string) ($_POST['student'] ?? ''));
        $yearId = (int) ($_POST['id_annee_acad'] ?? 0);
        $payload = is_array($_POST['evaluations'] ?? null) ? $_POST['evaluations'] : [];
        try {
            $result = $this->service->saveEvaluations($student, $yearId, $payload, (int) ($_SESSION['id_utilisateur'] ?? 0));
            $_SESSION['success'] = $result['message'] ?? 'Évaluations enregistrées.';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirectToPage($student, $yearId);
    }

    private function previewImport(): never
    {
        try {
            $batch = $this->importService->previewUploadedFile(
                is_array($_FILES['evaluation_file'] ?? null) ? $_FILES['evaluation_file'] : [],
                (int) ($_SESSION['id_utilisateur'] ?? 0)
            );
            $_SESSION['success'] = 'Prévisualisation créée : ' . (int) ($batch['lignes_valides'] ?? 0) . ' ligne(s) valide(s).';
            $url = '?page=gestion_notes_evaluations&tab=evaluations_s3&batch=' . (int) ($batch['id_batch'] ?? 0);
            header('Location: ' . $url);
            exit;
        } catch (Throwable $e) {
            $this->redirectWithError($e->getMessage());
        }
    }

    private function confirmImport(): never
    {
        try {
            $batchId = (int) ($_POST['batch_id'] ?? 0);
            $result = $this->importService->confirm($batchId, (int) ($_SESSION['id_utilisateur'] ?? 0));
            $_SESSION['success'] = $result['message'] ?? 'Import confirmé.';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: ?page=gestion_notes_evaluations&tab=evaluations_s3');
        exit;
    }

    private function generatePvEcrits(): never
    {
        try {
            $student = trim((string) ($_GET['student'] ?? ''));
            $yearId = (int) ($_GET['annee'] ?? 0);
            $root = dirname(__DIR__, 2);
            $pdfGenerator = new PdfGeneratorService(
                $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documents',
                $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'logo_ufhb.png'
            );
            $generator = new PvEpreuvesEcritesGeneratorService($pdfGenerator, $this->service, Database::getConnection());
            $result = $generator->generate($student, $yearId, (int) ($_SESSION['id_utilisateur'] ?? 0));
            if (!($result['success'] ?? false) || empty($result['path']) || !is_file($result['path'])) {
                throw new RuntimeException((string) ($result['error'] ?? 'Impossible de générer le PV.'));
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename((string) $result['path']) . '"');
            header('Content-Length: ' . (string) filesize((string) $result['path']));
            readfile((string) $result['path']);
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToPage((string) ($_GET['student'] ?? ''), (int) ($_GET['annee'] ?? 0));
        }
    }

    private function generateSoutenanceForm(string $action): never
    {
        try {
            $type = match ($action) {
                'generer_autorisation' => 'autorisation',
                'generer_suivi_directeur' => 'suivi_directeur',
                default => 'suivi_encadreur',
            };
            $root = dirname(__DIR__, 2);
            $pdfGenerator = new PdfGeneratorService(
                $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documents',
                $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'logo_ufhb.png'
            );
            $generator = new SoutenanceFormPdfService($pdfGenerator, Database::getConnection());
            $result = $generator->generate(
                $type,
                (string) ($_GET['student'] ?? ''),
                (int) ($_GET['annee'] ?? 0),
                (int) ($_SESSION['id_utilisateur'] ?? 0)
            );
            if (empty($result['path']) || !is_file($result['path'])) {
                throw new RuntimeException('Impossible de générer le formulaire.');
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename((string) $result['path']) . '"');
            header('Content-Length: ' . (string) filesize((string) $result['path']));
            readfile((string) $result['path']);
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToPage((string) ($_GET['student'] ?? ''), (int) ($_GET['annee'] ?? 0));
        }
    }

    private function redirectToPage(string $student, int $yearId): never
    {
        $url = '?page=gestion_notes_evaluations&tab=evaluations_s3';
        if ($yearId > 0) {
            $url .= '&annee=' . urlencode((string) $yearId);
        }
        if ($student !== '') {
            $url .= '&student=' . urlencode($student);
        }
        header('Location: ' . $url);
        exit;
    }

    private function redirectWithError(string $message): never
    {
        $_SESSION['error'] = $message;
        header('Location: ?page=gestion_notes_evaluations&tab=evaluations_s3');
        exit;
    }
}
