<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/AuditLog.php';

class NotesController
{
    private $noteModel;
    private $etudiantModel;
    private $niveauModel;
    private $anneeAcadModel;
    private $db;
    private $auditLog;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->noteModel = new Note($this->db);
        $this->etudiantModel = new Etudiant($this->db);
        $this->niveauModel = new NiveauEtude($this->db);
        $this->anneeAcadModel = new AnneeAcademique($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    public function index()
    {
        try {
            // Traitement de l'enregistrement des notes
            if (isset($_GET['action']) && $_GET['action'] == 'enregistrer_notes') {
                $this->enregistrerNotes();
                return;
            }

            // Récupération des paramètres
            $selectedNiveau = isset($_GET['niveau']) ? (int) $_GET['niveau'] : null;
            $selectedAnneeAcad = isset($_GET['annee']) ? (int) $_GET['annee'] : null;
            $selectedStudent = isset($_GET['student']) ? $_GET['student'] : null;

            // Charger les données de l'étudiant si sélectionné
            $studentObj = $selectedStudent ? $this->etudiantModel->getEtudiantById($selectedStudent) : null;

            // Préparer les données pour la vue
            $GLOBALS['niveaux'] = $this->niveauModel->getAllNiveauxEtudes();
            $GLOBALS['anneesAcademiques'] = $this->anneeAcadModel->getAllAnneeAcademiques();
            $GLOBALS['etudiants'] = $selectedNiveau ? $this->etudiantModel->getEtudiantsByNiveau($selectedNiveau) : [];
            $GLOBALS['selectedNiveau'] = $selectedNiveau;
            $GLOBALS['selectedAnneeAcad'] = $selectedAnneeAcad;
            $GLOBALS['niveau'] = $selectedNiveau ? $this->niveauModel->getNiveauEtudeById($selectedNiveau) : null;
            $GLOBALS['selectedStudent'] = $studentObj;

            // Charger les notes de l'étudiant si sélectionné
            if ($studentObj && $selectedAnneeAcad) {
                $GLOBALS['studentNote'] = $this->noteModel->getByStudentAndYear($studentObj->num_carte_etud, $selectedAnneeAcad);
            } else if ($studentObj) {
                $GLOBALS['studentNote'] = $this->noteModel->getLatestNote($studentObj->num_carte_etud);
            } else {
                $GLOBALS['studentNote'] = null;
            }

            // Calculer la moyenne s'il y a des notes
            if (isset($GLOBALS['studentNote']) && $GLOBALS['studentNote']) {
                $note = $GLOBALS['studentNote'];
                $GLOBALS['moyenneGenerale'] = ($note->moyenne_M1 + $note->moyenne_M2) / 2;
            } else {
                $GLOBALS['moyenneGenerale'] = null;
            }

        } catch (Exception $e) {
            error_log("Erreur dans NotesController::index : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des données.";
        }
    }

    public function enregistrerNotes()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_enregistrer_notes'])) {
            $studentId = $_GET['student'] ?? null;
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

            try {
                // Récupérer les moyennes
                $moyenneM1 = isset($_POST['moyenne_M1']) ? floatval($_POST['moyenne_M1']) : 0;
                $moyenneM2 = isset($_POST['moyenne_M2']) ? floatval($_POST['moyenne_M2']) : 0;

                // Validation des notes (entre 0 et 20)
                if ($moyenneM1 < 0 || $moyenneM1 > 20 || $moyenneM2 < 0 || $moyenneM2 > 20) {
                    $_SESSION['error'] = "Les moyennes doivent être comprises entre 0 et 20.";
                    $this->redirectBack();
                    return;
                }

                // Enregistrer les notes
                $result = $this->noteModel->saveNotes($studentId, $moyenneM1, $moyenneM2, $anneeAcadId);

                if ($result) {
                    $_SESSION['success'] = "Les notes ont été enregistrées avec succès.";
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Succès");
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des notes.";
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Erreur");
                }

            } catch (Exception $e) {
                error_log("Erreur lors de l'enregistrement des notes: " . $e->getMessage());
                $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Erreur");
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
        if (!empty($_GET['student'])) {
            $redirectUrl .= "&student=" . $_GET['student'];
        }

        header("Location: " . $redirectUrl);
        exit;
    }

    public function getNotesByEtudiant()
    {
        if (isset($_GET['student_id'])) {
            $anneeAcadId = $_GET['annee_acad_id'] ?? null;
            $notes = $this->noteModel->getByStudent($_GET['student_id'], $anneeAcadId);
            echo json_encode($notes);
            exit;
        }

        echo json_encode([]);
    }
}

