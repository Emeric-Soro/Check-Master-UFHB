<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/PersAdmin.php";
require_once __DIR__ . "/../models/Grade.php";
require_once __DIR__ . "/../models/Fonction.php";
require_once __DIR__ . "/../models/Specialite.php";
require_once __DIR__ . "/../models/AuditLog.php";
require_once __DIR__ . '/../utils/permissions.php';

class GestionRhController
{
    private $baseViewPath;
    private $enseignantModel;
    private $persAdminModel;
    private $gradeModel;
    private $fonctionModel;
    private $specialiteModel;
    private $auditLog;

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_rh_content.php';
        $this->enseignantModel = new Enseignant(Database::getConnection());
        $this->persAdminModel = new PersAdmin(Database::getConnection());
        $this->gradeModel = new Grade(Database::getConnection());
        $this->fonctionModel = new Fonction(Database::getConnection());
        $this->specialiteModel = new Specialite(Database::getConnection());
        $this->auditLog = new AuditLog(Database::getConnection());
    }

    public function index()
    {
        $messageErreur = '';
        $messageSuccess = '';
        $enseignant_a_modifier = null;
        $pers_admin_a_modifier = null;

        // Gestion des enseignants
        if (isset($_GET['tab']) && $_GET['tab'] === 'enseignant') {
            // Ajout ou modification d'un enseignant
            if (isset($_POST['btn_add_enseignant']) || isset($_POST['btn_modifier_enseignant'])) {
                $nom = $_POST['nom'] ?? '';
                $prenom = $_POST['prenom'] ?? '';
                $email = $_POST['email'] ?? '';
                $id_grade = $_POST['id_grade'] ?? null;
                $id_specialite = $_POST['id_specialite'] ?? null;
                $id_fonction = $_POST['id_fonction'] ?? null;
                $date_grade = $_POST['date_grade'] ?? null;
                $date_fonction = $_POST['date_fonction'] ?? null;
                $type_enseignant = $_POST['type_enseignant'];

                if (!empty($_POST['id_enseignant'])) {
                    // Modification
                    if ($this->enseignantModel->modifierEnseignant($_POST['id_enseignant'], $nom, $prenom, $email, 
                        $id_grade, $id_specialite, $id_fonction, $date_grade, $date_fonction,$type_enseignant)) {
                        $messageSuccess = "Enseignant modifié avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'enseignant', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de la modification de l'enseignant.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'enseignant', 'Erreur');
                    }
                } else {
                    // Ajout
                    if ($this->enseignantModel->ajouterEnseignant($nom, $prenom, $email, $id_grade, 
                        $id_specialite, $id_fonction, $date_grade, $date_fonction,$type_enseignant)) {
                        $messageSuccess = "Enseignant ajouté avec succès.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'enseignant', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de l'ajout de l'enseignant.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'enseignant', 'Erreur');
                    }
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    if (!$this->enseignantModel->supprimerEnseignant($id)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $messageSuccess = "Enseignants supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'enseignant', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la suppression des enseignants.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'enseignant', 'Erreur');
                }
            }

            // Récupération de l'enseignant à modifier
            $enseignant_a_modifier = null;
            if (isset($_GET['id_enseignant'])) {
                $enseignant_a_modifier = $this->enseignantModel->getEnseignantById($_GET['id_enseignant']);
            }

        }
        // Gestion du personnel administratif
        else if (isset($_GET['tab']) && $_GET['tab'] === 'pers_admin') {
            // Ajout ou modification d'un membre du personnel
            if (isset($_POST['btn_add_pers_admin']) || isset($_POST['btn_modifier_pers_admin'])) {
                $nom = $_POST['nom'] ?? '';
                $prenom = $_POST['prenom'] ?? '';
                $email = $_POST['email'] ?? '';
                $telephone = $_POST['telephone'] ?? '';
                $poste = $_POST['poste'] ?? '';
                $date_embauche = $_POST['date_embauche'] ?? '';

                if (!empty($_POST['id_pers_admin'])) {
                    // Modification
                    if ($this->persAdminModel->modifierPersAdmin($_POST['id_pers_admin'], $nom, $prenom, $email, $telephone, $poste, $date_embauche)) {
                        $messageSuccess = "Personnel administratif modifié avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'pers_admin', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de la modification du personnel administratif.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'pers_admin', 'Erreur');
                    }
                } else {
                    // Ajout
                    if ($this->persAdminModel->ajouterPersAdmin($nom, $prenom, $email, $telephone, $poste, $date_embauche)) {
                        $messageSuccess = "Personnel administratif ajouté avec succès.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'pers_admin', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de l'ajout du personnel administratif.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'pers_admin', 'Erreur');
                    }
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    if (!$this->persAdminModel->supprimerPersAdmin($id)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $messageSuccess = "Personnel administratif supprimé avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'pers_admin', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la suppression du personnel administratif.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'pers_admin', 'Erreur');
                }
            }

            // Récupération du membre à modifier
            if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id_pers_admin'])) {
                $pers_admin_a_modifier = $this->persAdminModel->getPersAdminById($_GET['id_pers_admin']);
            }
        }
       

        // Variables communes pour toutes les vues
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['pers_admin_a_modifier'] = $pers_admin_a_modifier;;
        $GLOBALS['enseignant_a_modifier'] = $enseignant_a_modifier;
        $GLOBALS['listeEnseignants'] = $this->enseignantModel->getAllEnseignants();
        $GLOBALS['listePersAdmin'] = $this->persAdminModel->getAllPersAdmin();
        $GLOBALS['listeGrades'] = $this->gradeModel->getAllGrades();
        $GLOBALS['listeFonctions'] = $this->fonctionModel->getAllFonctions();
        $GLOBALS['listeSpecialites'] = $this->specialiteModel->getAllSpecialites();
    }
}