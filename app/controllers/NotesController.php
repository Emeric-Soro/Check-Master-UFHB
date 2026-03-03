<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/NotesService.php';

use CheckMaster\Services\NotesService;

class NotesController
{
    private $service;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new NotesService($db);
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
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des données.";
        }
    }

    public function enregistrerNotes()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_enregistrer_notes'])) {
            $studentId = $_GET['student'] ?? ($_POST['student'] ?? ($_POST['student_picker'] ?? null));
            $studentId = is_string($studentId) ? trim($studentId) : $studentId;
            $anneeAcadId = $_POST['id_annee_acad'] ?? null;

            if (!$studentId) {
                $_SESSION['error'] = "ID étudiant manquant";
                $this->redirectBack();
                return;
            }

            if (!$anneeAcadId) {
                $_SESSION['error'] = "Année académique manquante";
                $this->redirectBack();
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

            $this->redirectBack();
        }
    }

    private function redirectBack()
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
        if (isset($_GET['student_id'])) {
            $anneeAcadId = isset($_GET['annee_acad_id']) ? (int) $_GET['annee_acad_id'] : null;
            $notes = $this->service->getNotesByEtudiant($_GET['student_id'], $anneeAcadId);
            echo json_encode($notes);
            exit;
        }

        echo json_encode([]);
    }
}
