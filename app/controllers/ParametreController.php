<?php

namespace App\Controllers;

use PDO;
use App\Models\Action;
use App\Models\AnneeAcademique;
use App\Models\Ecue;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\GroupeUtilisateur;
use App\Models\NiveauAccesDonnees;
use App\Models\NiveauApprobation;
use App\Models\Specialite;
use App\Models\StatutJury;
use App\Models\TypeUtilisateur;
use App\Models\Ue;
use App\Models\NiveauEtude;
use App\Models\Semestre;
use App\Models\Traitement;
use App\Models\Entreprise;
use App\Models\Message;
use App\Models\Attribution;
use App\Models\Enseignant;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * ParametreController - Gestion des paramètres généraux de l'application.
 * 
 * @package App\Controllers
 */
class ParametreController
{
    private PDO $pdo;
    private Action $actionModel;
    private AnneeAcademique $anneeAcadModel;
    private Ecue $ecueModel;
    private Fonction $fonctionModel;
    private Grade $gradeModel;
    private GroupeUtilisateur $groupeUtilisateurModel;
    private NiveauAccesDonnees $niveauAccesDonneesModel;
    private NiveauApprobation $niveauApprobationModel;
    private Specialite $specialiteModel;
    private StatutJury $statutJuryModel;
    private TypeUtilisateur $typeUtilisateurModel;
    private Ue $ueModel;
    private NiveauEtude $niveauEtudeModel;
    private Semestre $semestreModel;
    private Traitement $traitementModel;
    private Entreprise $entrepriseModel;
    private Message $messageModel;
    private Attribution $attributionModel;
    private Enseignant $enseignantModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Action $actionModel,
        AnneeAcademique $anneeAcadModel,
        Ecue $ecueModel,
        Fonction $fonctionModel,
        Grade $gradeModel,
        GroupeUtilisateur $groupeUtilisateurModel,
        NiveauAccesDonnees $niveauAccesDonneesModel,
        NiveauApprobation $niveauApprobationModel,
        Specialite $specialiteModel,
        StatutJury $statutJuryModel,
        TypeUtilisateur $typeUtilisateurModel,
        Ue $ueModel,
        NiveauEtude $niveauEtudeModel,
        Semestre $semestreModel,
        Traitement $traitementModel,
        Entreprise $entrepriseModel,
        Message $messageModel,
        Attribution $attributionModel,
        Enseignant $enseignantModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->actionModel = $actionModel;
        $this->anneeAcadModel = $anneeAcadModel;
        $this->ecueModel = $ecueModel;
        $this->fonctionModel = $fonctionModel;
        $this->gradeModel = $gradeModel;
        $this->groupeUtilisateurModel = $groupeUtilisateurModel;
        $this->niveauAccesDonneesModel = $niveauAccesDonneesModel;
        $this->niveauApprobationModel = $niveauApprobationModel;
        $this->specialiteModel = $specialiteModel;
        $this->statutJuryModel = $statutJuryModel;
        $this->typeUtilisateurModel = $typeUtilisateurModel;
        $this->ueModel = $ueModel;
        $this->niveauEtudeModel = $niveauEtudeModel;
        $this->semestreModel = $semestreModel;
        $this->traitementModel = $traitementModel;
        $this->entrepriseModel = $entrepriseModel;
        $this->messageModel = $messageModel;
        $this->attributionModel = $attributionModel;
        $this->enseignantModel = $enseignantModel;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centrale des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        if (!$this->security->can($idGroupe, 'parametres_generaux', $action)) {
            $this->logger->warning("Accès refusé ({$action}) pour " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur parametres_generaux");
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires.";
            return false;
        }
        return true;
    }


