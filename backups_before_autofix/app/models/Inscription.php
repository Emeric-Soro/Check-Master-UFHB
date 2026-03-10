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
                            a.date_deb, a.date_fin,
                            f.montant as frais_inscription
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_niv_etude = i.id_niv_etude AND f.id_annee_acad = i.id_annee_acad
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
     * Récupérer une inscription par clé composite (num_carte_etud + id_annee_acad + num_versement)
     */
    public function getInscriptionByKey($num_carte_etud, $id_annee_acad, $num_versement = 1)
    {
        try {
            $query = "SELECT i.*, 
                            e.nom_etu, e.prenom_etu, e.num_carte_etud, e.email_etu,
                            n.lib_niv_etude,
                            a.date_deb, a.date_fin,
                            f.montant as frais_inscription
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_niv_etude = i.id_niv_etude AND f.id_annee_acad = i.id_annee_acad
                     WHERE i.num_carte_etud = ? AND i.id_annee_acad = ? AND i.num_versement = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte_etud, $id_annee_acad, $num_versement]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'inscription : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer une inscription par son ancien ID (méthode deprecated pour compatibilité)
     * @deprecated Utiliser getInscriptionByKey à la place
     */
    public function getInscriptionById($id_inscription)
    {
        try {
            // Cette méthode est dépréciée car id_inscription n'existe plus
            // On essaie de parser l'ID composite si possible
            error_log("AVERTISSEMENT: getInscriptionById est déprécié. La table inscriptions n'a plus d'id_inscription.");
            return null;
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
                            n.lib_niv_etude,
                            a.date_deb, a.date_fin,
                            f.montant as frais_inscription
                     FROM inscriptions i
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_niv_etude = i.id_niv_etude AND f.id_annee_acad = i.id_annee_acad
                     WHERE i.num_carte_etud = ?
                     ORDER BY i.id_annee_acad DESC, i.date_inscription DESC, i.num_versement DESC";
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
                            n.lib_niv_etude,
                            a.date_deb, a.date_fin,
                            f.montant as frais_inscription
                     FROM inscriptions i
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_niv_etude = i.id_niv_etude AND f.id_annee_acad = i.id_annee_acad
                     WHERE i.num_carte_etud = ?
                     ORDER BY i.id_annee_acad DESC, i.date_inscription DESC
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
     * Créer un nouveau versement/inscription
     */
    public function creerInscription($num_carte_etud, $id_niv_etude, $id_annee_acad, $montant_verser = 0, $methode_paiement = null, $num_piece_mp = null)
    {
        try {
            // Déterminer le numéro de versement
            $query_max = "SELECT COALESCE(MAX(num_versement), 0) as max_versement 
                         FROM inscriptions 
                         WHERE num_carte_etud = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query_max);
            $stmt->execute([$num_carte_etud, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $num_versement = $result['max_versement'] + 1;

            // Récupérer le montant des frais
            $query_frais = "SELECT montant FROM frais_inscription 
                           WHERE id_niv_etude = ? AND id_annee_acad = ?";
            $stmt_frais = $this->db->prepare($query_frais);
            $stmt_frais->execute([$id_niv_etude, $id_annee_acad]);
            $frais = $stmt_frais->fetch(PDO::FETCH_ASSOC);
            $montant_total = $frais['montant'] ?? 0;

            // Calculer le solde
            $query_total_paye = "SELECT COALESCE(SUM(montant_verser), 0) as total_paye 
                                FROM inscriptions 
                                WHERE num_carte_etud = ? AND id_annee_acad = ?";
            $stmt_total = $this->db->prepare($query_total_paye);
            $stmt_total->execute([$num_carte_etud, $id_annee_acad]);
            $total_paye_avant = $stmt_total->fetch(PDO::FETCH_ASSOC)['total_paye'];
            $solde = $montant_total - ($total_paye_avant + $montant_verser);

            $query = "INSERT INTO inscriptions 
                     (num_carte_etud, id_niv_etude, id_annee_acad, num_versement, date_inscription, date_versement, montant_verser, methode_paiement, num_piece_mp, solde) 
                     VALUES (?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$num_carte_etud, $id_niv_etude, $id_annee_acad, $num_versement, $montant_verser, $methode_paiement, $num_piece_mp, $solde]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier une inscription (versement)
     */
    public function modifierInscription($num_carte_etud, $id_annee_acad, $num_versement, $id_niv_etude, $montant_verser = null, $methode_paiement = null)
    {
        try {
            $query = "UPDATE inscriptions 
                     SET id_niv_etude = ?";
            $params = [$id_niv_etude];

            if ($montant_verser !== null) {
                $query .= ", montant_verser = ?";
                $params[] = $montant_verser;
            }

            if ($methode_paiement !== null) {
                $query .= ", methode_paiement = ?";
                $params[] = $methode_paiement;
            }

            $query .= " WHERE num_carte_etud = ? AND id_annee_acad = ? AND num_versement = ?";
            $params[] = $num_carte_etud;
            $params[] = $id_annee_acad;
            $params[] = $num_versement;

            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une inscription (versement)
     */
    public function supprimerInscription($num_carte_etud, $id_annee_acad, $num_versement)
    {
        try {
            $query = "DELETE FROM inscriptions WHERE num_carte_etud = ? AND id_annee_acad = ? AND num_versement = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$num_carte_etud, $id_annee_acad, $num_versement]);
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
                     WHERE num_carte_etud = ? AND id_annee_acad = ?";
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
     * Mettre à jour le solde d'une inscription (méthode deprecated)
     * @deprecated Le solde est maintenant calculé automatiquement
     */
    public function changerStatut($num_carte_etud, $id_annee_acad, $num_versement, $solde)
    {
        try {
            $query = "UPDATE inscriptions SET solde = ? WHERE num_carte_etud = ? AND id_annee_acad = ? AND num_versement = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$solde, $num_carte_etud, $id_annee_acad, $num_versement]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du solde : " . $e->getMessage());
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
    public function countInscriptions($id_annee_acad = null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM inscriptions";
            $params = [];

            if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
                $query .= " WHERE id_annee_acad = ?";
                $params[] = (int) $id_annee_acad;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des inscriptions : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Compter les nouvelles inscriptions (dernière semaine)
     */
    public function countNouvellesInscriptions($jours = 7, $id_annee_acad = null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM inscriptions WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params = [(int) $jours];

            if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
                $query .= " AND id_annee_acad = ?";
                $params[] = (int) $id_annee_acad;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des nouvelles inscriptions : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer les inscriptions groupées par niveau d'étude
     */
    public function getInscriptionsParNiveau($id_annee_acad = null)
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
                      JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude";

            $params = [];
            if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
                $query .= " WHERE i.id_annee_acad = ?";
                $params[] = (int) $id_annee_acad;
            }

            $query .= "
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
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des inscriptions par niveau : " . $e->getMessage());
            return [];
        }
    }
}
