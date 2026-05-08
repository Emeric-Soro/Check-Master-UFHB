<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Action.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Fonction.php';
require_once __DIR__ . '/../models/Grade.php';
require_once __DIR__ . '/../models/GroupeUtilisateur.php';
require_once __DIR__ . '/../models/NiveauAccesDonnees.php';
require_once __DIR__ . '/../models/NiveauApprobation.php';
require_once __DIR__ . '/../models/Specialite.php';
require_once __DIR__ . '/../models/StatutJury.php';
require_once __DIR__ . '/../models/TypeUtilisateur.php';
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
use Fonction;
use Grade;
use GroupeUtilisateur;
use NiveauAccesDonnees;
use NiveauApprobation;
use Specialite;
use StatutJury;
use TypeUtilisateur;
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
    private $db;
    private $action;
    private $anneeAcademique;
    private $fonction;
    private $grade;
    private $groupeUtilisateur;
    private $niveauAccesDonnees;
    private $niveauApprobation;
    private $niveauEtude;
    private $specialite;
    private $statutJury;
    private $typeUtilisateur;
    private $semestre;
    private $entreprise;
    private $traitement;
    private $message;
    private $attribution;
    private $enseignant;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->anneeAcademique = new AnneeAcademique($db);
        $this->action = new Action($db);
        $this->fonction = new Fonction($db);
        $this->grade = new Grade($db);
        $this->groupeUtilisateur = new GroupeUtilisateur($db);
        $this->niveauAccesDonnees = new NiveauAccesDonnees($db);
        $this->niveauApprobation = new NiveauApprobation($db);
        $this->typeUtilisateur = new TypeUtilisateur($db);
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
            $dateDebut = trim((string) ($post['date_debut'] ?? ''));
            $dateFin = trim((string) ($post['date_fin'] ?? ''));
            $idAnneeCourante = isset($post['id_annee_acad']) && trim((string) $post['id_annee_acad']) !== ''
                ? (int) $post['id_annee_acad']
                : null;

            if ($dateDebut === '' || $dateFin === '') {
                $messageErreur = "Les dates de début et de fin sont obligatoires.";
            } else {
                $annee1 = date("Y", strtotime($dateDebut));
                $annee2 = date("Y", strtotime($dateFin));

                if (($annee1 == $annee2) || ($dateDebut >= $dateFin)) {
                    $messageErreur = "Les dates de début et de fin ne sont pas valides.";
                } else {
                    $nouvel_id = (int) (substr($annee2, 0, 1) . substr($annee2, 2, 2) . substr($annee1, 2, 2));
                    $anneeExistante = $this->anneeAcademique->getAnneeAcademiqueById($nouvel_id);

                    if ($anneeExistante !== false && $anneeExistante !== null && (int) ($anneeExistante->id_annee_acad ?? 0) !== $idAnneeCourante) {
                        $messageErreur = "Cette année académique existe déjà.";
                        $this->auditLog->logCreation($userId, 'annee_academique', 'Erreur');
                    } elseif ($this->anneeAcademique->isAnneeAcademiqueExist($idAnneeCourante, $dateDebut, $dateFin)) {
                        $messageErreur = "Cette année académique existe déjà.";
                        $this->auditLog->logCreation($userId, 'annee_academique', 'Erreur');
                    } elseif ($idAnneeCourante === null && $this->anneeAcademique->isAnneeAcademiqueInUse($nouvel_id)) {
                        $messageErreur = "Cette année académique est déjà utilisée.";
                        $this->auditLog->logCreation($userId, 'annee_academique', 'Erreur');
                    }

                    if (empty($messageErreur)) {
                        if ($idAnneeCourante !== null) {
                            if ($this->anneeAcademique->updateAnneeAcademique($idAnneeCourante, $dateDebut, $dateFin)) {
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
            // NOTE: montant_scolarite et montant_inscription ne sont plus stockés dans niveau_etude
            // Ces valeurs doivent être gérées via frais_inscription pour chaque année académique
            $montant_scolarite = $post['montant_scolarite'] ?? null;  // OBSOLÈTE - Ignoré
            $montant_inscription = $post['montant_inscription'] ?? null;  // OBSOLÈTE - Ignoré
            $id_enseignant = isset($post['id_enseignant']) ? $post['id_enseignant'] : null;

            if (!empty($post['id_niv_etude'])) {
                // Les montants sont ignorés, seul le libellé et l'enseignant sont mis à jour
                if ($this->niveauEtude->updateNiveauEtude($post['id_niv_etude'], $lib_niveau, $montant_scolarite, $montant_inscription, $id_enseignant)) {
                    $messageSuccess = "Niveau d'étude modifié avec succès.";
                    if ($montant_scolarite || $montant_inscription) {
                        $messageSuccess .= " ATTENTION: Les montants doivent être configurés dans 'Frais d'inscription' pour chaque année académique.";
                    }
                    $this->auditLog->logModification($userId, 'niveau_etude', 'Succès');
                } else {
                    $messageErreur = "Erreur lors de la modification du niveau d'étude.";
                    $this->auditLog->logModification($userId, 'niveau_etude', 'Erreur');
                }
            } else {
                if ($this->niveauEtude->ajouterNiveauEtude($lib_niveau, $montant_scolarite, $montant_inscription, $id_enseignant)) {
                    $messageSuccess = "Niveau d'étude ajouté avec succès.";
                    if ($montant_scolarite || $montant_inscription) {
                        $messageSuccess .= " ATTENTION: Les montants doivent être configurés dans 'Frais d'inscription' pour chaque année académique.";
                    }
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

        if (!$this->traitement->hasTraitementTable()) {
            return [
                'traitement_a_modifier' => null,
                'listeTraitements' => [],
                'messageErreur' => "Le référentiel des traitements est indisponible: la table 'traitement' n'existe pas dans la base actuelle.",
                'messageSuccess' => '',
            ];
        }

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
            $lib_entreprise = trim((string)($post['lib_long_entreprise'] ?? ($post['lib_entreprise'] ?? '')));
            $lib_court = trim((string)($post['lib_court_en'] ?? ($post['lib_court'] ?? '')));
            $email = trim((string)($post['email'] ?? ''));
            $telephone = trim((string)($post['telephone'] ?? ''));
            $logo = trim((string)($post['logo'] ?? ''));
            $id_entreprise = isset($post['id_entreprise']) && $post['id_entreprise'] !== '' ? (int)$post['id_entreprise'] : null;

            if ($lib_entreprise === '') {
                $messageErreur = "Le libellé de l'entreprise est obligatoire.";
            } else {
                if ($id_entreprise !== null) {
                    if ($this->entreprise->updateEntreprise($id_entreprise, $lib_entreprise, $lib_court, $email, $telephone, $logo)) {
                        $messageSuccess = "Entreprise modifiée avec succès.";
                        $this->auditLog->logModification($userId, 'entreprise', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de la modification de l'entreprise.";
                        $this->auditLog->logModification($userId, 'entreprise', 'Erreur');
                    }
                } else {
                    // Pour l'ajout, on peut avoir besoin d'un ID manuel si non auto-incrémenté
                    // Le modèle ne gère pas l'ID manuel dans ajouterEntreprise, on vérifie le schéma
                    if ($this->entreprise->ajouterEntreprise($lib_entreprise, $lib_court, $email, $telephone, $logo)) {
                        $messageSuccess = "Entreprise ajoutée avec succès.";
                        $this->auditLog->logCreation($userId, 'entreprise', 'Succès');
                    } else {
                        $messageErreur = "Erreur lors de l'ajout de l'entreprise.";
                        $this->auditLog->logCreation($userId, 'entreprise', 'Erreur');
                    }
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
        $redirectPage = (string) ($get['page'] ?? 'parametres_generaux');
        if (!in_array($redirectPage, ['parametres_generaux', 'parametres_specifiques'], true)) {
            $redirectPage = 'parametres_generaux';
        }
        $redirectBase = '?page=' . $redirectPage . '&action=gestion_menus';

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
                header('Location: ' . $redirectBase . '&error=csrf');
                exit;
            }
            if (!$isEditable) {
                header('Location: ' . $redirectBase . '&error=readonly');
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
                    header('Location: ' . $redirectBase . '&success=category_created');
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
                    header('Location: ' . $redirectBase . '&success=category_updated');
                    exit;
                } elseif ($op === 'deactivate_category') {
                    $id = (int) ($post['id_categorie'] ?? 0);
                    $pdo->prepare("UPDATE categories_fonctionnalites SET actif = 0 WHERE id_categorie = ?")->execute([$id]);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 0 WHERE id_categorie = ?")->execute([$id]);
                    header('Location: ' . $redirectBase . '&success=category_deactivated');
                    exit;
                } elseif ($op === 'activate_category') {
                    $id = (int) ($post['id_categorie'] ?? 0);
                    $pdo->prepare("UPDATE categories_fonctionnalites SET actif = 1 WHERE id_categorie = ?")->execute([$id]);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 1 WHERE id_categorie = ?")->execute([$id]);
                    header('Location: ' . $redirectBase . '&success=category_activated');
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
                    header('Location: ' . $redirectBase . '&success=item_created');
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
                    header('Location: ' . $redirectBase . '&success=item_updated');
                    exit;
                } elseif ($op === 'deactivate_fonctionnalite') {
                    $id = (int) ($post['id_fonctionnalite'] ?? 0);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 0 WHERE id_fonctionnalite = ?")->execute([$id]);
                    header('Location: ' . $redirectBase . '&success=item_deactivated');
                    exit;
                } elseif ($op === 'activate_fonctionnalite') {
                    $id = (int) ($post['id_fonctionnalite'] ?? 0);
                    $pdo->prepare("UPDATE fonctionnalites SET actif = 1 WHERE id_fonctionnalite = ?")->execute([$id]);
                    header('Location: ' . $redirectBase . '&success=item_activated');
                    exit;
                }
            } catch (Throwable $e) {
                $errorMsg = urlencode($e->getMessage());
                header('Location: ' . $redirectBase . '&error=exception&msg=' . $errorMsg);
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

    //============================GESTION REFERENTIELS SIMPLES==================================
    public function gestionReferentielSimple(array $post, array $get, string $userId): array
    {
        $action = (string) ($get['action'] ?? '');
        $config = $this->getReferentielSimpleConfig($action);

        if ($config === null) {
            return [
                'item_a_modifier' => null,
                'listeReferentiel' => [],
                'messageErreur' => 'Référentiel non supporté.',
                'messageSuccess' => '',
            ];
        }

        $table = (string) $config['table'];
        $idColumn = (string) $config['id_column'];
        $idParam = (string) ($config['id_param'] ?? $idColumn);
        $idPostKey = (string) ($config['id_post_key'] ?? $idColumn);
        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        $requiredFields = is_array($config['required_fields'] ?? null) ? $config['required_fields'] : [];
        $boolFields = is_array($config['bool_fields'] ?? null) ? $config['bool_fields'] : [];
        $intFields = is_array($config['int_fields'] ?? null) ? $config['int_fields'] : [];
        $nullableFields = is_array($config['nullable_fields'] ?? null) ? $config['nullable_fields'] : [];
        $allowManualId = !empty($config['allow_manual_id']);
        $allowIdUpdate = !empty($config['allow_id_update']);
        $orderBy = (string) ($config['order_by'] ?? $idColumn . ' DESC');
        $entity = (string) ($config['audit_entity'] ?? $table);
        $addButton = (string) ($config['add_button'] ?? ('btn_add_' . $action));
        $editButton = (string) ($config['edit_button'] ?? ('btn_modifier_' . $action));

        $itemAModifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (!$this->tableExists($table)) {
            return [
                'item_a_modifier' => null,
                'listeReferentiel' => [],
                'messageErreur' => "La table '{$table}' n'existe pas dans la base de données.",
                'messageSuccess' => '',
            ];
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids']) && is_array($post['selected_ids'])) {
            $success = true;
            $sql = "DELETE FROM {$this->quoteIdentifier($table)} WHERE {$this->quoteIdentifier($idColumn)} = ?";
            $stmt = $this->db->prepare($sql);

            foreach ($post['selected_ids'] as $id) {
                if (!$stmt->execute([$id])) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = 'Éléments supprimés avec succès.';
                $this->auditLog->logSuppression($userId, $entity, 'Succès');
            } else {
                $messageErreur = 'Erreur lors de la suppression.';
                $this->auditLog->logSuppression($userId, $entity, 'Erreur');
            }
        } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (isset($post[$addButton]) || isset($post[$editButton]))) {
            $isUpdate = isset($post[$editButton]);
            $data = [];

            foreach ($fields as $field) {
                $fieldName = (string) $field;

                if (in_array($fieldName, $boolFields, true)) {
                    $rawBool = $post[$fieldName] ?? null;
                    if ($rawBool === null) {
                        $data[$fieldName] = 0;
                    } else {
                        $boolValue = is_string($rawBool) ? strtolower(trim($rawBool)) : (string) $rawBool;
                        $data[$fieldName] = in_array($boolValue, ['1', 'true', 'on', 'yes', 'oui'], true) ? 1 : 0;
                    }
                    continue;
                }

                $value = $post[$fieldName] ?? null;
                if (is_string($value)) {
                    $value = trim($value);
                }
                if (in_array($fieldName, $nullableFields, true) && $value === '') {
                    $value = null;
                }
                if (in_array($fieldName, $intFields, true) && $value !== null && $value !== '') {
                    $value = (int) $value;
                }
                $data[$fieldName] = $value;
            }

            foreach ($requiredFields as $required) {
                $requiredName = (string) $required;
                if (!array_key_exists($requiredName, $data) || $data[$requiredName] === null || $data[$requiredName] === '') {
                    $messageErreur = 'Veuillez renseigner tous les champs obligatoires.';
                    break;
                }
            }

            if ($messageErreur === '') {
                try {
                    if ($isUpdate) {
                        $currentId = trim((string) ($post[$idPostKey] ?? ''));
                        if ($currentId === '') {
                            throw new Exception('Identifiant de modification invalide.');
                        }

                        $updateData = $data;
                        if (!$allowIdUpdate) {
                            unset($updateData[$idColumn]);
                        }
                        if (empty($updateData)) {
                            throw new Exception('Aucune donnée à mettre à jour.');
                        }

                        $setParts = [];
                        $params = [];
                        foreach ($updateData as $column => $value) {
                            $paramName = ':u_' . $column;
                            $setParts[] = $this->quoteIdentifier((string) $column) . ' = ' . $paramName;
                            $params[$paramName] = $value;
                        }
                        $params[':current_id'] = $currentId;

                        $sql = "UPDATE {$this->quoteIdentifier($table)}
                                SET " . implode(', ', $setParts) . "
                                WHERE {$this->quoteIdentifier($idColumn)} = :current_id";
                        $stmt = $this->db->prepare($sql);

                        if ($stmt->execute($params)) {
                            $messageSuccess = 'Élément modifié avec succès.';
                            $this->auditLog->logModification($userId, $entity, 'Succès');
                        } else {
                            $messageErreur = 'Erreur lors de la modification.';
                            $this->auditLog->logModification($userId, $entity, 'Erreur');
                        }
                    } else {
                        $insertData = $data;
                        if (!$allowManualId) {
                            unset($insertData[$idColumn]);
                        }

                        $insertData = array_filter(
                            $insertData,
                            static fn($value) => $value !== null
                        );

                        if (empty($insertData)) {
                            throw new Exception('Aucune donnée à enregistrer.');
                        }

                        $columns = array_keys($insertData);
                        $placeholders = array_map(static fn($col) => ':i_' . $col, $columns);
                        $params = [];
                        foreach ($columns as $col) {
                            $params[':i_' . $col] = $insertData[$col];
                        }

                        $sql = "INSERT INTO {$this->quoteIdentifier($table)}
                                (" . implode(', ', array_map([$this, 'quoteIdentifier'], $columns)) . ")
                                VALUES (" . implode(', ', $placeholders) . ")";
                        $stmt = $this->db->prepare($sql);

                        if ($stmt->execute($params)) {
                            $messageSuccess = 'Élément ajouté avec succès.';
                            $this->auditLog->logCreation($userId, $entity, 'Succès');
                        } else {
                            $messageErreur = 'Erreur lors de l\'ajout.';
                            $this->auditLog->logCreation($userId, $entity, 'Erreur');
                        }
                    }
                } catch (Throwable $e) {
                    $messageErreur = 'Erreur base de données: ' . $e->getMessage();
                    $this->auditLog->logModification($userId, $entity, 'Erreur');
                }
            }
        }

        if (isset($get[$idParam]) && trim((string) $get[$idParam]) !== '') {
            $itemAModifier = $this->fetchReferentielRow($table, $idColumn, $get[$idParam]);
        }

        $result = [
            'item_a_modifier' => $itemAModifier,
            'listeReferentiel' => $this->fetchReferentielList($table, $orderBy),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];

        if ($action === 'maitre_stage') {
            $result['listeEntreprisesRef'] = $this->fetchReferentielOptions('entreprises', 'id_entreprise', 'lib_long_entreprise', 'lib_long_entreprise ASC');
            $result['listeFonctionsRef'] = $this->fetchReferentielOptions('fonction', 'id_fonction', 'lib_fonction', 'lib_fonction ASC');
        }

        return $result;
    }

    //============================GESTION BAREME CRITERE==================================
    public function gestionBaremeCritere(array $post, array $get, string $userId): array
    {
        $messageErreur = '';
        $messageSuccess = '';
        $baremeAModifier = null;

        if (!$this->tableExists('bareme_critere')) {
            return [
                'bareme_a_modifier' => null,
                'listeBaremes' => [],
                'listeAnneesBareme' => [],
                'listeCriteresBareme' => [],
                'messageErreur' => "La table 'bareme_critere' n'existe pas.",
                'messageSuccess' => '',
            ];
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids']) && is_array($post['selected_ids'])) {
            $success = true;
            $stmt = $this->db->prepare('DELETE FROM bareme_critere WHERE id_annee_acad = ? AND id_critere = ?');

            foreach ($post['selected_ids'] as $encodedId) {
                $decoded = $this->decodeBaremePk((string) $encodedId);
                if ($decoded === null || !$stmt->execute([$decoded['id_annee_acad'], $decoded['id_critere']])) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = 'Barèmes supprimés avec succès.';
                $this->auditLog->logSuppression($userId, 'bareme_critere', 'Succès');
            } else {
                $messageErreur = 'Erreur lors de la suppression des barèmes.';
                $this->auditLog->logSuppression($userId, 'bareme_critere', 'Erreur');
            }
        } elseif (isset($post['btn_add_bareme_critere']) || isset($post['btn_modifier_bareme_critere'])) {
            $idAnnee = (int) ($post['id_annee_acad'] ?? 0);
            $idCritere = trim((string) ($post['id_critere'] ?? ''));
            $bareme = (int) ($post['bareme'] ?? 0);

            if ($idAnnee <= 0 || $idCritere === '' || $bareme <= 0) {
                $messageErreur = 'Veuillez renseigner une année, un critère et un barème valides.';
            } else {
                try {
                    if (isset($post['btn_modifier_bareme_critere']) && !empty($post['bareme_pk'])) {
                        $oldPk = $this->decodeBaremePk((string) $post['bareme_pk']);
                        if ($oldPk === null) {
                            throw new Exception('Clé de barème invalide.');
                        }

                        $stmt = $this->db->prepare(
                            'UPDATE bareme_critere
                             SET id_annee_acad = ?, id_critere = ?, bareme = ?
                             WHERE id_annee_acad = ? AND id_critere = ?'
                        );
                        $ok = $stmt->execute([$idAnnee, $idCritere, $bareme, $oldPk['id_annee_acad'], $oldPk['id_critere']]);

                        if ($ok) {
                            $messageSuccess = 'Barème modifié avec succès.';
                            $this->auditLog->logModification($userId, 'bareme_critere', 'Succès');
                        } else {
                            $messageErreur = 'Erreur lors de la modification du barème.';
                            $this->auditLog->logModification($userId, 'bareme_critere', 'Erreur');
                        }
                    } else {
                        $stmt = $this->db->prepare(
                            'INSERT INTO bareme_critere (id_annee_acad, id_critere, bareme)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE bareme = VALUES(bareme)'
                        );
                        $ok = $stmt->execute([$idAnnee, $idCritere, $bareme]);

                        if ($ok) {
                            $messageSuccess = 'Barème ajouté avec succès.';
                            $this->auditLog->logCreation($userId, 'bareme_critere', 'Succès');
                        } else {
                            $messageErreur = 'Erreur lors de l\'ajout du barème.';
                            $this->auditLog->logCreation($userId, 'bareme_critere', 'Erreur');
                        }
                    }
                } catch (Throwable $e) {
                    $messageErreur = 'Erreur base de données: ' . $e->getMessage();
                    $this->auditLog->logModification($userId, 'bareme_critere', 'Erreur');
                }
            }
        }

        if (isset($get['bareme_pk']) && trim((string) $get['bareme_pk']) !== '') {
            $decoded = $this->decodeBaremePk((string) $get['bareme_pk']);
            if ($decoded !== null) {
                $stmt = $this->db->prepare(
                    'SELECT bc.id_annee_acad, bc.id_critere, bc.bareme
                     FROM bareme_critere bc
                     WHERE bc.id_annee_acad = ? AND bc.id_critere = ?'
                );
                $stmt->execute([$decoded['id_annee_acad'], $decoded['id_critere']]);
                $baremeAModifier = $stmt->fetch(PDO::FETCH_OBJ) ?: null;
                if ($baremeAModifier !== null) {
                    $baremeAModifier->bareme_pk = $this->encodeBaremePk((int) $baremeAModifier->id_annee_acad, (string) $baremeAModifier->id_critere);
                }
            }
        }

        $stmtList = $this->db->query(
            "SELECT
                bc.id_annee_acad,
                bc.id_critere,
                bc.bareme,
                " . $this->getCritereCodeSelect('ce') . ",
                ce.lib_critere,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS lib_annee
             FROM bareme_critere bc
             LEFT JOIN critere_evaluation ce ON ce.id_critere = bc.id_critere
             LEFT JOIN annee_academique aa ON aa.id_annee_acad = bc.id_annee_acad
             ORDER BY bc.id_annee_acad DESC, ce.lib_critere ASC"
        );
        $listeBaremes = $stmtList->fetchAll(PDO::FETCH_OBJ);
        foreach ($listeBaremes as $row) {
            $row->bareme_pk = $this->encodeBaremePk((int) ($row->id_annee_acad ?? 0), (string) ($row->id_critere ?? ''));
        }

        $listeAnnees = $this->db->query(
            'SELECT id_annee_acad, date_deb, date_fin
             FROM annee_academique
             ORDER BY id_annee_acad DESC'
        )->fetchAll(PDO::FETCH_OBJ);

        foreach ($listeAnnees as $annee) {
            $annee->lib_annee = date('Y', strtotime((string) ($annee->date_deb ?? ''))) . '-' . date('Y', strtotime((string) ($annee->date_fin ?? '')));
        }

        $listeCriteres = $this->db->query(
            'SELECT id_critere, ' . $this->getCritereCodeSelect() . ', lib_critere
             FROM critere_evaluation
             ORDER BY lib_critere ASC'
        )->fetchAll(PDO::FETCH_OBJ);

        return [
            'bareme_a_modifier' => $baremeAModifier,
            'listeBaremes' => $listeBaremes,
            'listeAnneesBareme' => $listeAnnees,
            'listeCriteresBareme' => $listeCriteres,
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }

    //============================GESTION FRAIS INSCRIPTION==================================
    public function gestionFraisInscription(array $post, array $get, string $userId): array
    {
        $messageErreur = '';
        $messageSuccess = '';
        $fraisAModifier = null;

        if (!$this->tableExists('frais_inscription')) {
            return [
                'frais_a_modifier' => null,
                'listeFraisInscription' => [],
                'listeAnneesFrais' => [],
                'listeNiveauxFrais' => [],
                'messageErreur' => "La table 'frais_inscription' n'existe pas.",
                'messageSuccess' => '',
            ];
        }

        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids']) && is_array($post['selected_ids'])) {
            $success = true;
            $stmt = $this->db->prepare('DELETE FROM frais_inscription WHERE id_niv_etude = ? AND id_annee_acad = ?');

            foreach ($post['selected_ids'] as $encodedId) {
                $decoded = $this->decodeFraisInscriptionPk((string) $encodedId);
                if ($decoded === null || !$stmt->execute([$decoded['id_niv_etude'], $decoded['id_annee_acad']])) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = "Frais d'inscription supprimés avec succès.";
                $this->auditLog->logSuppression($userId, 'frais_inscription', 'Succès');
            } else {
                $messageErreur = "Erreur lors de la suppression des frais d'inscription.";
                $this->auditLog->logSuppression($userId, 'frais_inscription', 'Erreur');
            }
        } elseif (isset($post['btn_add_frais_inscription']) || isset($post['btn_modifier_frais_inscription'])) {
            $idNiveau = trim((string) ($post['id_niv_etude'] ?? ''));
            $idAnnee = (int) ($post['id_annee_acad'] ?? 0);
            $montantRaw = $post['montant'] ?? null;
            if (is_string($montantRaw)) {
                $montantRaw = str_replace(',', '.', trim($montantRaw));
            }

            if ($idNiveau === '' || $idAnnee <= 0 || $montantRaw === null || $montantRaw === '' || !is_numeric((string) $montantRaw)) {
                $messageErreur = "Veuillez renseigner un niveau, une année et un montant valides.";
            } else {
                $montant = (float) $montantRaw;
                if ($montant < 0) {
                    $messageErreur = 'Le montant ne peut pas être négatif.';
                } else {
                    try {
                        if (isset($post['btn_modifier_frais_inscription']) && !empty($post['frais_pk'])) {
                            $oldPk = $this->decodeFraisInscriptionPk((string) $post['frais_pk']);
                            if ($oldPk === null) {
                                throw new Exception("Clé de frais d'inscription invalide.");
                            }

                            $stmt = $this->db->prepare(
                                'UPDATE frais_inscription
                                 SET id_niv_etude = ?, id_annee_acad = ?, montant = ?
                                 WHERE id_niv_etude = ? AND id_annee_acad = ?'
                            );
                            $ok = $stmt->execute([$idNiveau, $idAnnee, $montant, $oldPk['id_niv_etude'], $oldPk['id_annee_acad']]);

                            if ($ok) {
                                $messageSuccess = "Frais d'inscription modifiés avec succès.";
                                $this->auditLog->logModification($userId, 'frais_inscription', 'Succès');
                            } else {
                                $messageErreur = "Erreur lors de la modification des frais d'inscription.";
                                $this->auditLog->logModification($userId, 'frais_inscription', 'Erreur');
                            }
                        } else {
                            $stmtExists = $this->db->prepare(
                                'SELECT COUNT(*) FROM frais_inscription WHERE id_niv_etude = ? AND id_annee_acad = ?'
                            );
                            $stmtExists->execute([$idNiveau, $idAnnee]);
                            $alreadyExists = ((int) $stmtExists->fetchColumn()) > 0;

                            if ($alreadyExists) {
                                $stmt = $this->db->prepare(
                                    'UPDATE frais_inscription SET montant = ? WHERE id_niv_etude = ? AND id_annee_acad = ?'
                                );
                                $ok = $stmt->execute([$montant, $idNiveau, $idAnnee]);
                                if ($ok) {
                                    $messageSuccess = "Frais d'inscription mis à jour avec succès.";
                                    $this->auditLog->logModification($userId, 'frais_inscription', 'Succès');
                                } else {
                                    $messageErreur = "Erreur lors de la mise à jour des frais d'inscription.";
                                    $this->auditLog->logModification($userId, 'frais_inscription', 'Erreur');
                                }
                            } else {
                                $stmt = $this->db->prepare(
                                    'INSERT INTO frais_inscription (id_niv_etude, id_annee_acad, montant) VALUES (?, ?, ?)'
                                );
                                $ok = $stmt->execute([$idNiveau, $idAnnee, $montant]);
                                if ($ok) {
                                    $messageSuccess = "Frais d'inscription ajoutés avec succès.";
                                    $this->auditLog->logCreation($userId, 'frais_inscription', 'Succès');
                                } else {
                                    $messageErreur = "Erreur lors de l'ajout des frais d'inscription.";
                                    $this->auditLog->logCreation($userId, 'frais_inscription', 'Erreur');
                                }
                            }
                        }
                    } catch (Throwable $e) {
                        $messageErreur = 'Erreur base de données: ' . $e->getMessage();
                        $this->auditLog->logModification($userId, 'frais_inscription', 'Erreur');
                    }
                }
            }
        }

        if (isset($get['frais_pk']) && trim((string) $get['frais_pk']) !== '') {
            $decoded = $this->decodeFraisInscriptionPk((string) $get['frais_pk']);
            if ($decoded !== null) {
                $stmt = $this->db->prepare(
                    'SELECT fi.id_niv_etude, fi.id_annee_acad, fi.montant,
                            ne.lib_niv_etude,
                            CONCAT(YEAR(aa.date_deb), "-", YEAR(aa.date_fin)) AS lib_annee
                     FROM frais_inscription fi
                     LEFT JOIN niveau_etude ne ON ne.id_niv_etude = fi.id_niv_etude
                     LEFT JOIN annee_academique aa ON aa.id_annee_acad = fi.id_annee_acad
                     WHERE fi.id_niv_etude = ? AND fi.id_annee_acad = ?
                     LIMIT 1'
                );
                $stmt->execute([$decoded['id_niv_etude'], $decoded['id_annee_acad']]);
                $fraisAModifier = $stmt->fetch(PDO::FETCH_OBJ) ?: null;
                if ($fraisAModifier !== null) {
                    $fraisAModifier->frais_pk = $this->encodeFraisInscriptionPk(
                        (string) ($fraisAModifier->id_niv_etude ?? ''),
                        (int) ($fraisAModifier->id_annee_acad ?? 0)
                    );
                }
            }
        }

        $stmtList = $this->db->query(
            'SELECT fi.id_niv_etude, fi.id_annee_acad, fi.montant,
                    ne.lib_niv_etude,
                    CONCAT(YEAR(aa.date_deb), "-", YEAR(aa.date_fin)) AS lib_annee
             FROM frais_inscription fi
             LEFT JOIN niveau_etude ne ON ne.id_niv_etude = fi.id_niv_etude
             LEFT JOIN annee_academique aa ON aa.id_annee_acad = fi.id_annee_acad
             ORDER BY fi.id_annee_acad DESC, ne.lib_niv_etude ASC, fi.id_niv_etude ASC'
        );
        $listeFrais = $stmtList ? $stmtList->fetchAll(PDO::FETCH_OBJ) : [];
        foreach ($listeFrais as $row) {
            $row->frais_pk = $this->encodeFraisInscriptionPk(
                (string) ($row->id_niv_etude ?? ''),
                (int) ($row->id_annee_acad ?? 0)
            );
        }

        $listeAnnees = $this->db->query(
            'SELECT id_annee_acad, date_deb, date_fin
             FROM annee_academique
             ORDER BY id_annee_acad DESC'
        )->fetchAll(PDO::FETCH_OBJ);
        foreach ($listeAnnees as $annee) {
            $annee->lib_annee = date('Y', strtotime((string) ($annee->date_deb ?? ''))) . '-' . date('Y', strtotime((string) ($annee->date_fin ?? '')));
        }

        $listeNiveaux = $this->db->query(
            'SELECT id_niv_etude, lib_niv_etude
             FROM niveau_etude
             ORDER BY lib_niv_etude ASC'
        )->fetchAll(PDO::FETCH_OBJ);

        return [
            'frais_a_modifier' => $fraisAModifier,
            'listeFraisInscription' => $listeFrais,
            'listeAnneesFrais' => $listeAnnees,
            'listeNiveauxFrais' => $listeNiveaux,
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }

    public function gestionSchemaTables(array $get): array
    {
        $tables = [];
        $columnsByTable = [];
        $messageErreur = '';

        $managedColumnsMap = $this->getManagedTableColumnsMap();
        $managedActionMap = $this->getManagedTableActionMap();

        try {
            $stmtTables = $this->db->query('SHOW TABLES');
            $tableRows = $stmtTables ? $stmtTables->fetchAll(PDO::FETCH_NUM) : [];

            foreach ($tableRows as $tableRow) {
                $tableName = trim((string) ($tableRow[0] ?? ''));
                if ($tableName === '') {
                    continue;
                }

                $stmtColumns = $this->db->query('SHOW COLUMNS FROM ' . $this->quoteIdentifier($tableName));
                $columnRows = $stmtColumns ? $stmtColumns->fetchAll(PDO::FETCH_ASSOC) : [];
                $columnsByTable[$tableName] = $columnRows;

                $dbColumns = array_values(array_filter(array_map(static function ($column): string {
                    return trim((string) ($column['Field'] ?? ''));
                }, $columnRows), static function (string $column): bool {
                    return $column !== '';
                }));

                $configuredColumns = array_values(array_unique($managedColumnsMap[$tableName] ?? []));
                $coveredColumns = array_values(array_intersect($dbColumns, $configuredColumns));
                $missingColumns = array_values(array_diff($dbColumns, $coveredColumns));

                $tables[] = [
                    'table_name' => $tableName,
                    'columns_count' => count($dbColumns),
                    'is_managed' => isset($managedColumnsMap[$tableName]),
                    'managed_action' => $managedActionMap[$tableName] ?? '',
                    'covered_count' => count($coveredColumns),
                    'missing_count' => count($missingColumns),
                    'missing_columns_preview' => implode(', ', array_slice($missingColumns, 0, 5)),
                ];
            }

            usort($tables, static function (array $a, array $b): int {
                return strcmp((string) ($a['table_name'] ?? ''), (string) ($b['table_name'] ?? ''));
            });
        } catch (Throwable $e) {
            $messageErreur = 'Erreur lors du chargement de la structure des tables: ' . $e->getMessage();
        }

        $selectedTable = trim((string) ($get['table'] ?? ''));
        if ($selectedTable === '' && !empty($tables)) {
            $selectedTable = (string) ($tables[0]['table_name'] ?? '');
        }

        $selectedColumns = $columnsByTable[$selectedTable] ?? [];
        $coveredSet = array_flip($managedColumnsMap[$selectedTable] ?? []);
        foreach ($selectedColumns as &$column) {
            $field = trim((string) ($column['Field'] ?? ''));
            $column['is_covered'] = isset($coveredSet[$field]);
        }
        unset($column);

        return [
            'schemaTables' => $tables,
            'schemaSelectedTable' => $selectedTable,
            'schemaSelectedColumns' => $selectedColumns,
            'schemaManagedActionMap' => $managedActionMap,
            'messageErreur' => $messageErreur,
            'messageSuccess' => '',
        ];
    }

    private function getManagedTableActionMap(): array
    {
        return [
            'action' => 'actions',
            'annee_academique' => 'annees_academiques',
            'app_settings' => 'app_settings',
            'bareme_critere' => 'bareme_critere',
            'categories_fonctionnalites' => 'gestion_menus',
            'critere_evaluation' => 'criteres_evaluation',
            'decisions_jury' => 'decisions_jury',
            'domaine' => 'domaine',
            'entreprises' => 'entreprises',
            'etablissement_origine' => 'etablissement_origine',
            'filiere' => 'filieres',
            'frais_inscription' => 'frais_inscription',
            'fonction' => 'fonctions',
            'fonctionnalites' => 'gestion_menus',
            'genre' => 'genre',
            'grade' => 'grades',
            'groupe_utilisateur' => 'fonction_utilisateur',
            'maitre_de_stage' => 'maitre_stage',
            'mentions' => 'mentions',
            'messages' => 'messages',
            'mode_paiement' => 'mode_paiement',
            'niveau_acces_donnees' => 'niveaux_acces',
            'niveau_approbation' => 'niveaux_approbation',
            'niveau_etude' => 'niveaux_etude',
            'permissions' => 'gestion_attribution',
            'qualite_jury' => 'qualite_jury',
            'route_actions' => 'gestion_menus',
            'salles' => 'salles',
            'semestre' => 'semestres',
            'session' => 'session',
            'specialite' => 'specialites',
            'statut_jury' => 'statut_jury',
            'statut_reclamation' => 'statut_reclamation',
            'type_enseignant' => 'type_enseignant',
            'type_utilisateur' => 'fonction_utilisateur',
        ];
    }

    private function getManagedTableColumnsMap(): array
    {
        return [
            'action' => ['id_action', 'lib_action'],
            'annee_academique' => ['id_annee_acad', 'date_deb', 'date_fin'],
            'app_settings' => ['setting_key', 'setting_value', 'is_sensitive'],
            'bareme_critere' => ['id_annee_acad', 'id_critere', 'bareme'],
            'categories_fonctionnalites' => ['id_categorie', 'code_categorie', 'lib_categorie', 'description_categorie', 'icone_categorie', 'ordre_categorie', 'actif'],
            'critere_evaluation' => ['id_critere', 'code_critere', 'lib_critere', 'bareme'],
            'decisions_jury' => ['id_decision', 'lib_decision', 'description', 'actif'],
            'domaine' => ['id_domaine', 'lib_domaine'],
            'entreprises' => ['id_entreprise', 'lib_long_entreprise', 'lib_court_en', 'logo', 'email', 'telephone'],
            'etablissement_origine' => ['id_etablissement', 'libelle_long', 'libelle_court'],
            'filiere' => ['id_filiere', 'lib_filiere'],
            'frais_inscription' => ['id_niv_etude', 'id_annee_acad', 'montant'],
            'fonction' => ['id_fonction', 'lib_fonction', 'origine_entreprise'],
            'fonctionnalites' => ['id_fonctionnalite', 'id_categorie', 'code_fonctionnalite', 'lib_fonctionnalite', 'label_fonctionnalite', 'description_fonctionnalite', 'url_fonctionnalite', 'icone_fonctionnalite', 'ordre_fonctionnalite', 'est_sous_page', 'page_parente', 'actif'],
            'genre' => ['id_genre', 'libelle_genre'],
            'grade' => ['id_grade', 'lib_grade'],
            'groupe_utilisateur' => ['id_GU', 'lib_GU', 'id_type_utilisateur'],
            'maitre_de_stage' => ['id_maitre_stage', 'Nom', 'prenom', 'email', 'telephone', 'id_entreprise', 'id_fonction'],
            'mentions' => ['id_mention', 'lib_mention', 'actif'],
            'messages' => ['id_message', 'contenu_message', 'lib_message', 'type_message'],
            'mode_paiement' => ['id_mode_paiement', 'code_mode_paiement', 'libelle_mode_paement'],
            'niveau_acces_donnees' => ['id_niveau_acces_donnees', 'lib_niveau_acces_donnees'],
            'niveau_approbation' => ['id_niveau_approbation', 'lib_niveau_approbation'],
            'niveau_etude' => ['id_niv_etude', 'lib_niv_etude'],
            'permissions' => ['id_GU', 'id_fonctionnalite', 'peut_voir', 'peut_creer', 'peut_modifier', 'peut_supprimer'],
            'qualite_jury' => ['id_role_jury', 'lib_role'],
            'route_actions' => ['id_route_action', 'id_fonctionnalite', 'route_pattern', 'http_method', 'action_crud', 'is_public', 'actif'],
            'salles' => ['id_salle', 'nom_salle', 'capacite'],
            'semestre' => ['id_semestre', 'code_semestre', 'lib_semestre'],
            'session' => ['id_session', 'lib_session'],
            'specialite' => ['id_specialite', 'lib_specialite'],
            'statut_jury' => ['id_statut_jury', 'lib_statut_jury'],
            'statut_reclamation' => ['id_statut_reclamation', 'libelle_statut_reclamation'],
            'type_enseignant' => ['id_type_enseignant', 'libelle'],
            'type_utilisateur' => ['id_type_utilisateur', 'lib_type_utilisateur'],
        ];
    }

    private function getReferentielSimpleConfig(string $action): ?array
    {
        $configs = [
            'app_settings' => [
                'table' => 'app_settings',
                'id_column' => 'setting_key',
                'id_post_key' => 'current_setting_key',
                'id_param' => 'setting_key',
                'fields' => ['setting_key', 'setting_value', 'is_sensitive'],
                'required_fields' => ['setting_key', 'setting_value'],
                'bool_fields' => ['is_sensitive'],
                'allow_manual_id' => true,
                'allow_id_update' => true,
                'order_by' => 'setting_key ASC',
                'audit_entity' => 'app_settings',
            ],
            'genre' => [
                'table' => 'genre',
                'id_column' => 'id_genre',
                'id_param' => 'id_genre',
                'fields' => ['libelle_genre'],
                'required_fields' => ['libelle_genre'],
                'order_by' => 'libelle_genre ASC',
                'audit_entity' => 'genre',
            ],
            'decisions_jury' => [
                'table' => 'decisions_jury',
                'id_column' => 'id_decision',
                'id_param' => 'id_decision',
                'fields' => ['lib_decision', 'description', 'actif'],
                'required_fields' => ['lib_decision'],
                'bool_fields' => ['actif'],
                'nullable_fields' => ['description'],
                'order_by' => 'lib_decision ASC',
                'audit_entity' => 'decisions_jury',
            ],
            'etablissement_origine' => [
                'table' => 'etablissement_origine',
                'id_column' => 'id_etablissement',
                'id_param' => 'id_etablissement',
                'fields' => ['libelle_long', 'libelle_court'],
                'required_fields' => ['libelle_long', 'libelle_court'],
                'order_by' => 'libelle_long ASC',
                'audit_entity' => 'etablissement_origine',
            ],
            'session' => [
                'table' => 'session',
                'id_column' => 'id_session',
                'id_param' => 'id_session',
                'fields' => ['lib_session'],
                'required_fields' => ['lib_session'],
                'order_by' => 'lib_session ASC',
                'audit_entity' => 'session',
            ],
            'mode_paiement' => [
                'table' => 'mode_paiement',
                'id_column' => 'id_mode_paiement',
                'id_param' => 'id_mode_paiement',
                'fields' => ['code_mode_paiement', 'libelle_mode_paement'],
                'required_fields' => ['code_mode_paiement', 'libelle_mode_paement'],
                'order_by' => 'libelle_mode_paement ASC',
                'audit_entity' => 'mode_paiement',
            ],
            'statut_reclamation' => [
                'table' => 'statut_reclamation',
                'id_column' => 'id_statut_reclamation',
                'id_param' => 'id_statut_reclamation',
                'fields' => ['libelle_statut_reclamation'],
                'required_fields' => ['libelle_statut_reclamation'],
                'order_by' => 'libelle_statut_reclamation ASC',
                'audit_entity' => 'statut_reclamation',
            ],
            'domaine' => [
                'table' => 'domaine',
                'id_column' => 'id_domaine',
                'id_param' => 'id_domaine',
                'fields' => ['lib_domaine'],
                'required_fields' => ['lib_domaine'],
                'order_by' => 'lib_domaine ASC',
                'audit_entity' => 'domaine',
            ],
            'mentions' => [
                'table' => 'mentions',
                'id_column' => 'id_mention',
                'id_param' => 'id_mention',
                'fields' => ['lib_mention', 'actif'],
                'required_fields' => ['lib_mention'],
                'bool_fields' => ['actif'],
                'order_by' => 'lib_mention ASC',
                'audit_entity' => 'mentions',
            ],
            'filieres' => [
                'table' => 'filiere',
                'id_column' => 'id_filiere',
                'id_param' => 'id_filiere',
                'fields' => ['lib_filiere'],
                'required_fields' => ['lib_filiere'],
                'order_by' => 'lib_filiere ASC',
                'audit_entity' => 'filiere',
            ],
            'qualite_jury' => [
                'table' => 'qualite_jury',
                'id_column' => 'id_role_jury',
                'id_param' => 'id_role_jury',
                'fields' => ['id_role_jury', 'lib_role'],
                'required_fields' => ['id_role_jury', 'lib_role'],
                'allow_manual_id' => true,
                'order_by' => 'lib_role ASC',
                'audit_entity' => 'qualite_jury',
            ],
            'maitre_stage' => [
                'table' => 'maitre_de_stage',
                'id_column' => 'id_maitre_stage',
                'id_param' => 'id_maitre_stage',
                'fields' => ['id_maitre_stage', 'Nom', 'prenom', 'email', 'telephone', 'id_entreprise', 'id_fonction'],
                'required_fields' => ['id_maitre_stage', 'Nom', 'prenom', 'id_entreprise'],
                'allow_manual_id' => true,
                'allow_id_update' => false,
                'nullable_fields' => ['email', 'telephone', 'id_fonction'],
                'int_fields' => ['id_entreprise'],
                'order_by' => 'Nom ASC, prenom ASC',
                'audit_entity' => 'maitre_de_stage',
            ],
            'entreprises' => [
                'table' => 'entreprises',
                'id_column' => 'id_entreprise',
                'id_param' => 'id_entreprise',
                'fields' => ['lib_long_entreprise', 'lib_court_en', 'logo', 'email', 'telephone'],
                'required_fields' => ['lib_long_entreprise', 'lib_court_en'],
                'order_by' => 'lib_long_entreprise ASC',
                'audit_entity' => 'entreprise',
            ],
            'specialites' => [
                'table' => 'specialite',
                'id_column' => 'id_specialite',
                'id_param' => 'id_specialite',
                'fields' => ['lib_specialite'],
                'required_fields' => ['lib_specialite'],
                'order_by' => 'lib_specialite ASC',
                'audit_entity' => 'specialite',
            ],
            'actions' => [
                'table' => 'action',
                'id_column' => 'id_action',
                'id_param' => 'id_action',
                'fields' => ['lib_action'],
                'required_fields' => ['lib_action'],
                'order_by' => 'lib_action ASC',
                'audit_entity' => 'action',
            ],
            'fonctions' => [
                'table' => 'fonction',
                'id_column' => 'id_fonction',
                'id_param' => 'id_fonction',
                'fields' => ['id_fonction', 'lib_fonction', 'origine_entreprise'],
                'required_fields' => ['id_fonction', 'lib_fonction'],
                'bool_fields' => ['origine_entreprise'],
                'allow_manual_id' => true,
                'order_by' => 'lib_fonction ASC',
                'audit_entity' => 'fonction',
            ],
            'messages' => [
                'table' => 'messages',
                'id_column' => 'id_message',
                'id_param' => 'id_message',
                'fields' => ['contenu_message', 'lib_message', 'type_message'],
                'required_fields' => ['contenu_message', 'lib_message', 'type_message'],
                'order_by' => 'lib_message ASC',
                'audit_entity' => 'messages',
            ],
            'type_enseignant' => [
                'table' => 'type_enseignant',
                'id_column' => 'id_type_enseignant',
                'id_param' => 'id_type_enseignant',
                'fields' => ['libelle'],
                'required_fields' => ['libelle'],
                'order_by' => 'libelle ASC',
                'audit_entity' => 'type_enseignant',
            ],
        ];

        return $configs[$action] ?? null;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        $stmt = $this->db->prepare('SHOW COLUMNS FROM ' . $this->quoteIdentifier($table) . ' LIKE ?');
        $stmt->execute([$column]);
        return (bool) $stmt->fetchColumn();
    }

    private function getCritereCodeSelect(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        if ($this->columnExists('critere_evaluation', 'code_critere')) {
            return $prefix . 'code_critere AS code_critere';
        }

        return $prefix . 'id_critere AS code_critere';
    }

    private function fetchReferentielRow(string $table, string $idColumn, $id): ?object
    {
        $sql = "SELECT * FROM {$this->quoteIdentifier($table)}
                WHERE {$this->quoteIdentifier($idColumn)} = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        return $row !== false ? $row : null;
    }

    private function fetchReferentielList(string $table, string $orderBy): array
    {
        $sql = "SELECT * FROM {$this->quoteIdentifier($table)} ORDER BY {$orderBy}";
        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
    }

    private function fetchReferentielOptions(string $table, string $idColumn, string $labelColumn, string $orderBy = ''): array
    {
        if (!$this->tableExists($table)) {
            return [];
        }

        $orderSql = $orderBy !== '' ? $orderBy : $labelColumn . ' ASC';
        $sql = "SELECT {$this->quoteIdentifier($idColumn)} AS id, {$this->quoteIdentifier($labelColumn)} AS label
                FROM {$this->quoteIdentifier($table)}
                ORDER BY {$orderSql}";

        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new Exception("Identifiant SQL invalide: {$identifier}");
        }

        return '`' . $identifier . '`';
    }

    private function encodeBaremePk(int $idAnnee, string $idCritere): string
    {
        $idCritere = trim($idCritere);
        if ($idCritere === '') {
            throw new Exception('Critère invalide pour la clé de barème.');
        }
        return $idAnnee . ':' . $idCritere;
    }

    private function decodeBaremePk(string $encoded): ?array
    {
        $parts = explode(':', trim($encoded), 2);
        if (count($parts) !== 2) {
            return null;
        }

        $idAnnee = (int) $parts[0];
        $idCritere = trim($parts[1]);
        if ($idAnnee <= 0 || $idCritere === '') {
            return null;
        }

        return [
            'id_annee_acad' => $idAnnee,
            'id_critere' => $idCritere,
        ];
    }

    private function encodeFraisInscriptionPk(string $idNiveau, int $idAnnee): string
    {
        $idNiveau = trim($idNiveau);
        if ($idNiveau === '' || $idAnnee <= 0) {
            throw new Exception("Clé de frais d'inscription invalide.");
        }

        return $idNiveau . ':' . $idAnnee;
    }

    private function decodeFraisInscriptionPk(string $encoded): ?array
    {
        $parts = explode(':', trim($encoded), 2);
        if (count($parts) !== 2) {
            return null;
        }

        $idNiveau = trim((string) $parts[0]);
        $idAnnee = (int) $parts[1];
        if ($idNiveau === '' || $idAnnee <= 0) {
            return null;
        }

        return [
            'id_niv_etude' => $idNiveau,
            'id_annee_acad' => $idAnnee,
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
