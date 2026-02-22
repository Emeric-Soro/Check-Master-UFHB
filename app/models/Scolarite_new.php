<?php

class Scolarite
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer le montant de la scolarité pour un niveau d'études
     */
    public function getMontantScolarite($id_niveau)
    {
        $query = "SELECT montant_scolarite FROM niveau_etude WHERE id_niv_etude = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_niveau]);
        $result = $this->db->fetch(PDO::FETCH_ASSOC);
        return $result['montant_scolarite'];
    }

    /**
     * Récupérer tous les niveaux d'études
     */
    public function getNiveauxEtudes()
    {
        $query = "SELECT id_niv_etude, lib_niv_etude, montant_scolarite, montant_inscription FROM niveau_etude";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les informations d'un étudiant
     */
    public function getInfoEtudiant($numEtu)
    {
        $query = "SELECT num_carte_etud as num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$numEtu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les étudiants
     */
    public function getAllEtudiants()
    {
        $query = "SELECT * FROM etudiants ORDER BY nom_etu, prenom_etu";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les étudiants qui ont au moins un versement
     * Groupés par étudiant pour afficher leur situation de paiement
     */
    public function getEtudiantsInscrits()
    {
        $query = "SELECT 
            i.id_etudiant,
            e.nom_etu AS nom,
            e.prenom_etu AS prenom,
            n.lib_niv_etude AS nom_niveau,
            n.montant_scolarite,
            i.id_annee_acad,
            i.id_niveau,
            COUNT(i.id_inscription) as nombre_versements,
            SUM(i.montant_verser) as montant_paye,
            (n.montant_scolarite - COALESCE(SUM(i.montant_verser), 0)) as reste_a_payer,
            MAX(i.date_versement) as derniere_date_versement,
            MAX(i.id_inscription) as derniere_inscription_id
        FROM inscriptions i
        INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
        INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
        GROUP BY i.id_etudiant, i.id_annee_acad, i.id_niveau, e.nom_etu, e.prenom_etu, n.lib_niv_etude, n.montant_scolarite
        ORDER BY e.nom_etu, e.prenom_etu";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les versements (toutes les lignes d'inscriptions)
     */
    public function getAllVersements()
    {
        $query = "SELECT 
            i.id_inscription,
            i.id_etudiant,
            e.nom_etu,
            e.prenom_etu,
            i.num_versement,
            i.date_versement,
            i.montant_verser,
            i.methode_paiement,
            i.num_piece_mp,
            i.solde,
            n.lib_niv_etude,
            a.date_deb,
            a.date_fin
        FROM inscriptions i
        INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
        INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
        INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
        ORDER BY i.date_versement DESC, e.nom_etu";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les étudiants qui n'ont jamais fait de versement
     */
    public function getEtudiantsNonInscrits()
    {
        $query = "SELECT e.num_carte_etud AS num_etu, e.nom_etu, e.prenom_etu
                  FROM etudiants e
                  WHERE NOT EXISTS (
                      SELECT 1
                      FROM inscriptions i
                      WHERE i.id_etudiant = e.num_carte_etud
                  )
                  ORDER BY e.nom_etu, e.prenom_etu";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une inscription (= enregistrer un versement)
     * Chaque versement crée une nouvelle ligne dans inscriptions
     */
    public function creerInscription($id_etudiant, $id_niveau, $id_annee_acad, $montant_versement, $methode_paiement, $num_piece = null)
    {
        try {
            // Récupérer le montant total de scolarité
            $montant_scolarite = $this->getMontantScolarite($id_niveau);

            // Calculer le numéro de versement (1 pour le premier, 2 pour le deuxième, etc.)
            $query = "SELECT COALESCE(MAX(num_versement), 0) as dernier_num 
                     FROM inscriptions 
                     WHERE id_etudiant = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_etudiant, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $num_versement = $result['dernier_num'] + 1;

            // Calculer le montant total déjà payé
            $query = "SELECT COALESCE(SUM(montant_verser), 0) as total_paye 
                     FROM inscriptions 
                     WHERE id_etudiant = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_etudiant, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_paye = $result['total_paye'];

            // Calculer le nouveau solde après ce versement
            $nouveau_montant_paye = $total_paye + $montant_versement;
            $solde = $montant_scolarite - $nouveau_montant_paye;

            // Déterminer le statut
            $statut = ($solde <= 0) ? 'Soldé' : 'En cours';

            // Insérer le versement
            $query = "INSERT INTO inscriptions (
                id_etudiant, id_niveau, id_annee_acad, 
                date_inscription, date_versement, 
                num_versement, montant_verser, 
                montant_paye, reste_a_payer, solde,
                methode_paiement, num_piece_mp, 
                statut_inscription
            ) VALUES (?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $id_etudiant,
                $id_niveau,
                $id_annee_acad,
                $num_versement,
                $montant_versement,
                $nouveau_montant_paye,
                $solde,
                $solde,
                $methode_paiement,
                $num_piece,
                $statut
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Erreur creerInscription: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Vérifier si un étudiant a déjà des versements pour une année académique
     */
    public function estEtudiantInscritPourAnnee($id_etudiant, $id_annee_acad)
    {
        $query = "SELECT COUNT(*) as total FROM inscriptions 
                 WHERE id_etudiant = ? AND id_annee_acad = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_etudiant, $id_annee_acad]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Récupérer les informations de paiement d'un étudiant
     */
    public function getInfosPaiementEtudiant($id_etudiant, $id_annee_acad)
    {
        $query = "SELECT 
            i.id_etudiant,
            i.id_niveau,
            i.id_annee_acad,
            n.montant_scolarite,
            COUNT(i.id_inscription) as nombre_versements,
            SUM(i.montant_verser) as montant_paye,
            (n.montant_scolarite - COALESCE(SUM(i.montant_verser), 0)) as reste_a_payer
        FROM inscriptions i
        INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
        WHERE i.id_etudiant = ? AND i.id_annee_acad = ?
        GROUP BY i.id_etudiant, i.id_niveau, i.id_annee_acad, n.montant_scolarite";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_etudiant, $id_annee_acad]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un versement spécifique
     */
    public function getVersementById($id_inscription)
    {
        $query = "SELECT i.*, e.nom_etu, e.prenom_etu, n.lib_niv_etude, n.montant_scolarite
                 FROM inscriptions i
                 INNER JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                 INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                 WHERE i.id_inscription = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_inscription]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Supprimer un versement
     */
    public function supprimerVersement($id_inscription)
    {
        $query = "DELETE FROM inscriptions WHERE id_inscription = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id_inscription]);
    }

    /**
     * Mettre à jour un versement
     */
    public function updateVersement($id_inscription, $data)
    {
        try {
            $query = "UPDATE inscriptions SET 
                     montant_verser = ?,
                     methode_paiement = ?,
                     date_versement = ?,
                     num_piece_mp = ?
                     WHERE id_inscription = ?";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                $data['montant'],
                $data['methode_paiement'],
                $data['date_versement'],
                $data['num_piece'] ?? null,
                $id_inscription
            ]);
        } catch (Exception $e) {
            error_log("Erreur updateVersement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les inscriptions/versements d'un étudiant pour une année
     */
    public function getVersementsEtudiant($id_etudiant, $id_annee_acad)
    {
        $query = "SELECT * FROM inscriptions 
                 WHERE id_etudiant = ? AND id_annee_acad = ?
                 ORDER BY num_versement ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_etudiant, $id_annee_acad]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
