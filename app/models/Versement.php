<?php

/**
 * Modèle Versement (Wrapper pour la table inscriptions)
 * 
 * NOTE IMPORTANTE: La table "versements" n'existe plus dans la nouvelle structure.
 * Les versements sont maintenant gérés via la table "inscriptions" avec num_versement.
 * Ce modèle sert de wrapper pour maintenir la compatibilité avec le code existant.
 */
class Versement
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les versements (toutes les inscriptions avec leurs versements)
     */
    public function getAllVersements()
    {
        try {
            $query = "SELECT i.num_carte_etud,
                            i.id_annee_acad,
                            i.num_versement,
                            i.montant_verser as montant,
                            i.date_versement,
                            i.methode_paiement,
                            i.solde,
                            e.nom_etu, 
                            e.prenom_etu, 
                            e.email_etu,
                            n.lib_niv_etude,
                            f.montant,
                            a.date_deb,
                            a.date_fin
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                                   AND f.id_niv_etude = i.id_niv_etude
                     ORDER BY i.date_versement DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des versements : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un versement par ses clés composites
     * @param string $num_carte_etud
     * @param int $id_annee_acad
     * @param int $num_versement
     */
    public function getVersementByKey($num_carte_etud, $id_annee_acad, $num_versement)
    {
        try {
            $query = "SELECT i.*,
                            e.nom_etu, 
                            e.prenom_etu, 
                            e.email_etu,
                            n.lib_niv_etude,
                            f.montant,
                            a.date_deb,
                            a.date_fin
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                                   AND f.id_niv_etude = i.id_niv_etude
                     WHERE i.num_carte_etud = ? 
                       AND i.id_annee_acad = ?
                       AND i.num_versement = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte_etud, $id_annee_acad, $num_versement]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du versement : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les versements d'un étudiant pour une année
     * @param string $num_carte_etud
     * @param int|null $id_annee_acad Si null, toutes les années
     */
    public function getVersementsByEtudiant($num_carte_etud, $id_annee_acad = null)
    {
        try {
            $query = "SELECT i.*, 
                            n.lib_niv_etude, 
                            a.date_deb, 
                            a.date_fin,
                            f.montant
                     FROM inscriptions i
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     INNER JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                                   AND f.id_niv_etude = i.id_niv_etude
                     WHERE i.num_carte_etud = ?";

            $params = [$num_carte_etud];
            if ($id_annee_acad !== null) {
                $query .= " AND i.id_annee_acad = ?";
                $params[] = $id_annee_acad;
            }

            $query .= " ORDER BY i.date_versement DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des versements : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer un nouveau versement (crée une nouvelle ligne dans inscriptions)
     * @param string $num_carte_etud
     * @param int $id_annee_acad
     * @param int $id_niv_etude
     * @param float $montant
     * @param string $methode_paiement
     * @return array|false Clés composites du nouveau versement ou false
     */
    public function creerVersement($num_carte_etud, $id_annee_acad, $id_niv_etude, $montant, $methode_paiement)
    {
        try {
            // Récupérer le montant des frais d'inscription
            $stmtFrais = $this->db->prepare(
                "SELECT montant FROM frais_inscription 
                 WHERE id_annee_acad = ? AND id_niv_etude = ?"
            );
            $stmtFrais->execute([$id_annee_acad, $id_niv_etude]);
            $frais = $stmtFrais->fetch(PDO::FETCH_ASSOC);
            $montant_total = $frais ? $frais['montant'] : 0;

            // Déterminer le prochain num_versement
            $stmtMax = $this->db->prepare(
                "SELECT COALESCE(MAX(num_versement), 0) as max_num
                 FROM inscriptions
                 WHERE num_carte_etud = ? AND id_annee_acad = ?"
            );
            $stmtMax->execute([$num_carte_etud, $id_annee_acad]);
            $resultMax = $stmtMax->fetch(PDO::FETCH_ASSOC);
            $num_versement = $resultMax['max_num'] + 1;

            // Calculer le solde
            $stmtSum = $this->db->prepare(
                "SELECT COALESCE(SUM(montant_verser), 0) as total_verse
                 FROM inscriptions
                 WHERE num_carte_etud = ? AND id_annee_acad = ?"
            );
            $stmtSum->execute([$num_carte_etud, $id_annee_acad]);
            $resultSum = $stmtSum->fetch(PDO::FETCH_ASSOC);
            $total_verse = $resultSum['total_verse'];
            $solde = max(0, $montant_total - $total_verse - $montant);

            // Insérer le nouveau versement
            $query = "INSERT INTO inscriptions 
                     (num_carte_etud, id_annee_acad, id_niv_etude, num_versement, 
                      montant_verser, date_versement, methode_paiement, solde) 
                     VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $num_carte_etud,
                $id_annee_acad,
                $id_niv_etude,
                $num_versement,
                $montant,
                $methode_paiement,
                $solde
            ]);

            if ($result) {
                // Mettre à jour le solde des versements précédents
                $this->updateSoldesPrecedents($num_carte_etud, $id_annee_acad);

                return [
                    'num_carte_etud' => $num_carte_etud,
                    'id_annee_acad' => $id_annee_acad,
                    'num_versement' => $num_versement
                ];
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la création du versement : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour les soldes de tous les versements précédents
     */
    private function updateSoldesPrecedents($num_carte_etud, $id_annee_acad)
    {
        // Cette méthode recalcule tous les soldes pour garantir la cohérence
        $query = "SELECT num_versement, montant_verser 
                  FROM inscriptions 
                  WHERE num_carte_etud = ? AND id_annee_acad = ?
                  ORDER BY num_versement ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$num_carte_etud, $id_annee_acad]);
        $versements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer le montant total
        $stmtFrais = $this->db->prepare(
            "SELECT f.montant 
             FROM frais_inscription f
             JOIN inscriptions i ON i.id_annee_acad = f.id_annee_acad AND i.id_niv_etude = f.id_niv_etude
             WHERE i.num_carte_etud = ? AND i.id_annee_acad = ?
             LIMIT 1"
        );
        $stmtFrais->execute([$num_carte_etud, $id_annee_acad]);
        $frais = $stmtFrais->fetch(PDO::FETCH_ASSOC);
        $montant_total = $frais ? $frais['montant'] : 0;

        // Recalculer chaque solde
        $cumul = 0;
        foreach ($versements as $v) {
            $cumul += $v['montant_verser'];
            $solde = max(0, $montant_total - $cumul);

            $updateStmt = $this->db->prepare(
                "UPDATE inscriptions SET solde = ? 
                 WHERE num_carte_etud = ? AND id_annee_acad = ? AND num_versement = ?"
            );
            $updateStmt->execute([$solde, $num_carte_etud, $id_annee_acad, $v['num_versement']]);
        }
    }

    /**
     * Calculer le total des versements pour un étudiant/année
     */
    public function getTotalVersements($num_carte_etud, $id_annee_acad)
    {
        try {
            $query = "SELECT COALESCE(SUM(montant_verser), 0) as total 
                     FROM inscriptions 
                     WHERE num_carte_etud = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte_etud, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return floatval($result['total']);
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer le premier versement d'un étudiant pour une année
     */
    public function getPremierVersement($num_carte_etud, $id_annee_acad)
    {
        try {
            $query = "SELECT i.*,
                            e.nom_etu, e.prenom_etu,
                            n.lib_niv_etude
                     FROM inscriptions i
                     INNER JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                     INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                     WHERE i.num_carte_etud = ? 
                       AND i.id_annee_acad = ?
                     ORDER BY i.num_versement ASC
                     LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte_etud, $id_annee_acad]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du premier versement : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les statistiques de versements pour une année académique
     */
    public function getStatistiquesVersements($id_annee_acad = null)
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_versements,
                        SUM(i.montant_verser) as montant_total,
                        COUNT(CASE WHEN i.num_versement = 1 THEN 1 END) as premiers_versements,
                        COUNT(CASE WHEN i.num_versement > 1 THEN 1 END) as tranches,
                        COUNT(DISTINCT i.num_carte_etud) as etudiants_distincts
                     FROM inscriptions i";

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
