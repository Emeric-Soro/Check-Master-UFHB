<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . '/../models/AuditLog.php';

use Etudiant;
use AuditLog;
use PDO;
use PDOException;

class EtudiantService
{
    private $db;
    private $etudiant;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
    }

    public function getNiveauxEtude()
    {
        try {
            $query = "SELECT id_niv_etude, lib_niv_etude FROM niveau_etude ORDER BY lib_niv_etude";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des niveaux : " . $e->getMessage());
            return [];
        }
    }

    public function getAnneesAcademiques()
    {
        try {
            $query = "SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des années académiques : " . $e->getMessage());
            return [];
        }
    }

    public function getEtudiantById($num_etu)
    {
        return $this->etudiant->getEtudiantById($num_etu);
    }

    public function getAllEtudiants()
    {
        return $this->etudiant->getAllEtudiants();
    }

    public function ajouterEtudiant($data, $idUtilisateur)
    {
        if (
            empty($data['num_etu']) || empty($data['nom_etu']) ||
            empty($data['prenom_etu']) || empty($data['date_naiss_etu']) ||
            empty($data['genre_etu']) || empty($data['email_etu'])
        ) {
            return ['success' => false, 'message' => "Les champs N° Étudiant, Nom, Prénom, Date de naissance, Genre et Email sont obligatoires."];
        }

        $num_etu = trim($data['num_etu']);

        if ($this->etudiant->getEtudiantById($num_etu)) {
            return ['success' => false, 'message' => "Ce numéro étudiant existe déjà. Veuillez en choisir un autre."];
        }

        $promotion_etu = !empty($data['promotion_etu']) ? $data['promotion_etu'] : date('Y') . '-' . (date('Y') + 1);
        $nom_etu = trim($data['nom_etu']);
        $prenom_etu = trim($data['prenom_etu']);
        $date_naiss_etu = $data['date_naiss_etu'];
        $genre_etu = $data['genre_etu'];
        $email_etu = trim($data['email_etu']);
        
        // CORRECTION DE TYPAGE ET GESTION EXPLICITE DES NULLS
        $id_niveau = (isset($data['id_niveau']) && trim($data['id_niveau']) !== '') ? (int) trim($data['id_niveau']) : null;
        $id_annee_acad = (isset($data['id_annee_acad']) && trim($data['id_annee_acad']) !== '') ? (int) trim($data['id_annee_acad']) : null;
        $identifiant_mesrs = !empty($data['identifiant_mesrs']) ? trim($data['identifiant_mesrs']) : null;

        if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "L'adresse email n'est pas valide."];
        }

        if ($this->etudiant->ajouterEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau, $id_annee_acad, $identifiant_mesrs)) {
            $this->auditLog->logCreation($idUtilisateur, "etudiants", "Succès");
            return ['success' => true, 'message' => "Étudiant ajouté avec succès. Numéro étudiant : " . $num_etu];
        } else {
            $this->auditLog->logCreation($idUtilisateur, "etudiants", "Erreur");
            return ['success' => false, 'message' => "Erreur lors de l'ajout de l'étudiant."];
        }
    }

    public function modifierEtudiant($data, $idUtilisateur)
    {
        if (
            empty($data['old_num_etu']) || empty($data['num_etu']) || empty($data['nom_etu']) ||
            empty($data['prenom_etu']) || empty($data['date_naiss_etu']) ||
            empty($data['genre_etu']) || empty($data['email_etu'])
        ) {
            return ['success' => false, 'message' => "Les champs Nom, Prénom, Date de naissance, Genre et Email sont obligatoires."];
        }

        $old_num_etu = trim($data['old_num_etu']);
        $num_etu = trim($data['num_etu']);

        if ($old_num_etu !== $num_etu) {
            if ($this->etudiant->getEtudiantById($num_etu)) {
                return ['success' => false, 'message' => "Ce numéro étudiant existe déjà. Veuillez en choisir un autre."];
            }
        }

        $nom_etu = trim($data['nom_etu']);
        $prenom_etu = trim($data['prenom_etu']);
        $date_naiss_etu = $data['date_naiss_etu'];
        $genre_etu = $data['genre_etu'];
        $email_etu = trim($data['email_etu']);
        $promotion_etu = !empty($data['promotion_etu']) ? $data['promotion_etu'] : null;
        
        // CORRECTION DE TYPAGE ET GESTION EXPLICITE DES NULLS
        $id_niveau = (isset($data['id_niveau']) && trim($data['id_niveau']) !== '') ? (int) trim($data['id_niveau']) : null;
        $id_annee_acad = (isset($data['id_annee_acad']) && trim($data['id_annee_acad']) !== '') ? (int) trim($data['id_annee_acad']) : null;
        $identifiant_mesrs = !empty($data['num_ident_etud']) ? trim($data['num_ident_etud']) : null;

        if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "L'adresse email n'est pas valide."];
        }

        if ($this->etudiant->modifierEtudiant($old_num_etu, $num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau, $id_annee_acad, $identifiant_mesrs)) {
            $this->auditLog->logModification($idUtilisateur, 'etudiants', 'Succès');
            return ['success' => true, 'message' => "Étudiant modifié avec succès."];
        } else {
            $this->auditLog->logModification($idUtilisateur, 'etudiants', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de la modification de l'étudiant."];
        }
    }

    public function supprimerEtudiants($ids, $idUtilisateur)
    {
        $success = true;

        foreach ($ids as $num_etu) {
            if (!$this->etudiant->supprimerEtudiant($num_etu)) {
                $success = false;
                break;
            }
        }

        if ($success) {
            foreach ($ids as $num_etu) {
                $this->auditLog->logSuppression($idUtilisateur, 'etudiants', 'Succès');
            }
            return ['success' => true, 'message' => "Étudiants supprimés avec succès."];
        } else {
            return ['success' => false, 'message' => "Erreur lors de la suppression des étudiants."];
        }
    }
}
