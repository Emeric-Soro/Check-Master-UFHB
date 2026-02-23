<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionRhService.php';

use CheckMaster\Services\GestionRhService;
class GestionRhController
{
    private $baseViewPath;
    /** @var GestionRhService */
    private $service;
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_rh_content.php';
        $this->service = new GestionRhService(Database::getConnection());
    }
    public function index()
    {
        $messageErreur = '';
        $messageSuccess = '';
        $enseignant_a_modifier = null;
        $pers_admin_a_modifier = null;
        if (isset($_GET['tab']) && $_GET['tab'] === 'enseignant') {
            // Ajout ou modification d'un enseignant
            if (isset($_POST['btn_add_enseignant']) || isset($_POST['btn_modifier_enseignant'])) {
                $result = $this->service->saveEnseignant($_POST, $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                $result = $this->service->deleteMultipleEnseignants($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }
            // Récupération de l'enseignant à modifier
            $enseignant_a_modifier = null;
            if (isset($_GET['id_enseignant'])) {
                $enseignant_a_modifier = $this->service->getEnseignantById($_GET['id_enseignant']);
            }

        }
        // Gestion du personnel administratif
        else if (isset($_GET['tab']) && $_GET['tab'] === 'pers_admin') {
            // Ajout ou modification d'un membre du personnel
            if (isset($_POST['btn_add_pers_admin']) || isset($_POST['btn_modifier_pers_admin'])) {
                $result = $this->service->savePersAdmin($_POST, $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                $result = $this->service->deleteMultiplePersAdmin($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }
            // Récupération du membre à modifier
            if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id_pers_admin'])) {
                $pers_admin_a_modifier = $this->service->getPersAdminById($_GET['id_pers_admin']);
            }
        }
       

        // Variables communes pour toutes les vues
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['pers_admin_a_modifier'] = $pers_admin_a_modifier;;
        $GLOBALS['enseignant_a_modifier'] = $enseignant_a_modifier;
        $referenceLists = $this->service->getReferenceLists();
        foreach ($referenceLists as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
}