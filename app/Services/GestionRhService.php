<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/PersAdmin.php";
require_once __DIR__ . "/../models/Grade.php";
require_once __DIR__ . "/../models/Fonction.php";
require_once __DIR__ . "/../models/Specialite.php";
require_once __DIR__ . "/../models/AuditLog.php";

use Enseignant;
use PersAdmin;
use Grade;
use Fonction;
use Specialite;
use AuditLog;

/**
 * Service métier de la gestion des ressources humaines
 *
 * Contient toute la logique métier pour :
 * - CRUD enseignants (ajout, modification, suppression multiple)
 * - CRUD personnel administratif (ajout, modification, suppression multiple)
 * - Audit logging des opérations
 * - Récupération des listes de référence (grades, fonctions, spécialités)
 */
class GestionRhService
{
    /** @var Enseignant */
    private $enseignantModel;

    /** @var PersAdmin */
    private $persAdminModel;

    /** @var Grade */
    private $gradeModel;

    /** @var Fonction */
    private $fonctionModel;

    /** @var Specialite */
    private $specialiteModel;

    /** @var AuditLog */
    private $auditLog;

    /**
     * @param \PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->enseignantModel = new Enseignant($db);
        $this->persAdminModel = new PersAdmin($db);
        $this->gradeModel = new Grade($db);
        $this->fonctionModel = new Fonction($db);
        $this->specialiteModel = new Specialite($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Ajoute ou modifie un enseignant
     *
     * @param array $data Données du formulaire enseignant
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveEnseignant(array $data, int $userId): array
    {
        $nom = $data['nom'] ?? '';
        $prenom = $data['prenom'] ?? '';
        $email = $data['email'] ?? '';
        $id_grade = $data['id_grade'] ?? null;
        $id_specialite = $data['id_specialite'] ?? null;
        $id_fonction = $data['id_fonction'] ?? null;
        $date_grade = $data['date_grade'] ?? null;
        $date_fonction = $data['date_fonction'] ?? null;
        $type_enseignant = $data['type_enseignant'];

        if (!empty($data['id_enseignant'])) {
            // Modification
            if ($this->enseignantModel->modifierEnseignant(
                $data['id_enseignant'], $nom, $prenom, $email,
                $id_grade, $id_specialite, $id_fonction, $date_grade, $date_fonction, $type_enseignant
            )) {
                $this->auditLog->logModification($userId, 'enseignant', 'Succès');
                return ['success' => true, 'message' => 'Enseignant modifié avec succès.'];
            }
            $this->auditLog->logModification($userId, 'enseignant', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de la modification de l'enseignant."];
        }

        // Ajout
        $id_enseignant = $data['id_enseignant'] ?? null;
        if ($this->enseignantModel->ajouterEnseignant(
            $id_enseignant, $nom, $prenom, $email, $id_grade,
            $id_specialite, $id_fonction, $date_grade, $date_fonction, $type_enseignant
        )) {
            $this->auditLog->logCreation($userId, 'enseignant', 'Succès');
            return ['success' => true, 'message' => 'Enseignant ajouté avec succès.'];
        }
        $this->auditLog->logCreation($userId, 'enseignant', 'Erreur');
        return ['success' => false, 'message' => "Erreur lors de l'ajout de l'enseignant."];
    }

    /**
     * Supprime plusieurs enseignants
     *
     * @param array $ids Liste d'identifiants à supprimer
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMultipleEnseignants(array $ids, int $userId): array
    {
        foreach ($ids as $id) {
            if (!$this->enseignantModel->supprimerEnseignant($id)) {
                $this->auditLog->logSuppression($userId, 'enseignant', 'Erreur');
                return ['success' => false, 'message' => "Erreur lors de la suppression des enseignants."];
            }
        }
        $this->auditLog->logSuppression($userId, 'enseignant', 'Succès');
        return ['success' => true, 'message' => 'Enseignants supprimés avec succès.'];
    }

    /**
     * Récupère un enseignant par son identifiant
     *
     * @param int $id
     * @return mixed
     */
    public function getEnseignantById($id)
    {
        return $this->enseignantModel->getEnseignantById($id);
    }

    /**
     * Ajoute ou modifie un membre du personnel administratif
     *
     * @param array $data Données du formulaire
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function savePersAdmin(array $data, int $userId): array
    {
        $nom = $data['nom'] ?? '';
        $prenom = $data['prenom'] ?? '';
        $email = $data['email'] ?? '';
        $telephone = $data['telephone'] ?? '';
        $poste = $data['poste'] ?? '';
        $date_embauche = $data['date_embauche'] ?? '';

        if (!empty($data['id_pers_admin'])) {
            // Modification
            if ($this->persAdminModel->modifierPersAdmin(
                $data['id_pers_admin'], $nom, $prenom, $email, $telephone, $poste, $date_embauche
            )) {
                $this->auditLog->logModification($userId, 'pers_admin', 'Succès');
                return ['success' => true, 'message' => 'Personnel administratif modifié avec succès.'];
            }
            $this->auditLog->logModification($userId, 'pers_admin', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de la modification du personnel administratif."];
        }

        // Ajout
        if ($this->persAdminModel->ajouterPersAdmin(
            $nom, $prenom, $email, $telephone, $poste, $date_embauche
        )) {
            $this->auditLog->logCreation($userId, 'pers_admin', 'Succès');
            return ['success' => true, 'message' => 'Personnel administratif ajouté avec succès.'];
        }
        $this->auditLog->logCreation($userId, 'pers_admin', 'Erreur');
        return ['success' => false, 'message' => "Erreur lors de l'ajout du personnel administratif."];
    }

    /**
     * Supprime plusieurs membres du personnel administratif
     *
     * @param array $ids Liste d'identifiants à supprimer
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMultiplePersAdmin(array $ids, int $userId): array
    {
        foreach ($ids as $id) {
            if (!$this->persAdminModel->supprimerPersAdmin($id)) {
                $this->auditLog->logSuppression($userId, 'pers_admin', 'Erreur');
                return ['success' => false, 'message' => "Erreur lors de la suppression du personnel administratif."];
            }
        }
        $this->auditLog->logSuppression($userId, 'pers_admin', 'Succès');
        return ['success' => true, 'message' => 'Personnel administratif supprimé avec succès.'];
    }

    /**
     * Récupère un membre du personnel administratif par son identifiant
     *
     * @param int $id
     * @return mixed
     */
    public function getPersAdminById($id)
    {
        return $this->persAdminModel->getPersAdminById($id);
    }

    /**
     * Récupère toutes les listes de référence nécessaires aux vues
     *
     * @return array Tableau associatif des listes
     */
    public function getReferenceLists(): array
    {
        return [
            'listeEnseignants' => $this->enseignantModel->getAllEnseignants(),
            'listePersAdmin' => $this->persAdminModel->getAllPersAdmin(),
            'listeGrades' => $this->gradeModel->getAllGrades(),
            'listeFonctions' => $this->fonctionModel->getAllFonctions(),
            'listeSpecialites' => $this->specialiteModel->getAllSpecialites(),
        ];
    }
}