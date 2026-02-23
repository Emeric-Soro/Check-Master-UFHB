<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Action.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Ecue.php';
require_once __DIR__ . '/../models/Fonction.php';
require_once __DIR__ . '/../models/Grade.php';
require_once __DIR__ . '/../models/GroupeUtilisateur.php';
require_once __DIR__ . '/../models/NiveauAccesDonnees.php';
require_once __DIR__ . '/../models/NiveauApprobation.php';
require_once __DIR__ . '/../models/Specialite.php';
require_once __DIR__ . '/../models/StatutJury.php';
require_once __DIR__ . '/../models/TypeUtilisateur.php';
require_once __DIR__ . '/../models/Ue.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/Semestre.php';
require_once __DIR__ . '/../models/Traitement.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Attribution.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Action;
use AnneeAcademique;
use Ecue;
use Fonction;
use Grade;
use GroupeUtilisateur;
use NiveauAccesDonnees;
use NiveauApprobation;
use Specialite;
use StatutJury;
use TypeUtilisateur;
use Ue;
use NiveauEtude;
use Semestre;
use Traitement;
use Entreprise;
use Message;
use Attribution;
use Enseignant;
use AuditLog;
use PDO;
use Database;
use Exception;
use Throwable;

class ParametreService
{
    private $action;
    private $anneeAcademique;
    private $ecue;
    private $fonction;
    private $grade;
    private $groupeUtilisateur;
    private $niveauAccesDonnees;
    private $niveauApprobation;
    private $niveauEtude;
    private $specialite;
    private $statutJury;
    private $typeUtilisateur;
    private $ue;
    private $semestre;
    private $entreprise;
    private $traitement;
    private $message;
    private $attribution;
    private $enseignant;
    private $auditLog;

    public function __construct($db)
    {
        $this->anneeAcademique = new AnneeAcademique($db);
        $this->action = new Action($db);
        $this->fonction = new Fonction($db);
        $this->grade = new Grade($db);
        $this->groupeUtilisateur = new GroupeUtilisateur($db);
        $this->niveauAccesDonnees = new NiveauAccesDonnees($db);
        $this->niveauApprobation = new NiveauApprobation($db);
        $this->typeUtilisateur = new TypeUtilisateur($db);
        $this->ue = new Ue($db);
        $this->ecue = new Ecue($db);
        $this->statutJury = new StatutJury($db);
        $this->specialite = new Specialite($db);
        $this->niveauEtude = new NiveauEtude($db);
        $this->semestre = new Semestre($db);
        $this->traitement = new Traitement($db);
        $this->entreprise = new Entreprise($db);
        $this->message = new Message($db);
        $this->attribution = new Attribution($db);
        $this->enseignant = new Enseignant($db);
        $this->auditLog = new AuditLog($db);
    }


