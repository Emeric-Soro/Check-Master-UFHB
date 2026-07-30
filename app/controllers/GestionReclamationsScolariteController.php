<?php
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Services/GestionReclamationsScolariteService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Services/GestionReclamationsService.php';

use CheckMaster\Services\GestionReclamationsScolariteService;
class GestionReclamationsScolariteController {
    private $service;
    public function __construct() {
        $db = Database::getConnection();
        $reclamationModel = new Reclamation($db);
        $auditLog = new AuditLog($db);
        $this->service = new GestionReclamationsScolariteService($reclamationModel, $auditLog);
    }
    public function index() {
        $data = $this->service->getReclamationsPartitionnees();
        // Passer aux vues
        $GLOBALS['reclamationsEnCours'] = $data['reclamationsEnCours'];
        $GLOBALS['reclamationsTraitees'] = $data['reclamationsTraitees'];
    }
    public function changerStatut() {
        if (isset($_GET['id']) && isset($_POST['nouveau_statut'])) {
            if (!canEdit('gestion_reclamations_scolarite')) {
                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                header('Location: ?page=gestion_reclamations_scolarite');
                exit;
            }
            $id = (int) $_GET['id'];
            $nouveauStatut = $_POST['nouveau_statut'];
            $idUtilisateur = $_SESSION['id_utilisateur'] ?? 0;
            $this->service->changerStatut($id, $nouveauStatut, $idUtilisateur);

            try {
                $reclamationService = new \CheckMaster\Services\GestionReclamationsService(new Reclamation(\Database::getConnection()), new AuditLog(\Database::getConnection()));
                $idReclamation = $id;
                $objet = $_POST['objet'] ?? '';
                $numEtu = $_POST['num_etu'] ?? '';
                $commentaire = $_POST['commentaire'] ?? '';
                $reclamationService->notifierReclamationStatut($idReclamation, $objet, $nouveauStatut, $numEtu, $commentaire);
            } catch (\Throwable $e) {
                error_log('Erreur notif reclamation: ' . $e->getMessage());
            }
        }
        header('Location: ?page=gestion_reclamations_scolarite');
        exit;
    }
}
