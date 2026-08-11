<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Csrf.php';
require_once __DIR__ . '/../Services/UeReferentielService.php';
require_once __DIR__ . '/../Services/EvaluationS3Service.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Services\EvaluationS3Service;
use CheckMaster\Services\UeReferentielService;

final class GestionCandidaturesEvaluationsController
{
    private EvaluationS3Service $service;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $ueService = new UeReferentielService($pdo);
        $this->service = new EvaluationS3Service($pdo, $ueService);
    }

    public function index(): void
    {
        if (!canView('gestion_candidatures_soutenance')) {
            $GLOBALS['messageErreur'] = 'Vous n’avez pas l’autorisation de consulter les évaluations des candidatures.';
            $GLOBALS['candidatureEvaluationsData'] = $this->emptyData();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->save();
        }

        try {
            $GLOBALS['candidatureEvaluationsData'] = $this->service->getIndexData($_GET);
        } catch (Throwable $e) {
            error_log('GestionCandidaturesEvaluationsController::index: ' . $e->getMessage());
            $GLOBALS['messageErreur'] = 'La grille des évaluations M2/S1 n’est pas disponible.';
            $GLOBALS['candidatureEvaluationsData'] = $this->emptyData();
        }
    }

    private function save(): never
    {
        if (!Csrf::validate(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
            $this->redirectWithError('Session expirée. Veuillez recharger la page.');
        }
        if (!canEdit('gestion_candidatures_soutenance')) {
            $this->redirectWithError('Vous n’avez pas l’autorisation de modifier les évaluations.');
        }

        try {
            $student = trim((string) ($_POST['student'] ?? ''));
            $yearId = (int) ($_POST['id_annee_acad'] ?? 0);
            $payload = is_array($_POST['evaluations'] ?? null) ? $_POST['evaluations'] : [];
            $result = $this->service->saveEvaluations($student, $yearId, $payload, (int) ($_SESSION['id_utilisateur'] ?? 0));
            $_SESSION['success'] = (string) ($result['message'] ?? 'Les évaluations ont été enregistrées.');
            $this->redirect($student, $yearId);
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect((string) ($_POST['student'] ?? ''), (int) ($_POST['id_annee_acad'] ?? 0));
        }
    }

    private function redirect(string $student = '', int $yearId = 0): never
    {
        $url = '?page=gestion_candidatures&action=evaluations_m2_s1';
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
        $this->redirect();
    }

    /** @return array<string, mixed> */
    private function emptyData(): array
    {
        return [
            'years' => [],
            'year_id' => 0,
            'students' => [],
            'student' => null,
            'ues' => [],
            'grid' => ['rows' => [], 'expected_credits' => 0, 'credits_are_compliant' => false],
            'semester_code' => UeReferentielService::SEMESTER_CODE,
            'semester_aliases' => [],
        ];
    }
}
