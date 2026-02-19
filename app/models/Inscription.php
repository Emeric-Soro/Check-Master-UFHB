<?php

class Inscription
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer toutes les inscriptions
     */
    public function getAllInscriptions()
    {
        try {
            $query = "SELECT i.*, 
                            e.nom_etu, e.prenom_etu, e.num_carte_etud,
                            n.lib_niv_etude,
                            a.date_deb, a.date_fin
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     ORDER BY i.date_inscription DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des inscriptions : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une inscription par son ID
     */
    public function getInscriptionById($id_inscription)
    {
        try {
            $query = "SELECT i.*, 
                            e.nom_etu, e.prenom_etu, e.num_carte_etud, e.email_etu,
                            n.lib_niv_etude, n.montant_scolarite, n.montant_inscription,
                            a.date_deb, a.date_fin
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     WHERE i.id_inscription = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_inscription]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'inscription : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les inscriptions d'un étudiant
     */
    public function getInscriptionsByEtudiant($num_etu)
    {
        try {
            $query = "SELECT i.*, 
                            n.lib_niv_etude, n.montant_scolarite, n.montant_inscription,
                            a.date_deb, a.date_fin
                     FROM inscriptions i
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     WHERE i.id_etudiant = ?
                     ORDER BY i.date_inscription DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_etu]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des inscriptions : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer l'inscription actuelle d'un étudiant
     */
    public function getInscriptionActuelle($num_etu)
    {
        try {
            $query = "SELECT i.*, 
                            n.lib_niv_etude, n.montant_scolarite, n.montant_inscription,
                            a.date_deb, a.date_fin
                     FROM inscriptions i
                     INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     WHERE i.id_etudiant = ?
                     ORDER BY i.date_inscription DESC
                     LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'inscription : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer une nouvelle inscription
     */
    public function creerInscription($id_etudiant, $id_niveau, $id_annee_acad, $nombre_tranche = 0, $reste_a_payer = 0, $montant_paye = 0)
    {
        try {
            $query = "INSERT INTO inscriptions 
                     (id_etudiant, id_niveau, id_annee_acad, date_inscription, statut_inscription, nombre_tranche, reste_a_payer, montant_paye) 
                     VALUES (?, ?, ?, NOW(), 'En cours', ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id_etudiant, $id_niveau, $id_annee_acad, $nombre_tranche, $reste_a_payer, $montant_paye]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier une inscription
     */
    public function modifierInscription($id_inscription, $id_niveau, $id_annee_acad, $nombre_tranche = null)
    {
        try {
            $query = "UPDATE inscriptions 
                     SET id_niveau = ?, id_annee_acad = ?";
            $params = [$id_niveau, $id_annee_acad];

            if ($nombre_tranche !== null) {
                $query .= ", nombre_tranche = ?";
                $params[] = $nombre_tranche;
            }

            $query .= " WHERE id_inscription = ?";
            $params[] = $id_inscription;

            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une inscription
     */
    public function supprimerInscription($id_inscription)
    {
        try {
            $query = "DELETE FROM inscriptions WHERE id_inscription = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id_inscription]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un étudiant est inscrit pour une année académique
     */
    public function etudiantInscrit($num_etu, $id_annee_acad)
    {
        try {
            $query = "SELECT COUNT(*) as count FROM inscriptions 
                     WHERE id_etudiant = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_etu, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour le statut d'une inscription
     */
    public function changerStatut($id_inscription, $statut)
    {
        try {
            $query = "UPDATE inscriptions SET statut_inscription = ? WHERE id_inscription = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$statut, $id_inscription]);
        } catch (PDOException $e) {
            error_log("Erreur lors du changement de statut : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les statistiques d'inscriptions par année académique
     */
    public function getStatistiquesParAnnee($id_annee_acad)
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut_inscription = 'En cours' THEN 1 ELSE 0 END) as en_cours,
                        SUM(CASE WHEN statut_inscription = 'Validée' THEN 1 ELSE 0 END) as validees,
                        SUM(CASE WHEN statut_inscription = 'Annulée' THEN 1 ELSE 0 END) as annulees
                     FROM inscriptions 
                     WHERE id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_annee_acad]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compter le nombre total d'inscriptions
     */
    public function countInscriptions()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM inscriptions";
            $stmt = $this->db->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des inscriptions : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Compter les nouvelles inscriptions (dernière semaine)
     */
    public function countNouvellesInscriptions($jours = 7)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM inscriptions WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$jours]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des nouvelles inscriptions : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer les inscriptions groupées par niveau d'étude
     */
    public function getInscriptionsParNiveau()
    {
        try {
            $query = "SELECT 
                        CASE 
                            WHEN n.lib_niv_etude LIKE '%Licence 1%' THEN 'Licence 1'
                            WHEN n.lib_niv_etude LIKE '%Licence 2%' THEN 'Licence 2'
                            WHEN n.lib_niv_etude LIKE '%Licence 3%' THEN 'Licence 3'
                            WHEN n.lib_niv_etude LIKE '%Master 1%' THEN 'Master 1'
                            WHEN n.lib_niv_etude LIKE '%Master 2%' THEN 'Master 2'
                            ELSE n.lib_niv_etude
                        END as niveau,
                        COUNT(i.id_inscription) as total
                      FROM inscriptions i
                      JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                      GROUP BY 
                        CASE 
                            WHEN n.lib_niv_etude LIKE '%Licence 1%' THEN 'Licence 1'
                            WHEN n.lib_niv_etude LIKE '%Licence 2%' THEN 'Licence 2'
                            WHEN n.lib_niv_etude LIKE '%Licence 3%' THEN 'Licence 3'
                            WHEN n.lib_niv_etude LIKE '%Master 1%' THEN 'Master 1'
                            WHEN n.lib_niv_etude LIKE '%Master 2%' THEN 'Master 2'
                            ELSE n.lib_niv_etude
                        END
                      ORDER BY 
                        CASE niveau
                            WHEN 'Licence 1' THEN 1
                            WHEN 'Licence 2' THEN 2
                            WHEN 'Licence 3' THEN 3
                            WHEN 'Master 1' THEN 4
                            WHEN 'Master 2' THEN 5
                            ELSE 6
                        END";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des inscriptions par niveau : " . $e->getMessage());
            return [];
        }
    }
}

