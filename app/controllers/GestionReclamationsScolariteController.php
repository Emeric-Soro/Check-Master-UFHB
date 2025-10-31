<?php
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';
class GestionReclamationsScolariteController {
    private $reclamationModel;
    private $auditLog;
    private $db;    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->reclamationModel = new Reclamation();
        $this->auditLog = new AuditLog($this->db);
    }

    public function index() {
        // Récupérer toutes les réclamations avec infos étudiant
        $allReclamations = $this->reclamationModel->getAllReclamationsWithEtudiant();

        // Séparer en cours/attente et traitées/clôturées
        $reclamationsEnCours = [];
        $reclamationsTraitees = [];
        foreach ($allReclamations as $rec) {
            if ($rec->statut_reclamation === 'En attente' || $rec->statut_reclamation === 'En cours') {
                $reclamationsEnCours[] = $rec;
            } else {
                $reclamationsTraitees[] = $rec;
            }
        }

        // Passer aux vues
        $GLOBALS['reclamationsEnCours'] = $reclamationsEnCours;
        $GLOBALS['reclamationsTraitees'] = $reclamationsTraitees;

    }

    public function changerStatut() {
        if (isset($_GET['id']) && isset($_POST['nouveau_statut'])) {
            $id = (int) $_GET['id'];
            $nouveauStatut = $_POST['nouveau_statut'];
            if ($this->reclamationModel->updateStatut($id, $nouveauStatut)) {
                
                // Log de l'audit
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'reclamations', 'Succès');
            } else {
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'reclamations', 'Erreur');
            }
        }
        header('Location: ?page=gestion_reclamations_scolarite');
        exit;
    }
}