    //=============================GESTION ANNEE ACADEMIQUE=============================
    public function gestionAnnees(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $annee_a_modifier = null;

        try {
            // Ajout ou modification
            if (isset($_POST['btn_add_annees_academiques']) || isset($_POST['btn_modifier_annees_academiques'])) {
                $action = isset($_POST['btn_modifier_annees_academiques']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $dateDebut = $this->security->sanitizeInput($_POST['date_debut'] ?? '');
                $dateFin = $this->security->sanitizeInput($_POST['date_fin'] ?? '');
                $annee1 = date("Y", strtotime($dateDebut));
                $annee2 = date("Y", strtotime($dateFin));

                if (($annee1 == $annee2) || ($dateDebut >= $dateFin)) {
                    $GLOBALS['messageErreur'] = "Les dates de début et de fin ne sont pas valides.";
                } else {
                    // Calculer le nouvel ID basé sur les nouvelles dates
                    $nouvel_id = substr($annee2, 0, 1) . substr($annee2, 2, 2) . substr($annee1, 2, 2);

                    if ($this->anneeAcadModel->isAnneeAcademiqueExist($nouvel_id, $dateDebut, $dateFin)) {
                        $GLOBALS['messageErreur'] = "Cette année académique existe déjà.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'annee_academique', 'Erreur: Doublon');
                    } elseif ($this->anneeAcadModel->isAnneeAcademiqueInUse($nouvel_id) && $action === 'create') {
                        $GLOBALS['messageErreur'] = "Cette année académique est déjà utilisée.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'annee_academique', 'Erreur: Utilisée');
                    }

                    if (empty($GLOBALS['messageErreur'])) {
                        if (!empty($_POST['id_annee_acad'])) {
                            // MODIFICATION
                            if ($this->anneeAcadModel->updateAnneeAcademique($nouvel_id, $dateDebut, $dateFin)) {
                                $GLOBALS['messageSuccess'] = "Année académique modifiée avec succès.";
                                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'annee_academique', 'Succès');
                            } else {
                                $GLOBALS['messageErreur'] = "Erreur lors de la mise à jour.";
                                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'annee_academique', 'Erreur');
                            }
                        } else {
                            // AJOUT
                            if ($this->anneeAcadModel->ajouterAnneeAcademique($dateDebut, $dateFin)) {
                                $GLOBALS['messageSuccess'] = "Année académique ajoutée avec succès.";
                                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'annee_academique', 'Succès');
                            } else {
                                $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'annee_academique', 'Erreur');
                            }
                        }
                    }
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && $_POST['submit_delete_multiple'] == '1' && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->anneeAcadModel->deleteAnneeAcademique($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Années académiques supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'annee_academique', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'annee_academique', 'Erreur');
                }
            }

            // Récupération de l'année à modifier
            if (isset($_GET['id_annee_acad'])) {
                $id = $this->security->sanitizeInput($_GET['id_annee_acad']);
                $annee_a_modifier = $this->anneeAcadModel->getAnneeAcademiqueById($id);
            }

            $GLOBALS['annee_a_modifier'] = $annee_a_modifier;
            $GLOBALS['listeAnnees'] = $this->anneeAcadModel->getAllAnneeAcademiques();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionAnnees: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION ANNEE ACADEMIQUE=============================


    //=============================GESTION GRADES=============================
    public function gestionGrade(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $grade_a_modifier = null;

        try {
            // Ajout ou modification
            if (isset($_POST['btn_add_grades']) || isset($_POST['btn_modifier_grades'])) {
                $action = isset($_POST['btn_modifier_grades']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_grade = $this->security->sanitizeInput($_POST['grades'] ?? '');

                if (empty($lib_grade)) {
                    $GLOBALS['messageErreur'] = "Le libellé du grade est requis.";
                } else {
                    if (!empty($_POST['id_grade'])) {
                        // MODIFICATION
                        $id_grade = $this->security->sanitizeInput($_POST['id_grade']);
                        if ($this->gradeModel->updateGrade($id_grade, $lib_grade)) {
                            $GLOBALS['messageSuccess'] = "Grade modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'grade', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'grade', 'Erreur');
                        }
                    } else {
                        // AJOUT
                        if ($this->gradeModel->ajouterGrade($lib_grade)) {
                            $GLOBALS['messageSuccess'] = "Grade ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'grade', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'grade', 'Erreur');
                        }
                    }
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                // Détecter si on est dans l'onglet grades (souvent partagé)
                // Mais ici gestionGrade est une méthode dédiée
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->gradeModel->deleteGrade($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Grades supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'grade', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'grade', 'Erreur');
                }
            }

            // Récupération du grade à modifier
            if (isset($_GET['id_grade'])) {
                $id = $this->security->sanitizeInput($_GET['id_grade']);
                $grade_a_modifier = $this->gradeModel->getGradeById($id);
            }

            $GLOBALS['grade_a_modifier'] = $grade_a_modifier;
            $GLOBALS['listeGrade'] = $this->gradeModel->getAllGrades();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionGrade: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION GRADES=============================




    //=============================GESTION FONCTION UTILISATEUR=============================
    //=============================GESTION FONCTION UTILISATEUR=============================
    public function gestionFonctionUtilisateur(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $groupe_a_modifier = null;
        $type_a_modifier = null;

        try {
            //======PARTIE GROUPE UTILISATEUR======
            if (isset($_GET['tab']) && $_GET['tab'] === 'groupes') {
                if (isset($_POST['submit_add_groupe']) || isset($_POST['btn_modifier_groupe'])) {
                    $action = isset($_POST['btn_modifier_groupe']) ? 'update' : 'create';
                    if (!$this->checkPermission($action)) {
                        return;
                    }

                    $lib_groupe = $this->security->sanitizeInput($_POST['lib_groupe'] ?? '');

                    if (!empty($_POST['id_groupe'])) {
                        $id_groupe = $this->security->sanitizeInput($_POST['id_groupe']);
                        if ($this->groupeUtilisateurModel->updateGroupeUtilisateur($id_groupe, $lib_groupe)) {
                            $GLOBALS['messageSuccess'] = "Groupe utilisateur modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'groupe_utilisateur', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'groupe_utilisateur', 'Erreur');
                        }
                    } else {
                        if ($this->groupeUtilisateurModel->ajouterGroupeUtilisateur($lib_groupe)) {
                            $GLOBALS['messageSuccess'] = "Groupe utilisateur ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'groupe_utilisateur', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'groupe_utilisateur', 'Erreur');
                        }
                    }
                }

                // Suppression multiple
                if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                    if (!$this->checkPermission('delete')) {
                        return;
                    }

                    $success = true;
                    foreach ($_POST['selected_ids'] as $id) {
                        $decodedId = $this->security->sanitizeInput($id);
                        if (!$this->groupeUtilisateurModel->deleteGroupeUtilisateur($decodedId)) {
                            $success = false;
                            break;
                        }
                    }

                    if ($success) {
                        $GLOBALS['messageSuccess'] = "Groupes utilisateurs supprimés avec succès.";
                        $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'groupe_utilisateur', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                        $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'groupe_utilisateur', 'Erreur');
                    }
                }

                // Récupération du groupe à modifier
                if (isset($_GET['id_groupe'])) {
                    $id = $this->security->sanitizeInput($_GET['id_groupe']);
                    $groupe_a_modifier = $this->groupeUtilisateurModel->getGroupeUtilisateurById($id);
                }

                $GLOBALS['groupe_a_modifier'] = $groupe_a_modifier;
                $GLOBALS['listeGroupes'] = $this->groupeUtilisateurModel->getAllGroupeUtilisateur();
            }

            //======PARTIE TYPE UTILISATEUR======
            if (isset($_GET['tab']) && $_GET['tab'] === 'types') {
                if (isset($_POST['submit_add_type']) || isset($_POST['btn_modifier_type'])) {
                    $action = isset($_POST['btn_modifier_type']) ? 'update' : 'create';
                    if (!$this->checkPermission($action)) {
                        return;
                    }

                    $lib_type = $this->security->sanitizeInput($_POST['lib_type_utilisateur'] ?? '');

                    if (!empty($_POST['id_type_utilisateur'])) {
                        $id_type = $this->security->sanitizeInput($_POST['id_type_utilisateur']);
                        if ($this->typeUtilisateurModel->updateTypeUtilisateur($id_type, $lib_type)) {
                            $GLOBALS['messageSuccess'] = "Type utilisateur modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'type_utilisateur', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'type_utilisateur', 'Erreur');
                        }
                    } else {
                        if ($this->typeUtilisateurModel->ajouterTypeUtilisateur($lib_type)) {
                            $GLOBALS['messageSuccess'] = "Type utilisateur ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'type_utilisateur', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'type_utilisateur', 'Erreur');
                        }
                    }
                }

                // Suppression multiple
                if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                    if (!$this->checkPermission('delete')) {
                        return;
                    }

                    $success = true;
                    foreach ($_POST['selected_ids'] as $id) {
                        $decodedId = $this->security->sanitizeInput($id);
                        if (!$this->typeUtilisateurModel->deleteTypeUtilisateur($decodedId)) {
                            $success = false;
                            break;
                        }
                    }

                    if ($success) {
                        $GLOBALS['messageSuccess'] = "Types utilisateurs supprimés avec succès.";
                        $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'type_utilisateur', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                        $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'type_utilisateur', 'Erreur');
                    }
                }

                // Récupération du type à modifier
                if (isset($_GET['id_type'])) {
                    $id = $this->security->sanitizeInput($_GET['id_type']);
                    $type_a_modifier = $this->typeUtilisateurModel->getTypeUtilisateurById($id);
                }

                $GLOBALS['type_a_modifier'] = $type_a_modifier;
                $GLOBALS['listeTypes'] = $this->typeUtilisateurModel->getAllTypeUtilisateur();
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionFonctionUtilisateur: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION FONCTION UTILISATEUR=============================


    //=============================GESTION SPECIALITE=============================
    public function gestionSpecialite(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $specialite_a_modifier = null;

        try {
            // Ajout ou modification
            if (isset($_POST['btn_add_specialite']) || isset($_POST['btn_modifier_specialite'])) {
                $action = isset($_POST['btn_modifier_specialite']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_specialite = $this->security->sanitizeInput($_POST['specialite'] ?? '');

                if (empty($lib_specialite)) {
                    $GLOBALS['messageErreur'] = "Le libellé de la spécialité est requis.";
                } else {
                    if (!empty($_POST['id_specialite'])) {
                        $id_specialite = $this->security->sanitizeInput($_POST['id_specialite']);
                        if ($this->specialiteModel->updateSpecialite($id_specialite, $lib_specialite)) {
                            $GLOBALS['messageSuccess'] = "Spécialité modifiée avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'specialite', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'specialite', 'Erreur');
                        }
                    } else {
                        if ($this->specialiteModel->ajouterSpecialite($lib_specialite)) {
                            $GLOBALS['messageSuccess'] = "Spécialité ajoutée avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'specialite', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'specialite', 'Erreur');
                        }
                    }
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->specialiteModel->deleteSpecialite($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Spécialités supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'specialite', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'specialite', 'Erreur');
                }
            }

            // Récupération de la spécialité à modifier
            if (isset($_GET['id_specialite'])) {
                $id = $this->security->sanitizeInput($_GET['id_specialite']);
                $specialite_a_modifier = $this->specialiteModel->getSpecialiteById($id);
            }

            $GLOBALS['specialite_a_modifier'] = $specialite_a_modifier;
            $GLOBALS['listeSpecialites'] = $this->specialiteModel->getAllSpecialites();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionSpecialite: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION SPECIALITE=============================


    //=============================GESTION NIVEAU ETUDE=============================
    public function gestionNiveauEtude(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $niveau_a_modifier = null;

        try {
            if (isset($_POST['btn_add_niveau']) || isset($_POST['btn_modifier_niveau'])) {
                $action = isset($_POST['btn_modifier_niveau']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_niveau = $this->security->sanitizeInput($_POST['lib_niv_etude'] ?? '');
                $montant_scolarite = $this->security->sanitizeInput($_POST['montant_scolarite'] ?? '0');
                $montant_inscription = $this->security->sanitizeInput($_POST['montant_inscription'] ?? '0');
                $id_enseignant = !empty($_POST['id_enseignant']) ? $this->security->sanitizeInput($_POST['id_enseignant']) : null;

                if (!empty($_POST['id_niv_etude'])) {
                    $id = $this->security->sanitizeInput($_POST['id_niv_etude']);
                    if ($this->niveauEtudeModel->updateNiveauEtude($id, $lib_niveau, $montant_scolarite, $montant_inscription, $id_enseignant)) {
                        $GLOBALS['messageSuccess'] = "Niveau d'étude modifié avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'niveau_etude', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'niveau_etude', 'Erreur');
                    }
                } else {
                    if ($this->niveauEtudeModel->ajouterNiveauEtude($lib_niveau, $montant_scolarite, $montant_inscription, $id_enseignant)) {
                        $GLOBALS['messageSuccess'] = "Niveau d'étude ajouté avec succès.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'niveau_etude', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'niveau_etude', 'Erreur');
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->niveauEtudeModel->deleteNiveauEtude($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Niveaux d'étude supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'niveau_etude', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'niveau_etude', 'Erreur');
                }
            }

            if (isset($_GET['id_niv_etude'])) {
                $id = $this->security->sanitizeInput($_GET['id_niv_etude']);
                $niveau_a_modifier = $this->niveauEtudeModel->getNiveauEtudeById($id);
            }

            $GLOBALS['niveau_a_modifier'] = $niveau_a_modifier;
            $GLOBALS['listeNiveaux'] = $this->niveauEtudeModel->getAllNiveauxEtudes();
            $GLOBALS['listeEnseignants'] = $this->enseignantModel->getAllEnseignants();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionNiveauEtude: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION NIVEAU ETUDE=============================


    //=============================GESTION UE=============================
    public function gestionUe(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $ue_a_modifier = null;

        try {
            if (isset($_POST['btn_add_ue']) || isset($_POST['btn_modifier_ue'])) {
                $action = isset($_POST['btn_modifier_ue']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_ue = $this->security->sanitizeInput($_POST['lib_ue'] ?? '');
                $credit = $this->security->sanitizeInput($_POST['credit'] ?? '0');
                $id_niveau_etude = $this->security->sanitizeInput($_POST['niveau_etude'] ?? '');
                $id_semestre = $this->security->sanitizeInput($_POST['semestre'] ?? '');
                $id_annee = $this->security->sanitizeInput($_POST['annee_academique'] ?? '');
                $id_enseignant = !empty($_POST['professeur_responsable']) ? $this->security->sanitizeInput($_POST['professeur_responsable']) : null;

                if (!empty($_POST['id_ue'])) {
                    $id = $this->security->sanitizeInput($_POST['id_ue']);
                    if ($this->ueModel->updateUe($id, $lib_ue, $id_niveau_etude, $id_semestre, $id_annee, $credit, $id_enseignant)) {
                        $GLOBALS['messageSuccess'] = "UE modifiée avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'ue', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'ue', 'Erreur');
                    }
                } else {
                    if ($this->ueModel->ajouterUe($lib_ue, $id_niveau_etude, $id_semestre, $id_annee, $credit, $id_enseignant)) {
                        $GLOBALS['messageSuccess'] = "UE ajoutée avec succès.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'ue', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'ue', 'Erreur');
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->ueModel->deleteUe($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "UEs supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'ue', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'ue', 'Erreur');
                }
            }

            if (isset($_GET['id_ue'])) {
                $id = $this->security->sanitizeInput($_GET['id_ue']);
                $ue_a_modifier = $this->ueModel->getUeById($id);
            }

            $GLOBALS['ue_a_modifier'] = $ue_a_modifier;
            $GLOBALS['listeUes'] = $this->ueModel->getAllUes();
            $GLOBALS['listeNiveauxEtude'] = $this->niveauEtudeModel->getAllNiveauxEtudes();
            $GLOBALS['listeSemestres'] = $this->semestreModel->getAllSemestres();
            $GLOBALS['listeAnnees'] = $this->anneeAcadModel->getAllAnneeAcademiques();
            $GLOBALS['listeEnseignants'] = $this->enseignantModel->getAllEnseignants();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionUe: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION UE=============================


    //=============================GESTION ECUE=============================
    public function gestionEcue(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $ecue_a_modifier = null;

        try {
            if (isset($_POST['btn_add_ecue']) || isset($_POST['btn_modifier_ecue'])) {
                $action = isset($_POST['btn_modifier_ecue']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $id_ue = $this->security->sanitizeInput($_POST['id_ue'] ?? '');
                $lib_ecue = $this->security->sanitizeInput($_POST['lib_ecue'] ?? '');
                $credit = $this->security->sanitizeInput($_POST['credit'] ?? '0');
                $id_enseignant = !empty($_POST['professeur_responsable']) ? $this->security->sanitizeInput($_POST['professeur_responsable']) : null;

                if (!empty($_POST['id_ecue'])) {
                    $id = $this->security->sanitizeInput($_POST['id_ecue']);
                    if ($this->ecueModel->updateEcue($id, $id_ue, $lib_ecue, $credit, $id_enseignant)) {
                        $GLOBALS['messageSuccess'] = "ECUE modifiée avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'ecue', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'ecue', 'Erreur');
                    }
                } else {
                    if ($this->ecueModel->ajouterEcue($id_ue, $lib_ecue, $credit, $id_enseignant)) {
                        $GLOBALS['messageSuccess'] = "ECUE ajoutée avec succès.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'ecue', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'ecue', 'Erreur');
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->ecueModel->deleteEcue($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "ECUEs supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'ecue', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'ecue', 'Erreur');
                }
            }

            if (isset($_GET['id_ecue'])) {
                $id = $this->security->sanitizeInput($_GET['id_ecue']);
                $ecue_a_modifier = $this->ecueModel->getEcueById($id);
            }

            $GLOBALS['ecue_a_modifier'] = $ecue_a_modifier;
            $GLOBALS['listeEcues'] = $this->ecueModel->getAllEcues();
            $GLOBALS['listeUes'] = $this->ueModel->getAllUes();
            $GLOBALS['listeEnseignants'] = $this->enseignantModel->getAllEnseignants();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionEcue: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION ECUE=============================
    //=============================FIN GESTION ECUE=============================


    //=============================GESTION STATUT JURY=============================
    public function gestionStatutJury(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $statut_a_modifier = null;

        try {
            if (isset($_POST['btn_add_statut_jury']) || isset($_POST['btn_modifier_statut_jury'])) {
                $action = isset($_POST['btn_modifier_statut_jury']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_statut = $this->security->sanitizeInput($_POST['statut_jury'] ?? '');

                if (empty($lib_statut)) {
                    $GLOBALS['messageErreur'] = "Le libellé du statut est requis.";
                } else {
                    if (!empty($_POST['id_statut_jury'])) {
                        $id = $this->security->sanitizeInput($_POST['id_statut_jury']);
                        if ($this->statutJuryModel->updateStatutJury($id, $lib_statut)) {
                            $GLOBALS['messageSuccess'] = "Statut jury modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'statut_jury', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'statut_jury', 'Erreur');
                        }
                    } else {
                        if ($this->statutJuryModel->ajouterStatutJury($lib_statut)) {
                            $GLOBALS['messageSuccess'] = "Statut jury ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'statut_jury', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'statut_jury', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->statutJuryModel->deleteStatutJury($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Statuts jury supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'statut_jury', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'statut_jury', 'Erreur');
                }
            }

            if (isset($_GET['id_statut_jury'])) {
                $id = $this->security->sanitizeInput($_GET['id_statut_jury']);
                $statut_a_modifier = $this->statutJuryModel->getStatutJuryById($id);
            }

            $GLOBALS['statut_a_modifier'] = $statut_a_modifier;
            $GLOBALS['listeStatuts'] = $this->statutJuryModel->getAllStatutsJury();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionStatutJury: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION STATUT JURY=============================


    //=============================GESTION NIVEAU APPROBATION=============================
    public function gestionNiveauApprobation(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $niveau_a_modifier = null;

        try {
            if (isset($_POST['btn_add_niveau_approbation']) || isset($_POST['btn_modifier_niveau_approbation'])) {
                $action = isset($_POST['btn_modifier_niveau_approbation']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_niveau = $this->security->sanitizeInput($_POST['niveaux_approbation'] ?? '');

                if (empty($lib_niveau)) {
                    $GLOBALS['messageErreur'] = "Le libellé est requis.";
                } else {
                    if (!empty($_POST['id_approb'])) {
                        $id = $this->security->sanitizeInput($_POST['id_approb']);
                        if ($this->niveauApprobationModel->updateNiveauApprobation($id, $lib_niveau)) {
                            $GLOBALS['messageSuccess'] = "Niveau d'approbation modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'niveau_approbation', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'niveau_approbation', 'Erreur');
                        }
                    } else {
                        if ($this->niveauApprobationModel->ajouterNiveauApprobation($lib_niveau)) {
                            $GLOBALS['messageSuccess'] = "Niveau d'approbation ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'niveau_approbation', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'niveau_approbation', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->niveauApprobationModel->deleteNiveauApprobation($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Niveaux d'approbation supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'niveau_approbation', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'niveau_approbation', 'Erreur');
                }
            }

            if (isset($_GET['id_approb'])) {
                $id = $this->security->sanitizeInput($_GET['id_approb']);
                $niveau_a_modifier = $this->niveauApprobationModel->getNiveauApprobationById($id);
            }

            $GLOBALS['niveau_a_modifier'] = $niveau_a_modifier;
            $GLOBALS['listeNiveaux'] = $this->niveauApprobationModel->getAllNiveauxApprobation();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionNiveauApprobation: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION NIVEAU APPROBATION=============================


    //=============================GESTION SEMESTRES=============================
    public function gestionSemestre(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $semestre_a_modifier = null;

        try {
            if (isset($_POST['btn_add_semestre']) || isset($_POST['btn_modifier_semestre'])) {
                $action = isset($_POST['btn_modifier_semestre']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_semestre = $this->security->sanitizeInput($_POST['lib_semestre'] ?? '');
                $id_niv_etude = $this->security->sanitizeInput($_POST['niveau_etude'] ?? '');

                if (empty($lib_semestre)) {
                    $GLOBALS['messageErreur'] = "Le libellé du semestre est requis.";
                } else {
                    if (!empty($_POST['id_semestre'])) {
                        $id = $this->security->sanitizeInput($_POST['id_semestre']);
                        if ($this->semestreModel->updateSemestre($id, $lib_semestre, $id_niv_etude)) {
                            $GLOBALS['messageSuccess'] = "Semestre modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'semestre', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'semestre', 'Erreur');
                        }
                    } else {
                        if ($this->semestreModel->ajouterSemestre($lib_semestre, $id_niv_etude)) {
                            $GLOBALS['messageSuccess'] = "Semestre ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'semestre', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'semestre', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->semestreModel->deleteSemestre($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Semestres supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'semestre', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'semestre', 'Erreur');
                }
            }

            if (isset($_GET['id_semestre'])) {
                $id = $this->security->sanitizeInput($_GET['id_semestre']);
                $semestre_a_modifier = $this->semestreModel->getSemestreById($id);
            }

            $GLOBALS['semestre_a_modifier'] = $semestre_a_modifier;
            $GLOBALS['listeSemestres'] = $this->semestreModel->getAllSemestres();
            $GLOBALS['listeNiveauxEtude'] = $this->niveauEtudeModel->getAllNiveauxEtudes();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionSemestre: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION SEMESTRES=============================


    //=============================GESTION NIVEAU ACCES DONNEES=============================
    public function gestionNiveauAccesDonnees(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $niveau_a_modifier = null;

        try {
            if (isset($_POST['btn_add_niveau']) || isset($_POST['btn_modifier_niveau_acces'])) {
                $action = isset($_POST['btn_modifier_niveau_acces']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_niveau = $this->security->sanitizeInput($_POST['lib_niveau_acces_donnees'] ?? '');

                if (empty($lib_niveau)) {
                    $GLOBALS['messageErreur'] = "Le libellé est requis.";
                } else {
                    if (!empty($_POST['id_niveau_acces_donnees'])) {
                        $id = $this->security->sanitizeInput($_POST['id_niveau_acces_donnees']);
                        if ($this->niveauAccesDonneesModel->updateNiveauAccesDonnees($id, $lib_niveau)) {
                            $GLOBALS['messageSuccess'] = "Niveau d'accès modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'niveau_acces_donnees', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'niveau_acces_donnees', 'Erreur');
                        }
                    } else {
                        if ($this->niveauAccesDonneesModel->ajouterNiveauAccesDonnees($lib_niveau)) {
                            $GLOBALS['messageSuccess'] = "Niveau d'accès ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'niveau_acces_donnees', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'niveau_acces_donnees', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->niveauAccesDonneesModel->deleteNiveauAccesDonnees($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Niveaux d'accès supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'niveau_acces_donnees', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'niveau_acces_donnees', 'Erreur');
                }
            }

            if (isset($_GET['id_niveau'])) {
                $id = $this->security->sanitizeInput($_GET['id_niveau']);
                $niveau_a_modifier = $this->niveauAccesDonneesModel->getNiveauAccesDonneesById($id);
            }

            $GLOBALS['niveau_a_modifier'] = $niveau_a_modifier;
            $GLOBALS['listeNiveaux'] = $this->niveauAccesDonneesModel->getAllNiveauxAccesDonnees();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionNiveauAccesDonnees: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION NIVEAU ACCES DONNEES=============================


    //=============================GESTION TRAITEMENT=============================
    public function gestionTraitement(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $traitement_a_modifier = null;

        try {
            if (isset($_POST['btn_add_traitement']) || isset($_POST['btn_modifier_traitement'])) {
                $action = isset($_POST['btn_modifier_traitement']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_traitement = $this->security->sanitizeInput($_POST['lib_traitement'] ?? '');
                $label_traitement = $this->security->sanitizeInput($_POST['label_traitement'] ?? '');
                $icone_traitement = $this->security->sanitizeInput($_POST['icone_traitement'] ?? '');
                $ordre_traitement = $this->security->sanitizeInput($_POST['ordre_traitement'] ?? '0');

                if (!empty($_POST['id_traitement'])) {
                    $id = $this->security->sanitizeInput($_POST['id_traitement']);
                    if ($this->traitementModel->updateTraitement($id, $lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement)) {
                        $GLOBALS['messageSuccess'] = "Traitement modifié avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'traitement', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'traitement', 'Erreur');
                    }
                } else {
                    if ($this->traitementModel->addTraitement($lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement)) {
                        $GLOBALS['messageSuccess'] = "Traitement ajouté avec succès.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'traitement', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'traitement', 'Erreur');
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->traitementModel->deleteTraitement($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Traitements supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'traitement', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'traitement', 'Erreur');
                }
            }

            if (isset($_GET['id_traitement'])) {
                $id = $this->security->sanitizeInput($_GET['id_traitement']);
                $traitement_a_modifier = $this->traitementModel->getTraitementById($id);
            }

            $GLOBALS['traitement_a_modifier'] = $traitement_a_modifier;
            $GLOBALS['listeTraitements'] = $this->traitementModel->getAllTraitements();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionTraitement: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION TRAITEMENT============================


    //=============================GESTION ENTREPRISE=============================
    public function gestionEntreprise(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $entreprise_a_modifier = null;

        try {
            if (isset($_POST['btn_add_entreprise']) || isset($_POST['btn_modifier_entreprise'])) {
                $action = isset($_POST['btn_modifier_entreprise']) ? 'update' : 'create';
                if (!$this->checkPermission($action)) {
                    return;
                }

                $lib_entreprise = $this->security->sanitizeInput($_POST['lib_entreprise'] ?? '');

                if (empty($lib_entreprise)) {
                    $GLOBALS['messageErreur'] = "Le nom de l'entreprise est requis.";
                } else {
                    if (!empty($_POST['id_entreprise'])) {
                        $id = $this->security->sanitizeInput($_POST['id_entreprise']);
                        if ($this->entrepriseModel->updateEntreprise($id, $lib_entreprise)) {
                            $GLOBALS['messageSuccess'] = "Entreprise modifiée avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'entreprise', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'entreprise', 'Erreur');
                        }
                    } else {
                        if ($this->entrepriseModel->ajouterEntreprise($lib_entreprise)) {
                            $GLOBALS['messageSuccess'] = "Entreprise ajoutée avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'entreprise', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'entreprise', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->entrepriseModel->deleteEntreprise($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Entreprises supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'entreprise', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'entreprise', 'Erreur');
                }
            }

            if (isset($_GET['id_entreprise'])) {
                $id = $this->security->sanitizeInput($_GET['id_entreprise']);
                $entreprise_a_modifier = $this->entrepriseModel->getEntrepriseById($id);
            }

            $GLOBALS['entreprise_a_modifier'] = $entreprise_a_modifier;
            $GLOBALS['listeEntreprises'] = $this->entrepriseModel->getAllEntreprises();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionEntreprise: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION ENTREPRISE============================

    //=============================GESTION ACTION=============================
    public function gestionAction(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $action_a_modifier = null;

        try {
            if (isset($_POST['btn_add_action']) || isset($_POST['btn_modifier_action'])) {
                $action_type = isset($_POST['btn_modifier_action']) ? 'update' : 'create';
                if (!$this->checkPermission($action_type)) {
                    return;
                }

                $lib_action = $this->security->sanitizeInput($_POST['action'] ?? '');

                if (empty($lib_action)) {
                    $GLOBALS['messageErreur'] = "Le libellé de l'action est requis.";
                } else {
                    if (!empty($_POST['id_action'])) {
                        $id = $this->security->sanitizeInput($_POST['id_action']);
                        if ($this->actionModel->updateAction($id, $lib_action)) {
                            $GLOBALS['messageSuccess'] = "Action modifiée avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'action', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'action', 'Erreur');
                        }
                    } else {
                        if ($this->actionModel->ajouterAction($lib_action)) {
                            $GLOBALS['messageSuccess'] = "Action ajoutée avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'action', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'action', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->actionModel->deleteAction($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Actions supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'action', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'action', 'Erreur');
                }
            }

            if (isset($_GET['id_action'])) {
                $id = $this->security->sanitizeInput($_GET['id_action']);
                $action_a_modifier = $this->actionModel->getActionById($id);
            }

            $GLOBALS['action_a_modifier'] = $action_a_modifier;
            $GLOBALS['listeActions'] = $this->actionModel->getAllAction();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionAction: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION ACTION============================

    //=============================GESTION FONCTION=============================
    public function gestionFonction(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $fonction_a_modifier = null;

        try {
            if (isset($_POST['btn_add_fonction']) || isset($_POST['btn_modifier_fonction'])) {
                $action_type = isset($_POST['btn_modifier_fonction']) ? 'update' : 'create';
                if (!$this->checkPermission($action_type)) {
                    return;
                }

                $lib_fonction = $this->security->sanitizeInput($_POST['lib_fonction'] ?? '');

                if (empty($lib_fonction)) {
                    $GLOBALS['messageErreur'] = "Le libellé de la fonction est requis.";
                } else {
                    if (!empty($_POST['id_fonction'])) {
                        $id = $this->security->sanitizeInput($_POST['id_fonction']);
                        if ($this->fonctionModel->updateFonction($id, $lib_fonction)) {
                            $GLOBALS['messageSuccess'] = "Fonction modifiée avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'fonction', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'fonction', 'Erreur');
                        }
                    } else {
                        if ($this->fonctionModel->ajouterFonction($lib_fonction)) {
                            $GLOBALS['messageSuccess'] = "Fonction ajoutée avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'fonction', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'fonction', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->fonctionModel->deleteFonction($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Fonctions supprimées avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'fonction', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'fonction', 'Erreur');
                }
            }

            if (isset($_GET['id_fonction'])) {
                $id = $this->security->sanitizeInput($_GET['id_fonction']);
                $fonction_a_modifier = $this->fonctionModel->getFonctionById($id);
            }

            $GLOBALS['fonction_a_modifier'] = $fonction_a_modifier;
            $GLOBALS['listeFonctions'] = $this->fonctionModel->getAllFonction();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionFonction: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION FONCTION============================

    //=============================GESTION MESSAGERIE=============================
    public function gestionMessagerie(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $message_a_modifier = null;

        try {
            if (isset($_POST['btn_add_message']) || isset($_POST['btn_modifier_message'])) {
                $action_type = isset($_POST['btn_modifier_message']) ? 'update' : 'create';
                if (!$this->checkPermission($action_type)) {
                    return;
                }

                $contenu_message = $this->security->sanitizeInput($_POST['contenu_message'] ?? '');
                $lib_message = $this->security->sanitizeInput($_POST['lib_message'] ?? '');
                $type_message = $this->security->sanitizeInput($_POST['type_message'] ?? '');

                if (empty($lib_message) || empty($contenu_message)) {
                    $GLOBALS['messageErreur'] = "Le libellé et le contenu sont requis.";
                } else {
                    if (!empty($_POST['id_message'])) {
                        $id = $this->security->sanitizeInput($_POST['id_message']);
                        if ($this->messageModel->updateMessage($id, $contenu_message, $lib_message, $type_message)) {
                            $GLOBALS['messageSuccess'] = "Message modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'message', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de la modification.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'message', 'Erreur');
                        }
                    } else {
                        if ($this->messageModel->ajouterMessage($contenu_message, $lib_message, $type_message)) {
                            $GLOBALS['messageSuccess'] = "Message ajouté avec succès.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'message', 'Succès');
                        } else {
                            $GLOBALS['messageErreur'] = "Erreur lors de l'ajout.";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'message', 'Erreur');
                        }
                    }
                }
            }

            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!$this->checkPermission('delete')) {
                    return;
                }

                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    $decodedId = $this->security->sanitizeInput($id);
                    if (!$this->messageModel->deleteMessage($decodedId)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $GLOBALS['messageSuccess'] = "Messages supprimés avec succès.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'message', 'Succès');
                } else {
                    $GLOBALS['messageErreur'] = "Erreur lors de la suppression.";
                    $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'message', 'Erreur');
                }
            }

            if (isset($_GET['id_message'])) {
                $id = $this->security->sanitizeInput($_GET['id_message']);
                $message_a_modifier = $this->messageModel->getMessageById($id);
            }

            $GLOBALS['message_a_modifier'] = $message_a_modifier;
            $GLOBALS['listeMessages'] = $this->messageModel->getAllMessages();

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionMessagerie: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }
    //=============================FIN GESTION MESSAGERIE============================


    //============================GESTION ATTRIBUTION==================================
    public function gestionAttribution(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Récupérer tous les groupes et traitements
            $listeGroupes = $this->groupeUtilisateurModel->getAllGroupeUtilisateur();
            $listeTraitements = $this->traitementModel->getAllTraitements();

            // Récupérer le groupe sélectionné
            $selectedGroupeId = isset($_GET['groupe']) ? $this->security->sanitizeInput($_GET['groupe']) : null;
            $selectedGroupe = null;
            $attributionsGroupe = [];

            if ($selectedGroupeId) {
                $selectedGroupe = $this->groupeUtilisateurModel->getGroupeUtilisateurById($selectedGroupeId);
                // Récupérer les traitements attribués au groupe
                $attributionsGroupe = $this->attributionModel->getTraitementsByGroupe($selectedGroupeId);
            }

            // Traiter le formulaire de soumission
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_GU'])) {
                if (!$this->checkPermission('update')) {
                    return;
                }
                $this->handleAttributionSubmit($_POST);
            }

            // Préparer les données pour la vue
            $attributionsMap = [];
            foreach ($listeGroupes as $groupe) {
                $attributionsMap[$groupe->id_GU] = $this->attributionModel->getTraitementsByGroupe($groupe->id_GU);
            }

            $GLOBALS['attributionsMap'] = $attributionsMap;
            $GLOBALS['listeGroupes'] = $listeGroupes;
            $GLOBALS['listeTraitements'] = $listeTraitements;
            $GLOBALS['selectedGroupe'] = $selectedGroupe;
            $GLOBALS['attributionsGroupe'] = $attributionsGroupe;

        } catch (Exception $e) {
            $this->logger->error("Erreur gestionAttribution: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur système est survenue.";
        }
    }

    private function handleAttributionSubmit(array $postData): void
    {
        $groupeId = $this->security->sanitizeInput($postData['id_GU'] ?? '');
        $selectedTraitements = isset($postData['traitements']) ? $postData['traitements'] : [];

        try {
            // Supprimer toutes les attributions existantes pour ce groupe
            $this->attributionModel->deleteAttribution($groupeId);

            // Ajouter les nouvelles attributions
            foreach ($selectedTraitements as $traitementId) {
                $tId = $this->security->sanitizeInput($traitementId);
                $this->attributionModel->ajouterAttribution($groupeId, $tId);
            }

            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'attribution', 'Succès');
            // Rediriger avec un message de succès
            header('Location: ?page=parametres_generaux&action=gestion_attribution&groupe=' . $groupeId . '&success=1');
            exit;
        } catch (Exception $e) {
            $this->logger->error("Erreur handleAttributionSubmit: " . $e->getMessage());
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'attribution', 'Erreur');
            // Rediriger avec un message d'erreur
            header('Location: ?page=parametres_generaux&action=gestion_attribution&groupe=' . $groupeId . '&error=1');
            exit;
        }
    }

    //==============================GESTION SALLES==============================
    public function gestionSalles(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }
        // Logic remains in the view
    }
    //==============================FIN GESTION SALLES==============================
}


//==============================FIN GESTION ATTRIBUTION==============================



/*Ce fichier est le contrôleur principal pour la gestion des paramètres généraux de l'application.
    Il gère les actions liées aux entités telles que les années académiques, les grades, les ECUE, etc.
    Chaque méthode correspond à une fonctionnalité spécifique et interagit avec le modèle approprié. */