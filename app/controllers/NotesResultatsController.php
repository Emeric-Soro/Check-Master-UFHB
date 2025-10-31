<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/Semestre.php';
require_once __DIR__ . '/../models/Ue.php';
require_once __DIR__ . '/../models/Ecue.php';
require_once __DIR__ . '/../utils/permissions.php';



class NotesResultatsController {
    private $noteModel;
    private $etudiantModel;
    private $niveauModel;
    private $semestreModel;
    private $ueModel;
    private $ecueModel;
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->noteModel = new Note($this->db);
        $this->etudiantModel = new Etudiant($this->db);
        $this->ueModel = new Ue($this->db);
        $this->niveauModel = new NiveauEtude($this->db);
        $this->ecueModel = new Ecue($this->db);
        $this->semestreModel = new Semestre($this->db);
    }



    public function index() {
        // Récupérer l'ID de l'étudiant connecté depuis la session
        $studentId = $_SESSION['num_etu'];

        // Récupérer les infos de l'étudiant
        $GLOBALS['etudiant'] = $this->etudiantModel->getEtudiantById($studentId);
        // Récupérer les notes détaillées
        $GLOBALS['notes'] = $this->noteModel->getByStudent($studentId);
        // Moyenne générale
        $GLOBALS['moyenneGenerale'] = $this->noteModel->getMoyenneGenerale($studentId)->moyenne_generale ?? null;
        // Nombre d'UE validées
        $GLOBALS['nbUeValide'] = $this->noteModel->getValidUe($studentId)[0]->nb_ue_valide ?? 0;
        // Classement (et total étudiants du niveau)
        $classementObj = $this->noteModel->getClassementStudent($studentId);
        $GLOBALS['classement'] = $classementObj->classement ?? null;
        $GLOBALS['totalEtudiants'] = $classementObj->total ?? 0;
        // Semestres
        $GLOBALS['semestres'] = $this->noteModel->getSemestreByEtudiant($studentId);
        
        
    }

    public function exportPdf() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../utils/DocumentGeneratorService.php';
        
        try {
            // Récupérer l'ID de l'étudiant connecté
            $studentId = $_SESSION['num_etu'];
            
            // Récupérer les infos de l'étudiant
            $etudiant = $this->etudiantModel->getEtudiantById($studentId);
            $niveau = $this->etudiantModel->getNiveauByEtudiant($studentId);
            $notes = $this->noteModel->getByStudent($studentId);
            $moyenneGenerale = $this->noteModel->getMoyenneGenerale($studentId)->moyenne_generale ?? 0;
            $nbUeValide = $this->noteModel->getValidUe($studentId)[0]->nb_ue_valide ?? 0;
            $classementObj = $this->noteModel->getClassementStudent($studentId);
            
            // Préparer les données pour le template
            $templateData = [
                'nom_etu' => $etudiant->nom_etu ?? '',
                'prenom_etu' => $etudiant->prenom_etu ?? '',
                'num_etu' => $studentId,
                'promotion_etu' => $etudiant->promotion_etu ?? '',
                'niveau' => $niveau->lib_niv_etude ?? '',
                'moyenne_generale' => number_format($moyenneGenerale, 2),
                'nb_ue_valide' => $nbUeValide,
                'classement' => $classementObj->classement ?? 0,
                'total_etudiants' => $classementObj->total ?? 0,
            ];
            
            // Préparer les notes pour le tableau répétitif
            $notesArray = [];
            foreach ($notes as $note) {
                $notesArray[] = [
                    'lib_ue' => $note->lib_ue ?? '',
                    'credit' => $note->credit ?? '',
                    'moyenne' => number_format($note->moyenne ?? 0, 2),
                    'resultat' => ($note->moyenne >= 10) ? 'Validé' : 'Non validé'
                ];
            }
            $templateData['notes'] = $notesArray;
            
            // Utiliser DocumentGeneratorService
            $documentService = new DocumentGeneratorService();
            $pdfPath = $documentService->generateFromTemplate('releve_notes', $templateData);
            
            // Envoyer le PDF au navigateur
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="releve-notes.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            
            // Nettoyer le fichier temporaire
            $documentService->cleanupTempFile($pdfPath);
            exit;
            
        } catch (Exception $e) {
            error_log('Erreur exportPdf: ' . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>Erreur lors de la génération du PDF : ' . htmlspecialchars($e->getMessage()) . '</h3>';
            exit;
        }
    }




    
}