<?php

class Versement
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les versements
     */
    public function getAllVersements()
    {
        try {
            $query = "SELECT v.*, 
                            i.id_etudiant,
                            e.nom_etu, e.prenom_etu, e.num_carte_etud,
                            n.lib_niv_etude
                     FROM versements v
                     INNER JOIN inscriptions i ON v.id_inscription = i.id_inscription
                     INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     ORDER BY v.date_versement DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des versements : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un versement par son ID
     */
    public function getVersementById($id_versement)
    {
        try {
            $query = "SELECT v.*, 
                            i.id_etudiant,
                            e.nom_etu, e.prenom_etu, e.num_carte_etud, e.email_etu,
                            n.lib_niv_etude, n.montant_scolarite
                     FROM versements v
                     INNER JOIN inscriptions i ON v.id_inscription = i.id_inscription
                     INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     WHERE v.id_versement = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_versement]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du versement : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les versements d'une inscription
     */
    public function getVersementsByInscription($id_inscription)
    {
        try {
            $query = "SELECT * FROM versements 
                     WHERE id_inscription = ? 
                     ORDER BY date_versement DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_inscription]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des versements : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les versements d'un étudiant
     */
    public function getVersementsByEtudiant($num_etu)
    {
        try {
            $query = "SELECT v.*, i.id_niveau, n.lib_niv_etude, a.date_deb, a.date_fin
                     FROM versements v
                     INNER JOIN inscriptions i ON v.id_inscription = i.id_inscription
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     WHERE i.id_etudiant = ?
                     ORDER BY v.date_versement DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_etu]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des versements : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer un nouveau versement
     */
    public function creerVersement($id_inscription, $montant, $type_versement, $methode_paiement)
    {
        try {
            // Vérifier que le type de versement est valide
            if (!in_array($type_versement, ['Premier versement', 'Tranche'])) {
                throw new Exception("Type de versement invalide");
            }

            // Vérifier que la méthode de paiement est valide
            if (!in_array($methode_paiement, ['Espèce', 'Carte bancaire', 'Virement', 'Chèque'])) {
                throw new Exception("Méthode de paiement invalide");
            }

            $query = "INSERT INTO versements 
                     (id_inscription, montant, date_versement, type_versement, methode_paiement) 
                     VALUES (?, ?, NOW(), ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id_inscription, $montant, $type_versement, $methode_paiement]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la création du versement : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier un versement
     */
    public function modifierVersement($id_versement, $montant, $methode_paiement)
    {
        try {
            // Vérifier que la méthode de paiement est valide
            if (!in_array($methode_paiement, ['Espèce', 'Carte bancaire', 'Virement', 'Chèque'])) {
                throw new Exception("Méthode de paiement invalide");
            }

            $query = "UPDATE versements 
                     SET montant = ?, methode_paiement = ? 
                     WHERE id_versement = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$montant, $methode_paiement, $id_versement]);
        } catch (Exception $e) {
            error_log("Erreur lors de la modification du versement : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un versement
     */
    public function supprimerVersement($id_versement)
    {
        try {
            $query = "DELETE FROM versements WHERE id_versement = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id_versement]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du versement : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer le total des versements pour une inscription
     */
    public function getTotalVersements($id_inscription)
    {
        try {
            $query = "SELECT COALESCE(SUM(montant), 0) as total 
                     FROM versements 
                     WHERE id_inscription = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_inscription]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return floatval($result['total']);
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer le premier versement d'une inscription
     */
    public function getPremierVersement($id_inscription)
    {
        try {
            $query = "SELECT * FROM versements 
                     WHERE id_inscription = ? AND type_versement = 'Premier versement'
                     ORDER BY date_versement ASC
                     LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_inscription]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du premier versement : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les statistiques de versements
     */
    public function getStatistiquesVersements($id_annee_acad = null)
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_versements,
                        SUM(v.montant) as montant_total,
                        COUNT(CASE WHEN v.type_versement = 'Premier versement' THEN 1 END) as premiers_versements,
                        COUNT(CASE WHEN v.type_versement = 'Tranche' THEN 1 END) as tranches
                     FROM versements v
                     INNER JOIN inscriptions i ON v.id_inscription = i.id_inscription";

            $params = [];
            if ($id_annee_acad) {
                $query .= " WHERE i.id_annee_acad = ?";
                $params[] = $id_annee_acad;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les méthodes de paiement disponibles
     */
    public static function getMethodesPaiement()
    {
        return ['Espèce', 'Carte bancaire', 'Virement', 'Chèque'];
    }

    /**
     * Récupérer les types de versement disponibles
     */
    public static function getTypesVersement()
    {
        return ['Premier versement', 'Tranche'];
    }
}
