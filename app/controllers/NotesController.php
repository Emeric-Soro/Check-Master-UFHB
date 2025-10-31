<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/Semestre.php';
require_once __DIR__ . '/../models/Ue.php';
require_once __DIR__ . '/../models/Ecue.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';



class NotesController {
    private $noteModel;
    private $etudiantModel;
    private $niveauModel;
    private $semestreModel;
    private $ueModel;
    private $ecueModel;
    private $db;
    private $auditLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->noteModel = new Note($this->db);
        $this->etudiantModel = new Etudiant($this->db);
        $this->ueModel = new Ue($this->db);
        $this->niveauModel = new NiveauEtude($this->db);
        $this->ecueModel = new Ecue($this->db);
        $this->semestreModel = new Semestre($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    public function index() {

       if(isset($_GET['action']) && $_GET['action'] == 'enregistrer_notes'){
        $this->enregistrerNotes();
        
       }
        $selectedNiveau = isset($_GET['niveau']) ? (int)$_GET['niveau'] : null;
        $selectedStudent = isset($_GET['student']) ? $_GET['student'] : null;
        $selectedStudent = $selectedStudent ? $this->etudiantModel->getEtudiantById($selectedStudent) : null;

        // Préparer les données pour la vue
        $data = [
            'niveaux' => $this->niveauModel->getAllNiveauxEtudes(),
            'etudiants' => $selectedNiveau ? $this->etudiantModel->getEtudiantsByNiveau($selectedNiveau) : [],
            'selectedNiveau' => $selectedNiveau,
            'niveau' => $this->niveauModel->getNiveauEtudeById($selectedNiveau),
            'selectedStudent' => $selectedStudent,
            'listeEtudiants' => $this->etudiantModel->getAllEtudiants(),
            'niveauxEtude' => $this->niveauModel->getAllNiveauxEtudes()
        ];

        if ($selectedStudent) {
            $data['studentGrades'] = $this->noteModel->getByStudent($selectedStudent->num_etu);
            $data['studentSemestres'] = $selectedNiveau ? $this->semestreModel->getSemestresByNiveau($selectedNiveau) : [];
            $data['studentUes'] = $selectedNiveau ? $this->ueModel->getUesByNiveau($selectedNiveau) : [];
            $data['studentEcues'] = $selectedNiveau ? $this->ecueModel->getEcuesByNiveau($selectedNiveau) : [];
        } else {
            $data['studentGrades'] = [];
            $data['studentSemestres'] = $selectedNiveau ? $this->semestreModel->getSemestresByNiveau($selectedNiveau) : [];
            $data['studentUes'] = $selectedNiveau ? $this->ueModel->getUesByNiveau($selectedNiveau) : [];
            $data['studentEcues'] = $selectedNiveau ? $this->ecueModel->getEcuesByNiveau($selectedNiveau) : [];
        }

        return $data;
    }

    public function enregistrerNotes() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_enregistrer_notes'])) {
            // Vérifier les permissions CREATE et UPDATE pour l'enregistrement des notes
            if (!hasPermission('gestion_notes', 'CREATE') && !hasPermission('gestion_notes', 'UPDATE')) {
                $_SESSION['error'] = "Vous n'avez pas la permission d'enregistrer des notes.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'notes', 'Erreur - Permission refusée');
                return;
            }
            
            $success = true;
            $studentId = $_GET['student'] ?? null;
            
            if (!$studentId) {
                $_SESSION['error'] = "ID étudiant manquant";
                return;
            }

            try {
                // Traiter les notes des UE
                if (isset($_POST['notes']) && is_array($_POST['notes'])) {
            
                    foreach ($_POST['notes'] as $ueId => $note) {

                        // On ne traite que les notes qui ont été saisies
                        if ($note !== '') {
                            $commentaire = $_POST['commentaires'][$ueId] ?? null;
                       
                            // Vérifier si la note existe déjà
                            $existingNote = $this->noteModel->getByStudent($studentId);
                            $noteExists = false;

                            if( $existingNote != null){
                            
                            foreach ($existingNote as $existing) {
                                if ($existing->id_ue == $ueId) {
                                    $noteExists = true;
                                    break;
                                }
                            }
                        }
                            if ($noteExists) {
                            
                                $result = $this->noteModel->updateNote($studentId, $ueId, $note, $commentaire,null);
                            } else {
                              
                                $result = $this->noteModel->createNote($studentId, $ueId, $note, $commentaire,null);
                            }
                            
                            if (!$result) {
                                $success = false;
                            }
                        }
                    }
                }

                // Traiter les notes des ECUE
                if (isset($_POST['notes_ecue']) && is_array($_POST['notes_ecue'])) {
                  
                    foreach ($_POST['notes_ecue'] as $ecueId => $note) {
                        // On ne traite que les notes qui ont été saisies
                        if ($note !== '') {
                            $commentaire = $_POST['commentaires_ecue'][$ecueId] ?? null;
                            
                            // Vérifier si la note existe déjà
                            $existingNote = $this->noteModel->getByStudent($studentId);
                            $noteExists = false;

                            if ($existingNote != null) {

                                foreach ($existingNote as $existing) {
                                    if ($existing->id_ecue == $ecueId) {
                                        $noteExists = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ($noteExists) {
   
                                $result = $this->noteModel->updateNote($studentId, $ueId, $note, $commentaire, $ecueId);
                            } else {
              
                                $result = $this->noteModel->createNote($studentId, $ueId, $note, $commentaire, $ecueId);
                            }
                            
                        
                            if (!$result) {
                                
                                $success = false;
                            }
                        }
                    }
                }

                
                
                if ($success) {
                    $_SESSION['success'] = "Les notes ont été enregistrées avec succès.";
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Succès  ");
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des notes.";
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Erreur");
                }
                
                // Rediriger vers la même page avec les mêmes paramètres
                $redirectUrl = "?page=gestion_notes_evaluations";
                if (!empty($_GET['niveau'])) {
                    $redirectUrl .= "&niveau=" . $_GET['niveau'];
                }
                if (!empty($_GET['student'])) {
                    $redirectUrl .= "&student=" . $_GET['student'];
                }
                
                
            } catch (Exception $e) {
               
                $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des notes: " . $e->getMessage();
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Erreur");
                
                // Rediriger vers la même page avec les mêmes paramètres
                $redirectUrl = "?page=gestion_notes_evaluations";
                if (!empty($_GET['niveau'])) {
                    $redirectUrl .= "&niveau=" . $_GET['niveau'];
                }
                if (!empty($_GET['student'])) {
                    $redirectUrl .= "&student=" . $_GET['student'];
                }

                header("Location: " . $redirectUrl);
                
                
            }
        }
    }

    public function getNotesByEtudiant() {
        if (isset($_GET['student_id'])) {
            $notes = $this->noteModel->getByStudent($_GET['student_id']);
            echo json_encode($notes);
            exit;
        }
        
        echo json_encode([]);
    }
} 