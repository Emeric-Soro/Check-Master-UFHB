<?php


class PersAdmin{



    private $db;
    private $id_pers_admin;
    private $nom_pers_admin;
    private $prenom_pers_admin;
    private $email_pers_admin;
    private $telephone_pers_admin;
    private $date_embauche;
    private $poste;

    public function __construct($db)
    {
        $this->db = $db;
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
    public function getAllPersAdmin() {
        $query = "SELECT pa.* FROM personnel_admin pa 
                 ORDER BY pa.nom_pers_admin, pa.prenom_pers_admin";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersAdminById($id) {
        $query = "SELECT pa.*
                 FROM personnel_admin pa 
                 WHERE pa.id_pers_admin = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getPersAdminByLogin($login) {
        $query = "SELECT pa.*
                 FROM personnel_admin pa 
                 WHERE pa.email_pers_admin = :login";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByUserId($id_utilisateur) {
        $query = "SELECT pa.*
                 FROM personnel_admin pa 
                 JOIN utilisateur u ON pa.email_pers_admin = u.login_utilisateur
                 WHERE u.id_utilisateur = :id_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function ajouterPersAdmin($nom, $prenom, $email, $telephone, $poste, $date_embauche) {
        try {
            $query = "INSERT INTO personnel_admin (nom_pers_admin, prenom_pers_admin, email_pers_admin, tel_pers_admin, poste, date_embauche) 
                     VALUES (:nom, :prenom, :email, :telephone, :poste, :date_embauche)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':poste', $poste);
            $stmt->bindParam(':date_embauche', $date_embauche);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    public function modifierPersAdmin($id, $nom, $prenom, $email, $telephone, $poste, $date_embauche) {
        try {
            $query = "UPDATE personnel_admin 
                     SET nom_pers_admin = :nom, 
                         prenom_pers_admin = :prenom, 
                         email_pers_admin = :email, 
                         tel_pers_admin = :telephone,
                         poste = :poste,
                         date_embauche = :date_embauche
                     WHERE id_pers_admin = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':poste', $poste);
            $stmt->bindParam(':date_embauche', $date_embauche);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification du personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerPersAdmin($id) {
        try {
            $query = "DELETE FROM personnel_admin  WHERE id_pers_admin = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fiche complete: identite + poste + compte utilisateur + candidatures traitees + historique
     */
    public function getFicheComplete($id): array
    {
        $identite = $this->getPersAdminById($id);
        if (!$identite) {
            return [];
        }

        // Compte utilisateur
        $compte = null;
        try {
            $stmt = $this->db->prepare("
                SELECT u.id_utilisateur, u.login_utilisateur, u.statut_utilisateur,
                       u.id_GU, gu.lib_GU
                FROM utilisateur u
                JOIN groupe_utilisateur gu ON u.id_GU = gu.id_GU
                WHERE u.login_utilisateur = ?
                LIMIT 1
            ");
            $stmt->execute([$identite->email_pers_admin]);
            $compte = $stmt->fetch(PDO::FETCH_OBJ) ?: null;
        } catch (PDOException $e) {
            error_log('PersAdmin::getFicheComplete - compte: ' . $e->getMessage());
        }

        // Candidatures traitees
        $candidatures = [];
        try {
            $stmt = $this->db->prepare("
                SELECT cs.*, e.nom_etu, e.prenom_etu, e.promotion_etu
                FROM candidature_soutenance cs
                JOIN etudiants e ON (cs.num_etu = e.num_carte_etud OR cs.num_etu = e.num_ident_etud)
                WHERE cs.id_pers_admin = ?
                ORDER BY cs.date_traitement DESC
                LIMIT 20
            ");
            $stmt->execute([$id]);
            $candidatures = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log('PersAdmin::getFicheComplete - candidatures: ' . $e->getMessage());
        }

        // Historique pister
        $historique = [];
        if ($compte && !empty($compte->id_utilisateur)) {
            try {
                $stmt = $this->db->prepare("
                    SELECT p.id_piste, p.action, p.statut_action, p.nom_table, p.date_creation
                    FROM pister p
                    WHERE p.id_utilisateur = ?
                    ORDER BY p.date_creation DESC
                    LIMIT 30
                ");
                $stmt->execute([$compte->id_utilisateur]);
                $historique = $stmt->fetchAll(PDO::FETCH_OBJ);
            } catch (PDOException $e) {
                error_log('PersAdmin::getFicheComplete - historique: ' . $e->getMessage());
            }
        }

        return [
            'identite' => $identite,
            'compte' => $compte,
            'candidatures' => $candidatures,
            'historique' => $historique,
        ];
    }
}