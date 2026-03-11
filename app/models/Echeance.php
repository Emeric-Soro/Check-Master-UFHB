<?php

class Echeance
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer toutes les échéances
     */
    public function getAllEcheances()
    {
        try {
            $query = "SELECT e.*, 
                            i.num_carte_etud,
                            et.nom_etu, et.prenom_etu, et.num_carte_etud,
                            n.lib_niv_etude
                     FROM echeances e
                     INNER JOIN inscriptions i ON e.id_inscription = i.id_inscription
                     INNER JOIN etudiants et ON i.num_carte_etud = et.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     ORDER BY e.date_echeance ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des échéances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une échéance par son ID
     */
    public function getEcheanceById($id_echeance)
    {
        try {
            $query = "SELECT e.*, 
                            i.num_carte_etud,
                            et.nom_etu, et.prenom_etu, et.num_carte_etud, et.email_etu,
                            n.lib_niv_etude
                     FROM echeances e
                     INNER JOIN inscriptions i ON e.id_inscription = i.id_inscription
                     INNER JOIN etudiants et ON i.num_carte_etud = et.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     WHERE e.id_echeance = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_echeance]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'échéance : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les échéances d'une inscription
     */
    public function getEcheancesByInscription($id_inscription)
    {
        try {
            $query = "SELECT * FROM echeances 
                     WHERE id_inscription = ? 
                     ORDER BY date_echeance ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_inscription]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des échéances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les échéances d'un étudiant
     */
    public function getEcheancesByEtudiant($num_etu)
    {
        try {
            $query = "SELECT e.*, i.id_niv_etude, n.lib_niv_etude
                     FROM echeances e
                     INNER JOIN inscriptions i ON e.id_inscription = i.id_inscription
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     WHERE i.num_carte_etud = ?
                     ORDER BY e.date_echeance ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_etu]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des échéances : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer une nouvelle échéance
     */
    public function creerEcheance($id_inscription, $montant, $date_echeance, $statut_echeance = 'En attente')
    {
        try {
            // Vérifier que le statut est valide
            if (!in_array($statut_echeance, ['En attente', 'Payée', 'En retard'])) {
                throw new Exception("Statut d'échéance invalide");
            }

            $query = "INSERT INTO echeances 
                     (id_inscription, montant, date_echeance, statut_echeance) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id_inscription, $montant, $date_echeance, $statut_echeance]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la création de l'échéance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier une échéance
     */
    public function modifierEcheance($id_echeance, $montant, $date_echeance, $statut_echeance)
    {
        try {
            // Vérifier que le statut est valide
            if (!in_array($statut_echeance, ['En attente', 'Payée', 'En retard'])) {
                throw new Exception("Statut d'échéance invalide");
            }

            $query = "UPDATE echeances 
                     SET montant = ?, date_echeance = ?, statut_echeance = ? 
                     WHERE id_echeance = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$montant, $date_echeance, $statut_echeance, $id_echeance]);
        } catch (Exception $e) {
            error_log("Erreur lors de la modification de l'échéance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Changer le statut d'une échéance
     */
    public function changerStatut($id_echeance, $statut_echeance)
    {
        try {
            // Vérifier que le statut est valide
            if (!in_array($statut_echeance, ['En attente', 'Payée', 'En retard'])) {
                throw new Exception("Statut d'échéance invalide");
            }

            $query = "UPDATE echeances SET statut_echeance = ? WHERE id_echeance = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$statut_echeance, $id_echeance]);
        } catch (Exception $e) {
            error_log("Erreur lors du changement de statut : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une échéance
     */
    public function supprimerEcheance($id_echeance)
    {
        try {
            $query = "DELETE FROM echeances WHERE id_echeance = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id_echeance]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'échéance : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer toutes les échéances d'une inscription
     */
    public function supprimerEcheancesByInscription($id_inscription)
    {
        try {
            $query = "DELETE FROM echeances WHERE id_inscription = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id_inscription]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression des échéances : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les échéances en retard
     */
    public function getEcheancesEnRetard()
    {
        try {
            $query = "SELECT e.*, 
                            i.num_carte_etud,
                            et.nom_etu, et.prenom_etu, et.num_carte_etud, et.email_etu,
                            n.lib_niv_etude
                     FROM echeances e
                     INNER JOIN inscriptions i ON e.id_inscription = i.id_inscription
                     INNER JOIN etudiants et ON i.num_carte_etud = et.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     WHERE e.date_echeance < CURDATE() 
                     AND e.statut_echeance != 'Payée'
                     ORDER BY e.date_echeance ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des échéances en retard : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les échéances à venir
     */
    public function getEcheancesAVenir($jours = 30)
    {
        try {
            $query = "SELECT e.*, 
                            i.num_carte_etud,
                            et.nom_etu, et.prenom_etu, et.num_carte_etud, et.email_etu,
                            n.lib_niv_etude
                     FROM echeances e
                     INNER JOIN inscriptions i ON e.id_inscription = i.id_inscription
                     INNER JOIN etudiants et ON i.num_carte_etud = et.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     WHERE e.date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                     AND e.statut_echeance = 'En attente'
                     ORDER BY e.date_echeance ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$jours]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des échéances à venir : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marquer automatiquement les échéances en retard
     */
    public function marquerEcheancesEnRetard()
    {
        try {
            $query = "UPDATE echeances 
                     SET statut_echeance = 'En retard' 
                     WHERE date_echeance < CURDATE() 
                     AND statut_echeance = 'En attente'";
            $stmt = $this->db->prepare($query);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des échéances : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les statuts disponibles
     */
    public static function getStatuts()
    {
        return ['En attente', 'Payée', 'En retard'];
    }
}
