<?php

namespace App\Controllers;

use PDO;
use App\Models\AnneeAcademique;
use App\Models\Note;
use App\Models\Etudiant;
use App\Models\NiveauEtude;
use App\Models\Semestre;
use App\Models\Ue;
use App\Models\Ecue;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * NotesController - Saisie des notes par les enseignants ou l'admin
 * 
 * Ce contrôleur gère la gestion des notes :
 * - Affichage et filtrage des étudiants
 * - Saisie et modification des notes UE/ECUE
 * - Consultation des notes par étudiant
 * 
 * @package App\Controllers
 */
class NotesController
{
    private PDO $db;
    private Note $noteModel;
    private Etudiant $etudiantModel;
    private NiveauEtude $niveauModel;
    private Semestre $semestreModel;
    private Ue $ueModel;
    private Ecue $ecueModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $db,
        Note $noteModel,
        Etudiant $etudiantModel,
        NiveauEtude $niveauModel,
        Semestre $semestreModel,
        Ue $ueModel,
        Ecue $ecueModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->noteModel = $noteModel;
        $this->etudiantModel = $etudiantModel;
        $this->niveauModel = $niveauModel;
        $this->semestreModel = $semestreModel;
        $this->ueModel = $ueModel;
        $this->ecueModel = $ecueModel;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'gestion_notes_evaluations', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur gestion_notes_evaluations"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action : Afficher la page de gestion des notes (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Traiter l'enregistrement des notes si demandé
            if (isset($_GET['action']) && $_GET['action'] == 'enregistrer_notes') {
                $this->enregistrerNotes();
            }
            
            $selectedNiveau = isset($_GET['niveau']) ? (int)$_GET['niveau'] : null;
            $selectedStudent = isset($_GET['student']) ? $this->security->sanitizeInput($_GET['student']) : null;
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
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans NotesController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des données.";
        }
    }

    /**
     * Action : Enregistrer les notes (UPDATE)
     */
    public function enregistrerNotes(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn_enregistrer_notes'])) {
            return;
        }

        try {
            $success = true;
            $studentId = $this->security->sanitizeInput($_GET['student'] ?? null);
            
            if (!$studentId) {
                $_SESSION['error'] = "ID étudiant manquant";
                $this->logger->warning("Tentative d'enregistrement de notes sans ID étudiant");
                return;
            }

            // Traiter les notes des UE
            if (isset($_POST['notes']) && is_array($_POST['notes'])) {
                foreach ($_POST['notes'] as $ueId => $note) {
                    if ($note !== '') {
                        $ueId = (int)$ueId;
                        $note = $this->security->sanitizeInput($note);
                        $commentaire = isset($_POST['commentaires'][$ueId]) ? 
                            $this->security->sanitizeInput($_POST['commentaires'][$ueId]) : null;
                        
                        // Vérifier si la note existe déjà
                        $existingNote = $this->noteModel->getByStudent($studentId);
                        $noteExists = false;

                        if ($existingNote != null) {
                            foreach ($existingNote as $existing) {
                                if ($existing->id_ue == $ueId) {
                                    $noteExists = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($noteExists) {
                            $result = $this->noteModel->updateNote($studentId, $ueId, $note, $commentaire, null);
                        } else {
                            $result = $this->noteModel->createNote($studentId, $ueId, $note, $commentaire, null);
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
                    if ($note !== '') {
                        $ecueId = (int)$ecueId;
                        $note = $this->security->sanitizeInput($note);
                        $commentaire = isset($_POST['commentaires_ecue'][$ecueId]) ? 
                            $this->security->sanitizeInput($_POST['commentaires_ecue'][$ecueId]) : null;
                        
                        // Vérifier si la note existe déjà
                        $existingNote = $this->noteModel->getByStudent($studentId);
                        $noteExists = false;
                        $ueId = null;

                        if ($existingNote != null) {
                            foreach ($existingNote as $existing) {
                                if ($existing->id_ecue == $ecueId) {
                                    $noteExists = true;
                                    $ueId = $existing->id_ue;
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
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Succès");
                $this->logger->info("Notes enregistrées pour l'étudiant: " . $studentId);
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des notes.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Erreur");
            }
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'enregistrement des notes: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des notes.";
            $this->auditLog->logCreation($_SESSION['id_utilisateur'], "notes", "Erreur");
        }
    }

    /**
     * Action : Récupérer les notes par étudiant (READ - AJAX)
     */
    public function getNotesByEtudiant(): void
    {
        if (!$this->checkPermission('read')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }

        try {
            if (isset($_GET['student_id'])) {
                $studentId = $this->security->sanitizeInput($_GET['student_id']);
                $notes = $this->noteModel->getByStudent($studentId);
                header('Content-Type: application/json');
                echo json_encode($notes);
                exit;
            }
            
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans getNotesByEtudiant: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erreur lors de la récupération des notes']);
            exit;
        }
    }
} 