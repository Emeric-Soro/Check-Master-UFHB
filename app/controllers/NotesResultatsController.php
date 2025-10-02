<?php


// Récupérer
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/Semestre.php';
require_once __DIR__ . '/../models/Ue.php';
require_once __DIR__ . '/../models/Ecue.php';



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
        // Récupérer l'ID de l'étudiant connecté
        $studentId = $_SESSION['num_etu'];
        // Récupérer les infos de l'étudiant
        $etudiant = $this->etudiantModel->getEtudiantById($studentId);
        $niveau = $this->etudiantModel->getNiveauByEtudiant($studentId);
        $notes = $this->noteModel->getByStudent($studentId);
        // Préparer les variables globales pour la vue
        $GLOBALS['selectedStudent'] = $etudiant;
        $GLOBALS['niveau'] = $niveau;
        $GLOBALS['studentGrades'] = $notes;
        // Générer le HTML de la vue
        ob_start();
        include __DIR__ . '/../../ressources/views/releve_notes.php';
        $html = ob_get_clean();
        // Générer le PDF avec Dompdf
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('releve-notes.pdf', ['Attachment' => true]);
        exit;
    }




    
}