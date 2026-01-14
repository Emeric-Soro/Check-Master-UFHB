<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class PersAdmin
{
    private $pdo;
    private $logger;
    private $id_pers_admin;
    private $nom_pers_admin;
    private $prenom_pers_admin;
    private $email_pers_admin;
    private $telephone_pers_admin;
    private $date_embauche;
    private $poste;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Getters
    public function getIdPersAdmin() { return $this->id_pers_admin; }
    public function getNomPersAdmin() { return $this->nom_pers_admin; }
    public function getPrenomPersAdmin() { return $this->prenom_pers_admin; }
    public function getEmailPersAdmin() { return $this->email_pers_admin; }
    public function getTelephonePersAdmin() { return $this->telephone_pers_admin; }
    public function getDateEmbauche() { return $this->date_embauche; }
    public function getPoste() { return $this->poste; }


    // Setters
    public function setIdPersAdmin($id) { $this->id_pers_admin = $id; }
    public function setNomPersAdmin($nom) { $this->nom_pers_admin = $nom; }
    public function setPrenomPersAdmin($prenom) { $this->prenom_pers_admin = $prenom; }
    public function setEmailPersAdmin($email) { $this->email_pers_admin = $email; }
    public function setTelephonePersAdmin($telephone) { $this->telephone_pers_admin = $telephone; }
    public function setDateEmbauche($date) { $this->date_embauche = $date; }
    public function setPoste($poste) { $this->poste = $poste; }

    // Méthodes CRUD
    public function getAllPersAdmin()
    {
        try {
            $query = "SELECT pa.* FROM personnel_admin pa 
                     ORDER BY pa.nom_pers_admin, pa.prenom_pers_admin";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tout le personnel administratif : " . $e->getMessage());
            return [];
        }
    }

    public function getPersAdminById($id)
    {
        try {
            $query = "SELECT pa.*
                     FROM personnel_admin pa 
                     WHERE pa.id_pers_admin = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du personnel administratif par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getPersAdminByLogin($login)
    {
        try {
            $query = "SELECT pa.*
                     FROM personnel_admin pa 
                     WHERE pa.email_pers_admin = :login";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':login', $login);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du personnel administratif par login : " . $e->getMessage());
            return null;
        }
    }

    public function getByUserId($id_utilisateur)
    {
        try {
            $query = "SELECT pa.*
                     FROM personnel_admin pa 
                     JOIN utilisateur u ON pa.email_pers_admin = u.login_utilisateur
                     WHERE u.id_utilisateur = :id_utilisateur";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du personnel administratif par ID utilisateur : " . $e->getMessage());
            return null;
        }
    }

    public function ajouterPersAdmin($nom, $prenom, $email, $telephone, $poste, $date_embauche)
    {
        try {
            $query = "INSERT INTO personnel_admin (nom_pers_admin, prenom_pers_admin, email_pers_admin, tel_pers_admin, poste, date_embauche) 
                     VALUES (:nom, :prenom, :email, :telephone, :poste, :date_embauche)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':poste', $poste);
            $stmt->bindParam(':date_embauche', $date_embauche);

            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    public function modifierPersAdmin($id, $nom, $prenom, $email, $telephone, $poste, $date_embauche)
    {
        try {
            $query = "UPDATE personnel_admin 
                     SET nom_pers_admin = :nom, 
                         prenom_pers_admin = :prenom, 
                         email_pers_admin = :email, 
                         tel_pers_admin = :telephone,
                         poste = :poste,
                         date_embauche = :date_embauche
                     WHERE id_pers_admin = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':poste', $poste);
            $stmt->bindParam(':date_embauche', $date_embauche);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la modification du personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerPersAdmin($id)
    {
        try {
            $query = "DELETE FROM personnel_admin  WHERE id_pers_admin = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du personnel administratif: " . $e->getMessage());
            return false;
        }
    }
}