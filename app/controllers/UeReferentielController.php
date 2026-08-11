<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Core/Csrf.php';
require_once __DIR__ . '/../Services/UeReferentielService.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Services\UeReferentielService;

final class UeReferentielController
{
    private UeReferentielService $service;

    public function __construct()
    {
        $this->service = new UeReferentielService(Database::getConnection());
    }

    public function index(): void
    {
        if (!canView('gestion_notes_evaluations')) {
            $GLOBALS['messageErreur'] = 'Vous n’avez pas l’autorisation de consulter le référentiel UE.';
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_GET['action'] ?? '') === 'enregistrer_ue') {
            $csrfToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
            if (!Csrf::validate($csrfToken) || !canEdit('gestion_notes_evaluations')) {
                $_SESSION['error'] = 'Action non autorisée ou session expirée.';
                $this->redirect();
            }
            try {
                $result = $this->service->saveVersion($_POST, (int) ($_SESSION['id_utilisateur'] ?? 0));
                $_SESSION['success'] = $result['message'] ?? 'UE enregistrée.';
            } catch (Throwable $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            $this->redirect();
        }

        try {
            $GLOBALS['ueReferentielData'] = [
                'semesters' => $this->service->getSemesters(),
                'ues' => $this->service->getAllVersions(),
            ];
        } catch (Throwable $e) {
            error_log('UeReferentielController::index: ' . $e->getMessage());
            $GLOBALS['messageErreur'] = 'Le référentiel UE n’est pas encore installé. Exécutez la migration SQL prévue.';
            $GLOBALS['ueReferentielData'] = ['semesters' => [], 'ues' => []];
        }
    }

    private function redirect(): never
    {
        header('Location: ?page=gestion_notes_evaluations&tab=ue');
        exit;
    }
}