    //=============================GESTION ANNEE ACADEMIQUE=============================
    /**
     * @param array  $post   $_POST data
     * @param array  $get    $_GET data
     * @param string $userId current user id
     * @return array view data
     */
    public function gestionAnnees(array $post, array $get, string $userId): array
    {
        $annee_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        // Ajout ou modification
        if (isset($post['btn_add_annees_academiques']) || isset($post['btn_modifier_annees_academiques'])) {
            $dateDebut = $post['date_debut'];
            $dateFin = $post['date_fin'];
            $annee1 = date("Y", strtotime($dateDebut));
            $annee2 = date("Y", strtotime($dateFin));

            if (($annee1 == $annee2) || ($dateDebut >= $dateFin)) {
                $messageErreur = "Les dates de début et de fin ne sont pas valides.";
            } else {
                $nouvel_id = substr($annee2, 0, 1) . substr($annee2, 2, 2) . substr($annee1, 2, 2);

                if ($this->anneeAcademique->isAnneeAcademiqueExist($nouvel_id, $dateDebut, $dateFin)) {
                    $messageErreur = "Cette année académique existe déjà.";
                    $this->auditLog->logCreation($userId, 'annee_academique', 'Erreur');
                }
                if ($this->anneeAcademique->isAnneeAcademiqueInUse($nouvel_id)) {
                    $messageErreur = "Cette année académique est déjà utilisée.";
                    $this->auditLog->logCreation($userId, 'annee_academique', 'Erreur');
                }

                if (empty($messageErreur)) {
                    if (!empty($post['id_annee_acad'])) {
                        if ($this->anneeAcademique->updateAnneeAcademique($nouvel_id, $dateDebut, $dateFin)) {
                            $messageSuccess = "Année académique modifiée avec succès.";
                            $this->auditLog->logModification($userId, 'annee_academique', 'Succès');
                        } else {
                            $messageErreur = "Erreur lors de la mise à jour de l'année académique.";
                            $this->auditLog->logModification($userId, 'annee_academique', 'Erreur');
                        }
                    } else {
                        if ($this->anneeAcademique->ajouterAnneeAcademique($dateDebut, $dateFin)) {
                            $messageSuccess = "Année académique ajoutée avec succès.";
                            $this->auditLog->logCreation($userId, 'annee_academique', 'Succès');
                        } else {
                            $messageErreur = "Erreur lors de l'ajout de l'année académique.";
                            $this->auditLog->logCreation($userId, 'annee_academique', 'Erreur');
                        }
                    }
                }
            }
        }

        // Suppression multiple
        if (isset($post['submit_delete_multiple']) && $post['submit_delete_multiple'] == '1' && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->anneeAcademique->deleteAnneeAcademique($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Années académiques supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'annee_academique', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des années académiques.";
                $this->auditLog->logSuppression($userId, 'annee_academique', 'Erreur');
            }
        }

        // Récupération de l'année à modifier
        if (isset($get['id_annee_acad'])) {
            $annee_a_modifier = $this->anneeAcademique->getAnneeAcademiqueById($get['id_annee_acad']);
        }

        return [
            'annee_a_modifier' => $annee_a_modifier,
            'listeAnnees' => $this->anneeAcademique->getAllAnneeAcademiques(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION GRADES=============================
    public function gestionGrade(array $post, array $get, string $userId): array
    {
        $grades_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_grades']) || isset($post['btn_modifier_grades'])) {
            $lib_grade = $post['grades'];

            if (!empty($post['id_grade'])) {
                if ($this->grade->updateGrade($post['id_grade'], $lib_grade)) {
                    $messageSuccess = "Grade modifié avec succès.";
                    $this->auditLog->logModification($userId, 'grade', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du grade.";
                    $this->auditLog->logModification($userId, 'grade', 'Erreur');
                }
            } else {
                if ($this->grade->ajouterGrade($lib_grade)) {
                    $messageSuccess = "Grade ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'grade', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du grade.";
                    $this->auditLog->logCreation($userId, 'grade', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->grade->deleteGrade($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Grades supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'grade', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des grades.";
                $this->auditLog->logSuppression($userId, 'grade', 'Erreur');
            }
        }

        if (isset($get['id_grade'])) {
            $grades_a_modifier = $this->grade->getGradeById($get['id_grade']);
        }

        return [
            'grade_a_modifier' => $grades_a_modifier,
            'listeGrade' => $this->grade->getAllGrades(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION FONCTION UTILISATEUR=============================
    public function gestionFonctionUtilisateur(array $post, array $get, string $userId): array
    {
        $groupe_a_modifier = null;
        $type_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';
        $result = [];

        //======PARTIE GROUPE UTILISATEUR======
        if (isset($get['tab']) && $get['tab'] === 'groupes') {
            if (isset($post['submit_add_groupe']) || isset($post['btn_modifier_groupe'])) {
                $lib_groupe = $post['lib_groupe'];
                $idTypeUtilisateur = isset($post['id_type_utilisateur']) && $post['id_type_utilisateur'] !== '' ? (int) $post['id_type_utilisateur'] : null;

                if (!empty($post['id_groupe'])) {
                    if ($this->groupeUtilisateur->updateGroupeUtilisateur($post['id_groupe'], $lib_groupe, $idTypeUtilisateur)) {
                        $messageSuccess = "Groupe utilisateur modifié avec succès.";
                        $this->auditLog->logModification($userId, 'groupe_utilisateur', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de la modification du groupe utilisateur.";
                        $this->auditLog->logModification($userId, 'groupe_utilisateur', 'Erreur');
                    }
                } else {
                    if ($this->groupeUtilisateur->ajouterGroupeUtilisateur($lib_groupe, $idTypeUtilisateur)) {
                        $messageSuccess = "Groupe utilisateur ajouté avec succès.";
                        $this->auditLog->logCreation($userId, 'groupe_utilisateur', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de l'ajout du groupe utilisateur.";
                        $this->auditLog->logCreation($userId, 'groupe_utilisateur', 'Erreur');
                    }
                }
            }

            if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
                $success = true;
                foreach ($post['selected_ids'] as $id) {
                    if (!$this->groupeUtilisateur->deleteGroupeUtilisateur($id)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $messageSuccess = "Groupes utilisateurs supprimés avec succès.";
                    $this->auditLog->logSuppression($userId, 'groupe_utilisateur', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la suppression des groupes utilisateurs.";
                    $this->auditLog->logSuppression($userId, 'groupe_utilisateur', 'Erreur');
                }
            }

            if (isset($get['id_groupe'])) {
                $groupe_a_modifier = $this->groupeUtilisateur->getGroupeUtilisateurById($get['id_groupe']);
            }

            $result['groupe_a_modifier'] = $groupe_a_modifier;
            $result['listeGroupes'] = $this->groupeUtilisateur->getAllGroupeUtilisateur();
            $result['listeTypesAll'] = $this->typeUtilisateur->getAllTypeUtilisateur();
        }

        //======PARTIE TYPE UTILISATEUR======
        if (isset($get['tab']) && $get['tab'] === 'types') {
            if (isset($post['submit_add_type']) || isset($post['btn_modifier_type'])) {
                $lib_type = $post['lib_type_utilisateur'];

                if (!empty($post['id_type_utilisateur'])) {
                    if ($this->typeUtilisateur->updateTypeUtilisateur($post['id_type_utilisateur'], $lib_type)) {
                        $messageSuccess = "Type utilisateur modifié avec succès.";
                        $this->auditLog->logModification($userId, 'type_utilisateur', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de la modification du type utilisateur.";
                        $this->auditLog->logModification($userId, 'type_utilisateur', 'Erreur');
                    }
                } else {
                    if ($this->typeUtilisateur->ajouterTypeUtilisateur($lib_type)) {
                        $messageSuccess = "Type utilisateur ajouté avec succès.";
                        $this->auditLog->logCreation($userId, 'type_utilisateur', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de l'ajout du type utilisateur.";
                        $this->auditLog->logCreation($userId, 'type_utilisateur', 'Erreur');
                    }
                }
            }

            if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
                $success = true;
                foreach ($post['selected_ids'] as $id) {
                    if (!$this->typeUtilisateur->deleteTypeUtilisateur($id)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $messageSuccess = "Types utilisateurs supprimés avec succès.";
                    $this->auditLog->logSuppression($userId, 'type_utilisateur', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la suppression des types utilisateurs.";
                    $this->auditLog->logSuppression($userId, 'type_utilisateur', 'Erreur');
                }
            }

            if (isset($get['id_type'])) {
                $type_a_modifier = $this->typeUtilisateur->getTypeUtilisateurById($get['id_type']);
            }

            $result['type_a_modifier'] = $type_a_modifier;
            $result['listeTypes'] = $this->typeUtilisateur->getAllTypeUtilisateur();
        }

        $result['messageErreur'] = $messageErreur;
        $result['messageSuccess'] = $messageSuccess;
        if (!isset($result['listeTypesAll'])) {
            $result['listeTypesAll'] = $this->typeUtilisateur->getAllTypeUtilisateur();
        }

        return $result;
    }


    //=============================GESTION SPECIALITE=============================
    public function gestionSpecialite(array $post, array $get, string $userId): array
    {
        $specialite_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_specialite']) || isset($post['btn_modifier_specialite'])) {
            $lib_specialite = $post['specialite'];

            if (!empty($post['id_specialite'])) {
                if ($this->specialite->updateSpecialite($post['id_specialite'], $lib_specialite)) {
                    $messageSuccess = "Spécialité modifiée avec succès.";
                    $this->auditLog->logModification($userId, 'specialite', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification de la spécialité.";
                    $this->auditLog->logModification($userId, 'specialite', 'Erreur');
                }
            } else {
                if ($this->specialite->ajouterSpecialite($lib_specialite)) {
                    $messageSuccess = "Spécialité ajoutée avec succès.";
                    $this->auditLog->logCreation($userId, 'specialite', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout de la spécialité.";
                    $this->auditLog->logCreation($userId, 'specialite', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->specialite->deleteSpecialite($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Spécialités supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'specialite', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des spécialités.";
                $this->auditLog->logSuppression($userId, 'specialite', 'Erreur');
            }
        }

        if (isset($get['id_specialite'])) {
            $specialite_a_modifier = $this->specialite->getSpecialiteById($get['id_specialite']);
        }

        return [
            'specialite_a_modifier' => $specialite_a_modifier,
            'listeSpecialites' => $this->specialite->getAllSpecialites(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION NIVEAU ETUDE=============================
    public function gestionNiveauEtude(array $post, array $get, string $userId): array
    {
        $niveau_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_niveau']) || isset($post['btn_modifier_niveau'])) {
            $lib_niveau = $post['lib_niv_etude'];
            $montant_scolarite = $post['montant_scolarite'];
            $montant_inscription = $post['montant_inscription'];
            $id_enseignant = isset($post['id_enseignant']) ? $post['id_enseignant'] : null;

            if (!empty($post['id_niv_etude'])) {
                if ($this->niveauEtude->updateNiveauEtude($post['id_niv_etude'], $lib_niveau, $montant_scolarite, $montant_inscription, $id_enseignant)) {
                    $messageSuccess = "Niveau d'étude modifié avec succès.";
                    $this->auditLog->logModification($userId, 'niveau_etude', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du niveau d'étude.";
                    $this->auditLog->logModification($userId, 'niveau_etude', 'Erreur');
                }
            } else {
                if ($this->niveauEtude->ajouterNiveauEtude($lib_niveau, $montant_scolarite, $montant_inscription, $id_enseignant)) {
                    $messageSuccess = "Niveau d'étude ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'niveau_etude', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du niveau d'étude.";
                    $this->auditLog->logCreation($userId, 'niveau_etude', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->niveauEtude->deleteNiveauEtude($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Niveaux d'étude supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'niveau_etude', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des niveaux d'étude.";
                $this->auditLog->logSuppression($userId, 'niveau_etude', 'Erreur');
            }
        }

        if (isset($get['id_niv_etude'])) {
            $niveau_a_modifier = $this->niveauEtude->getNiveauEtudeById($get['id_niv_etude']);
        }

        return [
            'niveau_a_modifier' => $niveau_a_modifier,
            'listeNiveaux' => $this->niveauEtude->getAllNiveauxEtudes(),
            'listeEnseignants' => $this->enseignant->getAllEnseignants(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION UE=============================
    public function gestionUe(array $post, array $get, string $userId): array
    {
        $ue_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_ue']) || isset($post['btn_modifier_ue'])) {
            $lib_ue = $post['lib_ue'];
            $credit = $post['credit'];
            $id_niveau_etude = $post['niveau_etude'];
            $id_semestre = $post['semestre'];
            $id_annee = $post['annee_academique'];
            $id_enseignant = $post['professeur_responsable'] ?? null;

            if (!empty($post['id_ue'])) {
                if ($this->ue->updateUe($post['id_ue'], $lib_ue, $id_niveau_etude, $id_semestre, $id_annee, $credit, $id_enseignant)) {
                    $messageSuccess = "UE modifiée avec succès.";
                    $this->auditLog->logModification($userId, 'ue', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification de l'UE.";
                    $this->auditLog->logModification($userId, 'ue', 'Erreur');
                }
            } else {
                if ($this->ue->ajouterUe($lib_ue, $id_niveau_etude, $id_semestre, $id_annee, $credit, $id_enseignant)) {
                    $messageSuccess = "UE ajoutée avec succès.";
                    $this->auditLog->logCreation($userId, 'ue', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout de l'UE.";
                    $this->auditLog->logCreation($userId, 'ue', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->ue->deleteUe($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "UEs supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'ue', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des UEs.";
                $this->auditLog->logSuppression($userId, 'ue', 'Erreur');
            }
        }

        if (isset($get['id_ue'])) {
            $ue_a_modifier = $this->ue->getUeById($get['id_ue']);
        }

        return [
            'ue_a_modifier' => $ue_a_modifier,
            'listeUes' => $this->ue->getAllUes(),
            'listeNiveauxEtude' => $this->niveauEtude->getAllNiveauxEtudes(),
            'listeSemestres' => $this->semestre->getAllSemestres(),
            'listeAnnees' => $this->anneeAcademique->getAllAnneeAcademiques(),
            'listeEnseignants' => $this->enseignant->getAllEnseignants(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION ECUE=============================
    public function gestionEcue(array $post, array $get, string $userId): array
    {
        $ecue_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_ecue']) || isset($post['btn_modifier_ecue'])) {
            $id_ue = $post['id_ue'];
            $lib_ecue = $post['lib_ecue'];
            $credit = $post['credit'];
            $id_enseignant = $post['professeur_responsable'] ?? null;

            if (!empty($post['id_ecue'])) {
                if ($this->ecue->updateEcue($post['id_ecue'], $id_ue, $lib_ecue, $credit, $id_enseignant)) {
                    $messageSuccess = "ECUE modifiée avec succès.";
                    $this->auditLog->logModification($userId, 'ecue', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification de l'ECUE.";
                    $this->auditLog->logModification($userId, 'ecue', 'Erreur');
                }
            } else {
                if ($this->ecue->ajouterEcue($id_ue, $lib_ecue, $credit, $id_enseignant)) {
                    $messageSuccess = "ECUE ajoutée avec succès.";
                    $this->auditLog->logCreation($userId, 'ecue', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout de l'ECUE.";
                    $this->auditLog->logCreation($userId, 'ecue', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->ecue->deleteEcue($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "ECUEs supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'ecue', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des ECUEs.";
                $this->auditLog->logSuppression($userId, 'ecue', 'Erreur');
            }
        }

        if (isset($get['id_ecue'])) {
            $ecue_a_modifier = $this->ecue->getEcueById($get['id_ecue']);
        }

        return [
            'ecue_a_modifier' => $ecue_a_modifier,
            'listeEcues' => $this->ecue->getAllEcues(),
            'listeUes' => $this->ue->getAllUes(),
            'listeEnseignants' => $this->enseignant->getAllEnseignants(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION STATUT JURY=============================
    public function gestionStatutJury(array $post, array $get, string $userId): array
    {
        $statut_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_statut_jury']) || isset($post['btn_modifier_statut_jury'])) {
            $lib_statut = $post['statut_jury'];

            if (!empty($post['id_statut_jury'])) {
                if ($this->statutJury->updateStatutJury($post['id_statut_jury'], $lib_statut)) {
                    $messageSuccess = "Statut jury modifié avec succès.";
                    $this->auditLog->logModification($userId, 'statut_jury', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du statut jury.";
                    $this->auditLog->logModification($userId, 'statut_jury', 'Erreur');
                }
            } else {
                if ($this->statutJury->ajouterStatutJury($lib_statut)) {
                    $messageSuccess = "Statut jury ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'statut_jury', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du statut jury.";
                    $this->auditLog->logCreation($userId, 'statut_jury', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->statutJury->deleteStatutJury($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Statuts jury supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'statut_jury', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des statuts jury.";
                $this->auditLog->logSuppression($userId, 'statut_jury', 'Erreur');
            }
        }

        if (isset($get['id_statut_jury'])) {
            $statut_a_modifier = $this->statutJury->getStatutJuryById($get['id_statut_jury']);
        }

        return [
            'statut_a_modifier' => $statut_a_modifier,
            'listeStatuts' => $this->statutJury->getAllStatutsJury(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION NIVEAU APPROBATION=============================
    public function gestionNiveauApprobation(array $post, array $get, string $userId): array
    {
        $niveau_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_niveau_approbation']) || isset($post['btn_modifier_niveau_approbation'])) {
            $lib_niveau = $post['niveaux_approbation'];

            if (!empty($post['id_approb'])) {
                if ($this->niveauApprobation->updateNiveauApprobation($post['id_approb'], $lib_niveau)) {
                    $messageSuccess = "Niveau d'approbation modifié avec succès.";
                    $this->auditLog->logModification($userId, 'niveau_approbation', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du niveau d'approbation.";
                    $this->auditLog->logModification($userId, 'niveau_approbation', 'Erreur');
                }
            } else {
                if ($this->niveauApprobation->ajouterNiveauApprobation($lib_niveau)) {
                    $messageSuccess = "Niveau d'approbation ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'niveau_approbation', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du niveau d'approbation.";
                    $this->auditLog->logCreation($userId, 'niveau_approbation', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->niveauApprobation->deleteNiveauApprobation($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Niveaux d'approbation supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'niveau_approbation', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des niveaux d'approbation.";
                $this->auditLog->logSuppression($userId, 'niveau_approbation', 'Erreur');
            }
        }

        if (isset($get['id_approb'])) {
            $niveau_a_modifier = $this->niveauApprobation->getNiveauApprobationById($get['id_approb']);
        }

        return [
            'niveau_a_modifier' => $niveau_a_modifier,
            'listeNiveaux' => $this->niveauApprobation->getAllNiveauxApprobation(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION SEMESTRES=============================
    public function gestionSemestre(array $post, array $get, string $userId): array
    {
        $semestre_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_semestre']) || isset($post['btn_modifier_semestre'])) {
            $lib_semestre = $post['lib_semestre'];
            $id_niv_etude = $post['niveau_etude'];

            if (!empty($post['id_semestre'])) {
                if ($this->semestre->updateSemestre($post['id_semestre'], $lib_semestre, $id_niv_etude)) {
                    $messageSuccess = "Semestre modifié avec succès.";
                    $this->auditLog->logModification($userId, 'semestre', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du semestre.";
                    $this->auditLog->logModification($userId, 'semestre', 'Erreur');
                }
            } else {
                if ($this->semestre->ajouterSemestre($lib_semestre, $id_niv_etude)) {
                    $messageSuccess = "Semestre ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'semestre', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du semestre.";
                    $this->auditLog->logCreation($userId, 'semestre', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->semestre->deleteSemestre($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Semestres supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'semestre', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des semestres.";
                $this->auditLog->logSuppression($userId, 'semestre', 'Erreur');
            }
        }

        if (isset($get['id_semestre'])) {
            $semestre_a_modifier = $this->semestre->getSemestreById($get['id_semestre']);
        }

        return [
            'semestre_a_modifier' => $semestre_a_modifier,
            'listeSemestres' => $this->semestre->getAllSemestres(),
            'listeNiveauxEtude' => $this->niveauEtude->getAllNiveauxEtudes(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION NIVEAU ACCES DONNEES=============================
    public function gestionNiveauAccesDonnees(array $post, array $get, string $userId): array
    {
        $niveau_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_niveau']) || isset($post['btn_modifier_niveau_acces'])) {
            $lib_niveau = $post['lib_niveau_acces_donnees'];

            if (!empty($post['id_niveau_acces_donnees'])) {
                if ($this->niveauAccesDonnees->updateNiveauAccesDonnees($post['id_niveau_acces_donnees'], $lib_niveau)) {
                    $messageSuccess = "Niveau d'accès modifié avec succès.";
                    $this->auditLog->logModification($userId, 'niveau_acces_donnees', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du niveau d'accès.";
                    $this->auditLog->logModification($userId, 'niveau_acces_donnees', 'Erreur');
                }
            } else {
                if ($this->niveauAccesDonnees->ajouterNiveauAccesDonnees($lib_niveau)) {
                    $messageSuccess = "Niveau d'accès ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'niveau_acces_donnees', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du niveau d'accès.";
                    $this->auditLog->logCreation($userId, 'niveau_acces_donnees', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->niveauAccesDonnees->deleteNiveauAccesDonnees($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Niveaux d'accès supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'niveau_acces_donnees', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des niveaux d'accès.";
                $this->auditLog->logSuppression($userId, 'niveau_acces_donnees', 'Erreur');
            }
        }

        if (isset($get['id_niveau'])) {
            $niveau_a_modifier = $this->niveauAccesDonnees->getNiveauAccesDonneesById($get['id_niveau']);
        }

        return [
            'niveau_a_modifier' => $niveau_a_modifier,
            'listeNiveaux' => $this->niveauAccesDonnees->getAllNiveauxAccesDonnees(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION TRAITEMENT=============================
    public function gestionTraitement(array $post, array $get, string $userId): array
    {
        $traitement_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_traitement']) || isset($post['btn_modifier_traitement'])) {
            $lib_traitement = $post['lib_traitement'];
            $label_traitement = $post['label_traitement'];
            $icone_traitement = $post['icone_traitement'];
            $ordre_traitement = $post['ordre_traitement'];

            if (!empty($post['id_traitement'])) {
                if ($this->traitement->updateTraitement($post['id_traitement'], $lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement)) {
                    $messageSuccess = "Traitement modifié avec succès.";
                    $this->auditLog->logModification($userId, 'traitement', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du traitement.";
                    $this->auditLog->logModification($userId, 'traitement', 'Erreur');
                }
            } else {
                if ($this->traitement->addTraitement($lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement)) {
                    $messageSuccess = "Traitement ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'traitement', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du traitement.";
                    $this->auditLog->logCreation($userId, 'traitement', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->traitement->deleteTraitement($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Traitements supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'traitement', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des traitements.";
                $this->auditLog->logSuppression($userId, 'traitement', 'Erreur');
            }
        }

        if (isset($get['id_traitement'])) {
            $traitement_a_modifier = $this->traitement->getTraitementById($get['id_traitement']);
        }

        return [
            'traitement_a_modifier' => $traitement_a_modifier,
            'listeTraitements' => $this->traitement->getAllTraitements(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION ENTREPRISE=============================
    public function gestionEntreprise(array $post, array $get, string $userId): array
    {
        $entreprise_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_entreprise']) || isset($post['btn_modifier_entreprise'])) {
            $lib_entreprise = $post['lib_entreprise'];

            if (!empty($post['id_entreprise'])) {
                if ($this->entreprise->updateEntreprise($post['id_entreprise'], $lib_entreprise)) {
                    $messageSuccess = "Entreprise modifiée avec succès.";
                    $this->auditLog->logModification($userId, 'entreprise', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification de l'entreprise.";
                    $this->auditLog->logModification($userId, 'entreprise', 'Erreur');
                }
            } else {
                if ($this->entreprise->ajouterEntreprise($lib_entreprise)) {
                    $messageSuccess = "Entreprise ajoutée avec succès.";
                    $this->auditLog->logCreation($userId, 'entreprise', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout de l'entreprise.";
                    $this->auditLog->logCreation($userId, 'entreprise', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->entreprise->deleteEntreprise($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Entreprises supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'entreprise', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des entreprises.";
                $this->auditLog->logSuppression($userId, 'entreprise', 'Erreur');
            }
        }

        if (isset($get['id_entreprise'])) {
            $entreprise_a_modifier = $this->entreprise->getEntrepriseById($get['id_entreprise']);
        }

        return [
            'entreprise_a_modifier' => $entreprise_a_modifier,
            'listeEntreprises' => $this->entreprise->getAllEntreprises(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION ACTION=============================
    public function gestionAction(array $post, array $get, string $userId): array
    {
        $action_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_action']) || isset($post['btn_modifier_action'])) {
            $lib_action = $post['action'];

            if (!empty($post['id_action'])) {
                if ($this->action->updateAction($post['id_action'], $lib_action)) {
                    $messageSuccess = "Action modifiée avec succès.";
                    $this->auditLog->logModification($userId, 'action', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification de l'action.";
                    $this->auditLog->logModification($userId, 'action', 'Erreur');
                }
            } else {
                if ($this->action->ajouterAction($lib_action)) {
                    $messageSuccess = "Action ajoutée avec succès.";
                    $this->auditLog->logCreation($userId, 'action', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout de l'action.";
                    $this->auditLog->logCreation($userId, 'action', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->action->deleteAction($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Actions supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'action', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des actions.";
                $this->auditLog->logSuppression($userId, 'action', 'Erreur');
            }
        }

        if (isset($get['id_action'])) {
            $action_a_modifier = $this->action->getActionById($get['id_action']);
        }

        return [
            'action_a_modifier' => $action_a_modifier,
            'listeActions' => $this->action->getAllAction(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION FONCTION=============================
    public function gestionFonction(array $post, array $get, string $userId): array
    {
        $fonction_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_fonction']) || isset($post['btn_modifier_fonction'])) {
            $lib_fonction = $post['lib_fonction'];

            if (!empty($post['id_fonction'])) {
                if ($this->fonction->updateFonction($post['id_fonction'], $lib_fonction)) {
                    $messageSuccess = "Fonction modifiée avec succès.";
                    $this->auditLog->logModification($userId, 'fonction', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification de la fonction.";
                    $this->auditLog->logModification($userId, 'fonction', 'Erreur');
                }
            } else {
                if ($this->fonction->ajouterFonction($lib_fonction)) {
                    $messageSuccess = "Fonction ajoutée avec succès.";
                    $this->auditLog->logCreation($userId, 'fonction', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout de la fonction.";
                    $this->auditLog->logCreation($userId, 'fonction', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->fonction->deleteFonction($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Fonctions supprimées avec succès.";
                $this->auditLog->logSuppression($userId, 'fonction', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des fonctions.";
                $this->auditLog->logSuppression($userId, 'fonction', 'Erreur');
            }
        }

        if (isset($get['id_fonction'])) {
            $fonction_a_modifier = $this->fonction->getFonctionById($get['id_fonction']);
        }

        return [
            'fonction_a_modifier' => $fonction_a_modifier,
            'listeFonctions' => $this->fonction->getAllFonctions(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //=============================GESTION MESSAGERIE=============================
    public function gestionMessagerie(array $post, array $get, string $userId): array
    {
        $message_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($post['btn_add_message']) || isset($post['btn_modifier_message'])) {
            $contenu_message = $post['contenu_message'];
            $lib_message = $post['lib_message'];
            $type_message = $post['type_message'];

            if (!empty($post['id_message'])) {
                if ($this->message->updateMessage($post['id_message'], $contenu_message, $lib_message, $type_message)) {
                    $messageSuccess = "Message modifié avec succès.";
                    $this->auditLog->logModification($userId, 'message', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du message.";
                    $this->auditLog->logModification($userId, 'message', 'Erreur');
                }
            } else {
                if ($this->message->ajouterMessage($contenu_message, $lib_message, $type_message)) {
                    $messageSuccess = "Message ajouté avec succès.";
                    $this->auditLog->logCreation($userId, 'message', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de l'ajout du message.";
                    $this->auditLog->logCreation($userId, 'message', 'Erreur');
                }
            }
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                if (!$this->message->deleteMessage($id)) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Messages supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'message', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des messages.";
                $this->auditLog->logSuppression($userId, 'message', 'Erreur');
            }
        }

        if (isset($get['id_message'])) {
            $message_a_modifier = $this->message->getMessageById($get['id_message']);
        }

        return [
            'message_a_modifier' => $message_a_modifier,
            'listeMessages' => $this->message->getAllMessages(),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //============================GESTION ATTRIBUTION==================================
    public function gestionAttribution(array $post, array $get, string $userId, string $userGroupeId): array
    {
        $messageErreur = '';
        $messageSuccess = '';
        $attribution_a_modifier = null;

        require_once __DIR__ . '/../models/Fonctionnalite.php';
        require_once __DIR__ . '/../models/Permission.php';

        $pdo = Database::getConnection();
        $fonctionnaliteModel = new \Fonctionnalite($pdo);
        $permissionModel = new \Permission($pdo);

        $listeTypesAll = $this->typeUtilisateur->getAllTypeUtilisateur();
        $selectedTypeId = isset($get['type']) && $get['type'] !== '' ? (string) $get['type'] : 'all';

        if ($selectedTypeId !== 'all') {
            $listeGroupes = $this->groupeUtilisateur->getGroupesByTypeUtilisateur((int) $selectedTypeId);
        } else {
            $listeGroupes = $this->groupeUtilisateur->getAllGroupeUtilisateur();
        }

        $sql = "SELECT f.*, c.lib_categorie, c.code_categorie 
                FROM fonctionnalites f
                INNER JOIN categories_fonctionnalites c ON f.id_categorie = c.id_categorie
                WHERE f.actif = TRUE
                ORDER BY c.ordre_categorie, f.ordre_fonctionnalite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $listeFonctionnalites = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (isset($get['debug']) && $get['debug'] === 'permissions') {
            error_log("Liste des groupes: " . print_r($listeGroupes, true));
            error_log("Liste des fonctionnalités: " . count($listeFonctionnalites));
        }

        $selectedGroupeId = isset($get['groupe']) ? $get['groupe'] : null;
        $selectedGroupe = null;
        $permissionsGroupe = [];

        if ($selectedGroupeId) {
            $selectedGroupe = $this->groupeUtilisateur->getGroupeUtilisateurById($selectedGroupeId);
            $permissionsGroupe = $permissionModel->getAllPermissionsForGroupe($selectedGroupeId);

            if (isset($get['debug']) && $get['debug'] === 'permissions') {
                error_log("Groupe sélectionné: " . print_r($selectedGroupe, true));
                error_log("Permissions du groupe: " . count($permissionsGroupe));
            }
        }

        // Traiter le formulaire de soumission
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($post['id_GU'])) {
            $this->handleAttributionSubmit($post, $userId, $userGroupeId);
        }

        $permissionsMap = [];
        foreach ($listeGroupes as $groupe) {
            $permissionsMap[$groupe->id_GU] = $permissionModel->getAllPermissionsForGroupe($groupe->id_GU);
        }

        return [
            'permissionsMap' => $permissionsMap,
            'listeGroupes' => $listeGroupes,
            'listeTypesAll' => $listeTypesAll,
            'selectedTypeId' => $selectedTypeId,
            'listeFonctionnalites' => $listeFonctionnalites,
            'selectedGroupe' => $selectedGroupe,
            'permissionsGroupe' => $permissionsGroupe,
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
            'attribution_a_modifier' => $attribution_a_modifier,
            // COMPATIBILITÉ
            'listeTraitements' => $listeFonctionnalites,
            'attributionsGroupe' => $permissionsGroupe,
            'attributionsMap' => $permissionsMap,
        ];
    }


    //============================GESTION MENUS==================================
    public function gestionMenus(array $post, array $get): array
    {
        $messageErreur = '';
        $messageSuccess = '';

        if (isset($get['success'])) {
            $successMessages = [
                'category_created' => 'Catégorie créée avec succès.',
                'category_updated' => 'Catégorie mise à jour.',
                'category_deactivated' => 'Catégorie désactivée.',
                'category_activated' => 'Catégorie réactivée.',
                'item_created' => 'Élément créé avec succès.',
                'item_updated' => 'Élément mis à jour.',
                'item_deactivated' => 'Élément désactivé.',
                'item_activated' => 'Élément réactivé.',
            ];
            $messageSuccess = $successMessages[$get['success']] ?? 'Opération réussie.';
        }

        if (isset($get['error'])) {
            $errorMessages = [
                'csrf' => 'Erreur de sécurité CSRF. Veuillez réessayer.',
                'readonly' => 'Vous n\'avez pas les droits de modification.',
                'exception' => isset($get['msg']) ? urldecode($get['msg']) : 'Une erreur est survenue.',
            ];
            $messageErreur = $errorMessages[$get['error']] ?? 'Une erreur est survenue.';
        }

        require_once __DIR__ . '/../models/Categorie.php';
        require_once __DIR__ . '/../models/Fonctionnalite.php';

        $pdo = Database::getConnection();
        $categorieModel = new \Categorie($pdo);
        $fonctionnaliteModel = new \Fonctionnalite($pdo);

        $isEditable = function_exists('canEdit') ? (bool) canEdit() : true;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!\CheckMaster\Core\Csrf::validate($post['csrf_token'] ?? null)) {
                header('Location: ?page=parametres_generaux&action=gestion_menus&error=csrf');
                exit;
            }
            if (!$isEditable) {
                header('Location: ?page=parametres_generaux&action=gestion_menus&error=readonly');
                exit;
            }

            $op = isset($post['op']) ? (string) $post['op'] : '';

            try {
                if ($op === 'create_category') {
                    $categorieModel->createCategorie([
                        'code_categorie' => strtoupper(trim((string) ($post['code_categorie'] ?? ''))),
                        'lib_categorie' => trim((string) ($post['lib_categorie'] ?? '')),
                        'description_categorie' => trim((string) ($post['description_categorie'] ?? '')),
                        'icone_categorie' => trim((string) ($post['icone_categorie'] ?? '')),
                        'ordre_categorie' => (int) ($post['ordre_categorie'] ?? 0),
                        'actif' => isset($post['actif']) ? 1 : 0,
                    ]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=category_created');
                    exit;
                } elseif ($op === 'update_category') {
                    $id = (int) ($post['id_categorie'] ?? 0);
                    $categorieModel->updateCategorie($id, [
                        'lib_categorie' => trim((string) ($post['lib_categorie'] ?? '')),
                        'description_categorie' => trim((string) ($post['description_categorie'] ?? '')),
                        'icone_categorie' => trim((string) ($post['icone_categorie'] ?? '')),
                        'ordre_categorie' => (int) ($post['ordre_categorie'] ?? 0),
                        'actif' => isset($post['actif']) ? 1 : 0,
                    ]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=category_updated');
                    exit;
                } elseif ($op === 'deactivate_category') {
                    $id = (int) ($post['id_categorie'] ?? 0);
                    $pdo->prepare("UPDATE categories_fonctionnalites SET actif = 0 WHERE id_categorie = ?")->execute([$id]);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 0 WHERE id_categorie = ?")->execute([$id]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=category_deactivated');
                    exit;
                } elseif ($op === 'activate_category') {
                    $id = (int) ($post['id_categorie'] ?? 0);
                    $pdo->prepare("UPDATE categories_fonctionnalites SET actif = 1 WHERE id_categorie = ?")->execute([$id]);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 1 WHERE id_categorie = ?")->execute([$id]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=category_activated');
                    exit;
                } elseif ($op === 'create_fonctionnalite') {
                    $idCategorie = (int) ($post['id_categorie'] ?? 0);
                    $type = (string) ($post['type_item'] ?? 'parent');
                    $code = strtoupper(trim((string) ($post['code_fonctionnalite'] ?? '')));
                    $label = trim((string) ($post['label_fonctionnalite'] ?? ''));
                    $lib = trim((string) ($post['lib_fonctionnalite'] ?? $label));
                    $url = trim((string) ($post['url_fonctionnalite'] ?? '#'));
                    $icone = trim((string) ($post['icone_fonctionnalite'] ?? ''));
                    $ordre = (int) ($post['ordre_fonctionnalite'] ?? 0);
                    $actif = isset($post['actif']) ? 1 : 0;

                    $estSousPage = $type === 'child' ? 1 : 0;
                    $pageParente = null;
                    if ($estSousPage) {
                        $pageParente = trim((string) ($post['page_parente'] ?? ''));
                        if ($pageParente === '') {
                            throw new Exception('Pour créer un écran, il faut choisir un sous-menu parent.');
                        }
                    }

                    $fonctionnaliteModel->createFonctionnalite([
                        'id_categorie' => $idCategorie,
                        'code_fonctionnalite' => $code,
                        'lib_fonctionnalite' => $lib,
                        'label_fonctionnalite' => $label,
                        'description_fonctionnalite' => trim((string) ($post['description_fonctionnalite'] ?? '')),
                        'url_fonctionnalite' => $url,
                        'icone_fonctionnalite' => $icone,
                        'ordre_fonctionnalite' => $ordre,
                        'est_sous_page' => $estSousPage,
                        'page_parente' => $pageParente,
                        'actif' => $actif,
                    ]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=item_created');
                    exit;
                } elseif ($op === 'update_fonctionnalite') {
                    $id = (int) ($post['id_fonctionnalite'] ?? 0);
                    $idCategorie = (int) ($post['id_categorie'] ?? 0);
                    $type = (string) ($post['type_item'] ?? 'parent');
                    $estSousPage = $type === 'child' ? 1 : 0;
                    $pageParente = null;
                    if ($estSousPage) {
                        $pageParente = trim((string) ($post['page_parente'] ?? ''));
                        if ($pageParente === '') {
                            throw new Exception('Pour un écran, le parent est obligatoire.');
                        }
                    }
                    $fonctionnaliteModel->updateFonctionnaliteAdmin($id, [
                        'id_categorie' => $idCategorie,
                        'lib_fonctionnalite' => trim((string) ($post['lib_fonctionnalite'] ?? '')),
                        'label_fonctionnalite' => trim((string) ($post['label_fonctionnalite'] ?? '')),
                        'description_fonctionnalite' => trim((string) ($post['description_fonctionnalite'] ?? '')),
                        'url_fonctionnalite' => trim((string) ($post['url_fonctionnalite'] ?? '#')),
                        'icone_fonctionnalite' => trim((string) ($post['icone_fonctionnalite'] ?? '')),
                        'ordre_fonctionnalite' => (int) ($post['ordre_fonctionnalite'] ?? 0),
                        'est_sous_page' => $estSousPage,
                        'page_parente' => $pageParente,
                        'actif' => isset($post['actif']) ? 1 : 0,
                    ]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=item_updated');
                    exit;
                } elseif ($op === 'deactivate_fonctionnalite') {
                    $id = (int) ($post['id_fonctionnalite'] ?? 0);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 0 WHERE id_fonctionnalite = ?")->execute([$id]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=item_deactivated');
                    exit;
                } elseif ($op === 'activate_fonctionnalite') {
                    $id = (int) ($post['id_fonctionnalite'] ?? 0);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 1 WHERE id_fonctionnalite = ?")->execute([$id]);
                    header('Location: ?page=parametres_generaux&action=gestion_menus&success=item_activated');
                    exit;
                }
            } catch (Throwable $e) {
                $errorMsg = urlencode($e->getMessage());
                header('Location: ?page=parametres_generaux&action=gestion_menus&error=exception&msg=' . $errorMsg);
                exit;
            }
        }

        // Charger la liste pour l'affichage (inclut inactifs)
        $categories = $pdo->query("SELECT * FROM categories_fonctionnalites ORDER BY ordre_categorie ASC, id_categorie ASC")->fetchAll(PDO::FETCH_OBJ);
        $fonctionnalites = $pdo->query("SELECT * FROM fonctionnalites ORDER BY id_categorie ASC, ordre_fonctionnalite ASC, id_fonctionnalite ASC")->fetchAll(PDO::FETCH_OBJ);

        // Construire arborescence par catégorie
        $tree = [];
        foreach ($categories as $c) {
            $tree[(int) $c->id_categorie] = [
                'categorie' => $c,
                'items' => [],
                'parents' => [],
            ];
        }
        foreach ($fonctionnalites as $f) {
            $cid = (int) ($f->id_categorie ?? 0);
            if (!isset($tree[$cid])) {
                continue;
            }
            $tree[$cid]['items'][] = $f;
        }
        foreach ($tree as $cid => &$node) {
            $items = $node['items'];
            $parentsByCode = [];
            $childrenByParent = [];
            foreach ($items as $f) {
                $isSous = !empty($f->est_sous_page);
                $code = (string) ($f->code_fonctionnalite ?? '');
                $parentCode = (string) ($f->page_parente ?? '');
                if ($isSous && $parentCode !== '') {
                    $childrenByParent[$parentCode] = $childrenByParent[$parentCode] ?? [];
                    $childrenByParent[$parentCode][] = $f;
                } elseif (!$isSous && $code !== '') {
                    $parentsByCode[$code] = $f;
                }
            }
            foreach ($parentsByCode as $code => $p) {
                $children = $childrenByParent[$code] ?? [];
                usort($children, fn($a, $b) => ((int) ($a->ordre_fonctionnalite ?? 0)) <=> ((int) ($b->ordre_fonctionnalite ?? 0)));
                $p->children = array_values($children);
            }
            $parents = array_values($parentsByCode);
            usort($parents, fn($a, $b) => ((int) ($a->ordre_fonctionnalite ?? 0)) <=> ((int) ($b->ordre_fonctionnalite ?? 0)));
            $node['parents'] = $parents;
        }
        unset($node);

        return [
            'menuMgmtCategories' => $categories,
            'menuMgmtTree' => $tree,
            'menuMgmtIsEditable' => $isEditable,
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }


    //============================PRIVATE: ATTRIBUTION SUBMIT==================================
    private function handleAttributionSubmit(array $postData, string $userId, string $userGroupeId): void
    {
        $groupeId = $postData['id_GU'];
        $permissions = isset($postData['permissions']) ? $postData['permissions'] : [];

        error_log("=== DÉBUT SAUVEGARDE PERMISSIONS ===");
        error_log("Groupe modifié: $groupeId");
        error_log("Nombre de permissions reçues: " . count($permissions));
        error_log("Permissions POST: " . json_encode($permissions, JSON_PRETTY_PRINT));

        if (!\CheckMaster\Core\Csrf::validate($postData['csrf_token'] ?? null)) {
            error_log("❌ ERREUR CSRF - Abandon");
            header('Location: ?page=parametres_generaux&action=gestion_attribution&groupe=' . $groupeId . '&error=csrf&_r=1');
            exit;
        }

        try {
            $conn = Database::getConnection();
            $conn->beginTransaction();

            $isModifyingOwnGroup = (int) $groupeId === (int) $userGroupeId;

            error_log("=== PROTECTION ANTI-LOCKOUT ===");
            error_log("Groupe modifié: $groupeId");
            error_log("Groupe de l'utilisateur: " . $userGroupeId);
            error_log("isModifyingOwnGroup: " . ($isModifyingOwnGroup ? 'OUI' : 'NON'));

            if ($isModifyingOwnGroup) {
                $stmtFonc = $conn->prepare(
                    "SELECT id_fonctionnalite FROM fonctionnalites 
                     WHERE url_fonctionnalite LIKE '%gestion_attribution%' 
                     OR code_fonctionnalite = 'PARAM_ATTRIB' 
                     LIMIT 1"
                );
                $stmtFonc->execute();
                $currentPageFonc = $stmtFonc->fetch(PDO::FETCH_ASSOC);

                if ($currentPageFonc) {
                    $currentPageId = (int) $currentPageFonc['id_fonctionnalite'];

                    error_log("Fonctionnalité gestion_attribution trouvée: ID = $currentPageId");
                    error_log("Permission avant protection: voir=" . (isset($permissions[$currentPageId]['voir']) ? 'OUI' : 'NON'));

                    if (!isset($permissions[$currentPageId]['voir'])) {
                        $permissions[$currentPageId]['voir'] = 'on';
                        error_log("✅ Permission 'voir' FORCÉE pour ID $currentPageId");
                    }
                    if (!isset($permissions[$currentPageId]['modifier'])) {
                        $permissions[$currentPageId]['modifier'] = 'on';
                        error_log("✅ Permission 'modifier' FORCÉE pour ID $currentPageId");
                    }
                } else {
                    error_log("❌ Fonctionnalité gestion_attribution NON TROUVÉE");
                }
            }

            $stmt = $conn->prepare("DELETE FROM permissions WHERE id_GU = ?");
            $stmt->execute([$groupeId]);
            error_log("🗑️ Permissions supprimées pour groupe $groupeId");

            $stmt = $conn->prepare(
                "INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insertCount = 0;
            foreach ($permissions as $fonctionnaliteId => $actions) {
                if (!empty($actions)) {
                    $peutVoir = isset($actions['voir']) ? 1 : 0;
                    $peutCreer = isset($actions['creer']) ? 1 : 0;
                    $peutModifier = isset($actions['modifier']) ? 1 : 0;
                    $peutSupprimer = isset($actions['supprimer']) ? 1 : 0;

                    if ($peutVoir || $peutCreer || $peutModifier || $peutSupprimer) {
                        $stmt->execute([
                            $groupeId,
                            $fonctionnaliteId,
                            $peutVoir,
                            $peutCreer,
                            $peutModifier,
                            $peutSupprimer
                        ]);
                        $insertCount++;
                        error_log("  ✅ Permission ajoutée: Fonc=$fonctionnaliteId V=$peutVoir C=$peutCreer M=$peutModifier S=$peutSupprimer");
                    }
                }
            }

            $conn->commit();
            error_log("💾 Transaction validée: $insertCount permissions insérées pour groupe $groupeId");

            $this->auditLog->logAction(
                $userId,
                'Modification',
                'permissions',
                'Succès',
                "Mise à jour permissions CRUD groupe={$groupeId}"
            );
            header('Location: ?page=parametres_generaux&action=gestion_attribution&groupe=' . $groupeId . '&success=1&_r=1');
            exit;
        } catch (Exception $e) {
            try {
                if (isset($conn) && $conn->inTransaction()) {
                    $conn->rollBack();
                }
            } catch (Exception $e2) {
                // ignore rollback errors
            }

            $this->auditLog->logAction(
                $userId,
                'Modification',
                'permissions',
                'Erreur',
                'Erreur attribution permissions CRUD: ' . $e->getMessage()
            );
            header('Location: ?page=parametres_generaux&action=gestion_attribution&groupe=' . $groupeId . '&error=1&_r=1');
            exit;
        }
    }
}
