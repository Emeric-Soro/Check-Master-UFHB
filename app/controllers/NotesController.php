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
require_once __DIR__ . '/../services/HashIdService.php';



class NotesController {
    private $noteModel;
    private $etudiantModel;
    private $niveauModel;
    private $semestreModel;
    private $ueModel;
    private $ecueModel;
    private $db;
    private $auditLog;
    private $hashids;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->noteModel = new Note($this->db);
        $this->etudiantModel = new Etudiant($this->db);
        $this->ueModel = new Ue($this->db);
        $this->niveauModel = new NiveauEtude($this->db);
        $this->ecueModel = new Ecue($this->db);
        $this->semestreModel = new Semestre($this->db);
        $this->auditLog = new AuditLog($this->db);
        $this->hashids = new \App\Services\HashIdService();
    }

    public function index() {

       if(isset($_GET['action']) && $_GET['action'] == 'enregistrer_notes'){
        $this->enregistrerNotes();
        
       }
        $selectedNiveau = isset($_GET['niveau']) ? (int)$_GET['niveau'] : null;
        $selectedStudent = isset($_GET['student']) ? $_GET['student'] : null;
        $selectedStudent = $selectedStudent ? $this->etudiantModel->getEtudiantById($selectedStudent) : null;

        $GLOBALS['niveaux'] = $this->niveauModel->getAllNiveauxEtudes();
        $GLOBALS['etudiants'] = $selectedNiveau ? $this->etudiantModel->getEtudiantsByNiveau($selectedNiveau) : [];
        $GLOBALS['selectedNiveau'] = $selectedNiveau;
        $GLOBALS['niveau'] = $this->niveauModel->getNiveauEtudeById($selectedNiveau);
        $GLOBALS['selectedStudent'] = $selectedStudent;

        $GLOBALS['listeEtudiants'] = $this->etudiantModel->getAllEtudiants();
        $GLOBALS['niveauxEtude'] = $this->niveauModel->getAllNiveauxEtudes();

        if ($selectedStudent) {
            $GLOBALS['studentGrades'] = $this->noteModel->getByStudent($selectedStudent->num_etu);
            $GLOBALS['studentSemestres'] = $selectedNiveau ? $this->semestreModel->getSemestresByNiveau($selectedNiveau) : [];
            $GLOBALS['studentUes'] = $selectedNiveau ? $this->ueModel->getUesByNiveau($selectedNiveau) : [];
            $GLOBALS['studentEcues'] = $selectedNiveau ? $this->ecueModel->getEcuesByNiveau($selectedNiveau) : [];
        } else {
            $GLOBALS['studentGrades'] = [];
            $GLOBALS['studentSemestres'] = $selectedNiveau ? $this->semestreModel->getSemestresByNiveau($selectedNiveau) : [];
            $GLOBALS['studentUes'] = $selectedNiveau ? $this->ueModel->getUesByNiveau($selectedNiveau) : [];
            $GLOBALS['studentEcues'] = $selectedNiveau ? $this->ecueModel->getEcuesByNiveau($selectedNiveau) : [];
        }

       
    }

    public function enregistrerNotes() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_enregistrer_notes'])) {
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
    public function imprimerReleve($id)
    {
        require_once __DIR__ . '/../utils/DocumentGeneratorService.php';

        $decodedId = $this->hashids->decode($id);
        if (!$decodedId) {
            die("ID invalide");
        }
        $id = $decodedId;
        
        // Récupérer les informations de l'étudiant
        $etudiant = $this->etudiantModel->getEtudiantById($id);
        
        if (!$etudiant) {
            die("Étudiant non trouvé");
        }

        // Récupérer les notes de l'étudiant
        $notesRaw = $this->noteModel->getByStudent($etudiant->num_etu);
        
        // Formater les notes pour la vue
        $notes = [];
        $totalPoints = 0;
        $totalCredits = 0;

        if ($notesRaw) {
            foreach ($notesRaw as $note) {
                // Récupérer les infos de l'UE (supposons que getByStudent retourne ces infos jointes)
                // Si ce n'est pas le cas, il faudrait faire des requêtes supplémentaires
                // Pour l'exemple, on utilise les données disponibles
                $ue = [
                    'code' => $note->code_ue ?? 'UE-' . $note->id_ue,
                    'intitule' => $note->lib_ue ?? 'Unité d\'enseignement ' . $note->id_ue,
                    'credits' => $note->credit_ue ?? 0,
                    'moyenne' => $note->moyenne,
                    'decision' => $note->moyenne >= 10 ? 'Validé' : 'Ajourné'
                ];
                
                $notes[] = $ue;
                
                if (is_numeric($note->moyenne) && is_numeric($note->credit_ue)) {
                    $totalPoints += $note->moyenne * $note->credit_ue;
                    $totalCredits += $note->credit_ue;
                }
            }
        }

        $moyenneGenerale = $totalCredits > 0 ? $totalPoints / $totalCredits : 0;

        $data = [
            'annee_academique' => '2024-2025', // À dynamiser
            'etudiant' => [
                'nom_complet' => $etudiant->nom_etu . ' ' . $etudiant->prenom_etu,
                'matricule' => $etudiant->num_etu,
                'niveau' => $etudiant->promotion_etu, // Ou récupérer le niveau actuel
                'filiere' => 'MIAGE'
            ],
            'notes' => $notes,
            'moyenne_generale' => $moyenneGenerale,
            'total_credits' => $totalCredits,
            'decision_jury' => $moyenneGenerale >= 10 ? 'ADMIS' : 'AJOURNÉ'
        ];

        $generator = new DocumentGeneratorService();
        $generator->generatePdfFromView('ressources/views/pdf/releve_notes.php', $data, 'releve_notes_' . $etudiant->num_etu . '.pdf');
    }
} 