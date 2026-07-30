<?php

require_once __DIR__ . '/../Services/NotesService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Controllers\BaseController;
use CheckMaster\Core\Messages;
use CheckMaster\Services\NotesService;

class NotesController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct(\Database::getConnection());
        $this->service = new NotesService($this->pdo);
    }

    public function index()
    {
        try {
            // Traitement de l'enregistrement des notes
            if (isset($_GET['action']) && $_GET['action'] == 'enregistrer_notes') {
                $this->enregistrerNotes();
                return;
            }

            // Delegate to service
            $data = $this->service->getIndexData($_GET);

            // Populate globals for the view
            $GLOBALS['niveaux'] = $data['niveaux'];
            $GLOBALS['anneesAcademiques'] = $data['anneesAcademiques'];
            $GLOBALS['etudiants'] = $data['etudiants'];
            $GLOBALS['selectedNiveau'] = $data['selectedNiveau'];
            $GLOBALS['selectedAnneeAcad'] = $data['selectedAnneeAcad'];
            $GLOBALS['niveau'] = $data['niveau'];
            $GLOBALS['selectedStudent'] = $data['selectedStudent'];
            $GLOBALS['studentNote'] = $data['studentNote'];
            $GLOBALS['moyenneGenerale'] = $data['moyenneGenerale'];

        } catch (Exception $e) {
            error_log("Erreur dans NotesController::index : " . $e->getMessage());
            $GLOBALS['messageErreur'] = Messages::get('error.generic');
        }
    }

    public function enregistrerNotes()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_enregistrer_notes'])) {
            if (!canCreate('gestion_notes_evaluations') && !canEdit('gestion_notes_evaluations')) {
                $_SESSION['error'] = Messages::get('error.permission_denied');
                $this->redirectToNotes();
                return;
            }
            $studentId = $_GET['student'] ?? ($_POST['student'] ?? ($_POST['student_picker'] ?? null));
            $studentId = is_string($studentId) ? trim($studentId) : $studentId;
            $anneeAcadId = $_POST['id_annee_acad'] ?? null;

            if (!$studentId) {
                $_SESSION['error'] = Messages::get('error.invalid_input');
                $this->redirectToNotes();
                return;
            }

            if (!$anneeAcadId) {
                $_SESSION['error'] = Messages::get('error.invalid_input');
                $this->redirectToNotes();
                return;
            }

            $moyenneM1 = isset($_POST['moyenne_M1']) ? floatval($_POST['moyenne_M1']) : 0;
            $moyenneM2 = isset($_POST['moyenne_M2']) ? floatval($_POST['moyenne_M2']) : 0;

            $result = $this->service->enregistrerNotes(
                $studentId,
                (int) $anneeAcadId,
                $moyenneM1,
                $moyenneM2,
                (int) $_SESSION['id_utilisateur']
            );

            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirectToNotes();
        }
    }

    private function redirectToNotes()
    {
        $redirectUrl = "?page=gestion_notes_evaluations";
        if (!empty($_GET['niveau'])) {
            $redirectUrl .= "&niveau=" . $_GET['niveau'];
        }
        if (!empty($_GET['annee'])) {
            $redirectUrl .= "&annee=" . $_GET['annee'];
        }
        $studentId = $_GET['student'] ?? ($_POST['student'] ?? ($_POST['student_picker'] ?? null));
        if (!empty($studentId)) {
            $redirectUrl .= "&student=" . $studentId;
        }

        header("Location: " . $redirectUrl);
        exit;
    }

    public function getNotesByEtudiant()
    {
        if (!canView('gestion_notes_evaluations')) {
            $this->jsonError(Messages::get('error.permission_denied'), 403);
        }
        if (isset($_GET['student_id'])) {
            $anneeAcadId = isset($_GET['annee_acad_id']) ? (int) $_GET['annee_acad_id'] : null;
            $notes = $this->service->getNotesByEtudiant($_GET['student_id'], $anneeAcadId);
            $this->json($notes);
        }

        $this->json([]);
    }
}
