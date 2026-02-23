<?php

class Scolarite
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function tableExists($tableName)
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Récupérer le montant de la scolarité pour un niveau d'études
     */
    public function getMontantScolarite($id_niveau)
    {
        $query = "SELECT montant_scolarite FROM niveau_etude WHERE id_niv_etude = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_niveau]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
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

    /**
     * Récupérer la dernière inscription d'un étudiant (pour obtenir id_niveau et id_annee_acad)
     */
    public function getDerniereInscription($id_etudiant)
    {
        $query = "SELECT id_niveau, id_annee_acad FROM inscriptions 
                 WHERE id_etudiant = ? ORDER BY id_inscription DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_etudiant]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une inscription par ID (compatibilité avec les vues de reçu).
     */
    public function getInscriptionById($id_inscription)
    {
        try {
            $hasVersements = $this->tableExists('versements');

            $montantPayeExpr = $hasVersements
                ? "COALESCE((SELECT SUM(v2.montant) FROM versements v2 WHERE v2.id_inscription = i.id_inscription), COALESCE(i.montant_paye, 0))"
                : "COALESCE(i.montant_paye, COALESCE(i.montant_verser, 0), 0)";

            $premierMontantExpr = $hasVersements
                ? "(SELECT v3.montant FROM versements v3 WHERE v3.id_inscription = i.id_inscription ORDER BY v3.date_versement ASC, v3.id_versement ASC LIMIT 1)"
                : "COALESCE(i.montant_verser, 0)";

            $methodeExpr = $hasVersements
                ? "(SELECT v3.methode_paiement FROM versements v3 WHERE v3.id_inscription = i.id_inscription ORDER BY v3.date_versement ASC, v3.id_versement ASC LIMIT 1)"
                : "i.methode_paiement";

            $sql = "
                SELECT 
                    i.*,
                    n.lib_niv_etude AS nom_niveau,
                    COALESCE(n.montant_scolarite, 0) AS montant_total,
                    CONCAT(YEAR(a.date_deb), '-', YEAR(a.date_fin)) AS annee_academique,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    $premierMontantExpr AS montant_premier_versement,
                    $methodeExpr AS methode_paiement,
                    $montantPayeExpr AS montant_paye,
                    GREATEST(COALESCE(n.montant_scolarite, 0) - $montantPayeExpr, 0) AS reste_a_payer
                FROM inscriptions i
                LEFT JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                LEFT JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                LEFT JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                WHERE i.id_inscription = ?
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_inscription]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getInscriptionById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le dernier versement pour une inscription.
     */
    public function getLastVersementByInscription($id_inscription)
    {
        try {
            if ($this->tableExists('versements')) {
                $sql = "
                    SELECT 
                        v.*,
                        i.id_etudiant AS num_etu,
                        e.nom_etu AS nom_etudiant,
                        e.prenom_etu AS prenom_etudiant
                    FROM versements v
                    JOIN inscriptions i ON v.id_inscription = i.id_inscription
                    JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                    WHERE v.id_inscription = ?
                    ORDER BY v.date_versement DESC, v.id_versement DESC
                    LIMIT 1
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_inscription]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $sql = "
                SELECT
                    i.id_inscription AS id_versement,
                    i.id_inscription,
                    COALESCE(i.montant_verser, 0) AS montant,
                    COALESCE(i.date_versement, i.date_inscription) AS date_versement,
                    COALESCE(i.methode_paiement, '') AS methode_paiement,
                    i.id_etudiant AS num_etu,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant
                FROM inscriptions i
                LEFT JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
                WHERE i.id_inscription = ?
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_inscription]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getLastVersementByInscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule les montants à une date donnée pour l'historique des reçus.
     */
    public function getMontantsAsOf($id_inscription, $asOfDate)
    {
        try {
            $sqlBase = "
                SELECT
                    COALESCE(n.montant_scolarite, 0) AS montant_total
                FROM inscriptions i
                LEFT JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                WHERE i.id_inscription = ?
                LIMIT 1
            ";
            $stmtBase = $this->db->prepare($sqlBase);
            $stmtBase->execute([$id_inscription]);
            $base = $stmtBase->fetch(PDO::FETCH_ASSOC) ?: ['montant_total' => 0];
            $montantTotal = (float) ($base['montant_total'] ?? 0);

            $montantPaye = 0.0;
            if ($this->tableExists('versements')) {
                $sqlPaye = "
                    SELECT COALESCE(SUM(v.montant), 0) AS montant_paye
                    FROM versements v
                    WHERE v.id_inscription = ?
                    AND DATE(v.date_versement) <= DATE(?)
                ";
                $stmtPaye = $this->db->prepare($sqlPaye);
                $stmtPaye->execute([$id_inscription, $asOfDate]);
                $row = $stmtPaye->fetch(PDO::FETCH_ASSOC) ?: ['montant_paye' => 0];
                $montantPaye = (float) ($row['montant_paye'] ?? 0);
            } else {
                $sqlPaye = "
                    SELECT COALESCE(montant_paye, COALESCE(montant_verser, 0), 0) AS montant_paye
                    FROM inscriptions
                    WHERE id_inscription = ?
                    LIMIT 1
                ";
                $stmtPaye = $this->db->prepare($sqlPaye);
                $stmtPaye->execute([$id_inscription]);
                $row = $stmtPaye->fetch(PDO::FETCH_ASSOC) ?: ['montant_paye' => 0];
                $montantPaye = (float) ($row['montant_paye'] ?? 0);
            }

            return [
                'montant_total' => $montantTotal,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => max($montantTotal - $montantPaye, 0),
            ];
        } catch (Exception $e) {
            error_log("Erreur getMontantsAsOf: " . $e->getMessage());
            return [
                'montant_total' => 0,
                'montant_paye' => 0,
                'reste_a_payer' => 0,
            ];
        }
    }
}
