<?php

/**
 * Modèle Scolarite
 * 
 * ATTENTION: Ce fichier contient encore plusieurs méthodes utilisant l'ancienne structure.
 * Les principales méthodes ont été corrigées (getEtudiantsInscrits, getInfosPaiementEtudiant)
 * mais plusieurs méthodes nécessitent encore une révision complète :
 * - getVersementById() : Utilise id_inscription (obsolète)
 * - supprimerVersement() : Utilise id_inscription (obsolète)
 * - getInscriptionById() : Nécessite refactoring pour clé composite
 * - getLastVersementByInscription() : Utilise id_inscription (obsolète)
 * - getMontantsAsOf() : Utilise id_inscription (obsolète)
 * 
 * Ces méthodes sont marquées @deprecated et devraient être réécrites ou supprimées.
 */

class Scolarite
{
    private $db;
    private $columnExistsCache = [];

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

    private function columnExists($tableName, $columnName): bool
    {
        $key = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([(string) $columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (Throwable $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function getAcademicYearLabelById($id_annee_acad)
    {
        if ($id_annee_acad === null || (int) $id_annee_acad <= 0) {
            return '';
        }

        $stmt = $this->db->prepare("
            SELECT CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle
            FROM annee_academique
            WHERE id_annee_acad = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $id_annee_acad]);

        return (string) ($stmt->fetchColumn() ?: '');
    }

    private function synchronizeStudentAcademicContext($id_etudiant, $id_niveau, $id_annee_acad): void
    {
        if (!$this->tableExists('etudiants')) {
            return;
        }

        $setParts = [];
        $params = [':id_etudiant' => (string) $id_etudiant];

        if ($this->columnExists('etudiants', 'id_niveau')) {
            $setParts[] = "id_niveau = :id_niveau";
            $params[':id_niveau'] = ($id_niveau === null || (int) $id_niveau <= 0) ? null : (int) $id_niveau;
        }

        if ($this->columnExists('etudiants', 'id_annee_acad')) {
            $setParts[] = "id_annee_acad = :id_annee_acad";
            $params[':id_annee_acad'] = ($id_annee_acad === null || (int) $id_annee_acad <= 0) ? null : (int) $id_annee_acad;
        }

        if ($this->columnExists('etudiants', 'promotion_etu')) {
            $setParts[] = "promotion_etu = COALESCE(:promotion_etu, promotion_etu)";
            $yearLabel = $this->getAcademicYearLabelById($id_annee_acad);
            $params[':promotion_etu'] = $yearLabel !== '' ? $yearLabel : null;
        }

        if ($setParts === []) {
            return;
        }

        $sql = "
            UPDATE etudiants
            SET " . implode(', ', $setParts) . "
            WHERE num_carte_etud = :id_etudiant
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function refreshStudentYearBalances($num_carte_etud, $id_annee_acad, $id_niv_etude = null): void
    {
        // TODO: Refactoriser cette méthode pour la nouvelle structure
        // Pour l'instant, les soldes sont déjà calculés par creerInscription
        // Cette méthode n'est plus nécessaire avec la nouvelle logique
        if ($id_annee_acad === null || (int) $id_annee_acad <= 0) {
            return;
        }

        // Log pour debug
        error_log("refreshStudentYearBalances appelée pour $num_carte_etud, année $id_annee_acad - SKIPPED (nouvelle structure)");
        return;
    }

    /**
     * Récupérer le montant de la scolarité pour un niveau d'études
     */
    public function getMontantScolarite($id_niv_etude, $id_annee_acad = null)
    {
        // Si pas d'année fournie, prendre l'année active
        if ($id_annee_acad === null) {
            $id_annee_acad = \AcademicYear::getSelectedIdFromSession();
        }

        // Récupérer depuis frais_inscription
        $query = "SELECT montant FROM frais_inscription 
                  WHERE id_niv_etude = ? AND id_annee_acad = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_niv_etude, $id_annee_acad]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['montant'])) {
            return floatval($result['montant']);
        }

        // Fallback: retourner 0 pour éviter les erreurs
        error_log("ATTENTION: Aucun frais_inscription trouvé pour niveau $id_niv_etude, année $id_annee_acad");
        return 0.0;
    }

    /**
     * Récupérer tous les niveaux d'études
     * 
     * @param int|null $idAnneeAcad ID de l'année académique (optionnel)
     * @return array Liste des niveaux d'études
     */
    public function getNiveauxEtudes($idAnneeAcad = null)
    {
        if ($idAnneeAcad !== null) {
            $query = "SELECT 
                        n.id_niv_etude, 
                        n.lib_niv_etude, 
                        COALESCE(f.montant, 0) as montant_scolarite,
                        COALESCE(f.montant, 0) as montant_inscription,
                        f.id_annee_acad 
                      FROM niveau_etude n
                      LEFT JOIN frais_inscription f ON f.id_niv_etude = n.id_niv_etude 
                                                    AND f.id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$idAnneeAcad]);
        } else {
            $query = "SELECT 
                        n.id_niv_etude, 
                        n.lib_niv_etude,
                        NULL as montant_scolarite,
                        NULL as montant_inscription,
                        NULL as id_annee_acad
                      FROM niveau_etude n";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
        }
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
    public function getEtudiantsInscrits($id_annee_acad = null)
    {
        $query = "SELECT 
            i.num_carte_etud,
            COALESCE(e.num_ident_etud, i.num_carte_etud) AS num_ident_etud,
            COALESCE(e.num_carte_etud, '') AS num_carte_etud_reel,
            COALESCE(e.nom_etu, 'Inconnu') AS nom,
            COALESCE(e.prenom_etu, '') AS prenom,
            COALESCE(n.lib_niv_etude, i.id_niv_etude) AS nom_niveau,
            COALESCE(f.montant, 0) AS montant_scolarite,
            i.id_annee_acad,
            i.id_niv_etude,
            COUNT(i.num_versement) as nombre_versements,
            SUM(i.montant_verser) as montant_paye,
            COALESCE(MAX(i.solde), 0) as reste_a_payer,
            COALESCE(MAX(i.solde), 0) as solde,
            MAX(i.date_versement) as derniere_date_versement
        FROM inscriptions i
        LEFT JOIN etudiants e ON i.num_carte_etud = e.num_ident_etud
        LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
        LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                      AND f.id_niv_etude = i.id_niv_etude";

        $params = [];
        if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
            $query .= " WHERE i.id_annee_acad = ?";
            $params[] = (int) $id_annee_acad;
        }

        $query .= "
        GROUP BY i.num_carte_etud, i.id_annee_acad, i.id_niv_etude, e.num_ident_etud, e.num_carte_etud, e.nom_etu, e.prenom_etu, n.lib_niv_etude, f.montant
        ORDER BY COALESCE(e.nom_etu, i.num_carte_etud), e.prenom_etu";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les versements (toutes les lignes d'inscriptions)
     */
    public function getAllVersements()
    {
        $query = "SELECT 
            CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) as id_inscription,
            i.num_carte_etud,
            COALESCE(e.num_ident_etud, i.num_carte_etud) AS num_ident_etud,
            COALESCE(e.nom_etu, 'Inconnu') as nom_etu,
            COALESCE(e.prenom_etu, '') as prenom_etu,
            i.num_versement,
            i.date_versement,
            i.montant_verser,
            i.methode_paiement,
            i.num_piece_mp,
            i.id_annee_acad,
            i.id_niv_etude,
            i.solde,
            COALESCE(f.montant, 0) as montant_scolarite,
            COALESCE(n.lib_niv_etude, i.id_niv_etude) as lib_niv_etude,
            a.date_deb,
            a.date_fin
        FROM inscriptions i
        LEFT JOIN etudiants e ON i.num_carte_etud = e.num_ident_etud
        LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
        LEFT JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
        LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                      AND f.id_niv_etude = i.id_niv_etude
        ORDER BY i.date_versement DESC, COALESCE(e.nom_etu, i.num_carte_etud)";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les étudiants qui n'ont jamais fait de versement
     */
    public function getEtudiantsNonInscrits($id_annee_acad = null)
    {
        $query = "SELECT e.num_carte_etud AS num_etu, e.nom_etu, e.prenom_etu
                  FROM etudiants e
                  WHERE NOT EXISTS (
                      SELECT 1
                      FROM inscriptions i
                      WHERE i.num_carte_etud = e.num_carte_etud";
        $params = [];
        if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
            $query .= " AND i.id_annee_acad = ?";
            $params[] = (int) $id_annee_acad;
        }
        $query .= "
                  )
                  ORDER BY e.nom_etu, e.prenom_etu";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une inscription (= enregistrer un versement)
     * Chaque versement crée une nouvelle ligne dans inscriptions
     */
    public function creerInscription($num_carte_etud, $id_niv_etude, $id_annee_acad, $montant_versement, $methode_paiement, $num_piece = null)
    {
        $manageTransaction = !$this->db->inTransaction();

        try {
            if ($manageTransaction) {
                $this->db->beginTransaction();
            }

            // Récupérer le montant total de scolarité depuis frais_inscription
            $montant_scolarite = $this->getMontantScolarite($id_niv_etude, $id_annee_acad);

            // Calculer le numéro de versement (1 pour le premier, 2 pour le deuxième, etc.)
            $query = "SELECT COALESCE(MAX(num_versement), 0) as dernier_num 
                     FROM inscriptions 
                     WHERE num_carte_etud = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte_etud, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $num_versement = $result['dernier_num'] + 1;

            // Calculer le montant total déjà payé
            $query = "SELECT COALESCE(SUM(montant_verser), 0) as total_paye 
                     FROM inscriptions 
                     WHERE num_carte_etud = ? AND id_annee_acad = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte_etud, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_paye = $result['total_paye'];

            // Calculer le nouveau solde après ce versement
            $nouveau_montant_paye = $total_paye + $montant_versement;
            $solde = $montant_scolarite - $nouveau_montant_paye;

            // Déterminer le statut
            $statut = ($solde <= 0) ? 'Soldé' : 'En cours';

            // Insérer le versement (PAS de champs id_inscription, montant_paye, reste_a_payer qui n'existent pas)
            $query = "INSERT INTO inscriptions (
                num_carte_etud, id_niv_etude, id_annee_acad, 
                date_inscription, date_versement, 
                num_versement, montant_verser, 
                solde, methode_paiement, num_piece_mp
            ) VALUES (?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                $num_carte_etud,
                $id_niv_etude,
                $id_annee_acad,
                $num_versement,
                $montant_versement,
                $solde,
                $methode_paiement,
                $num_piece
            ]);

            // Note: pas de lastInsertId() car PK composite
            $this->refreshStudentYearBalances($num_carte_etud, $id_annee_acad, $id_niv_etude);

            if ($manageTransaction) {
                $this->db->commit();
            }

            return $success;
        } catch (Exception $e) {
            if ($manageTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur creerInscription: " . $e->getMessage());
            throw $e;
        }
    }

    public function modifierInscription($id_inscription, $id_niveau, $id_annee_acad, $montant_versement, $nombre_tranches = null, $methode_paiement = null)
    {
        $manageTransaction = !$this->db->inTransaction();

        try {
            $existing = $this->getInscriptionById($id_inscription);
            if (!is_array($existing) || empty($existing['id_etudiant'])) {
                return false;
            }

            if ($manageTransaction) {
                $this->db->beginTransaction();
            }

            $oldYearId = !empty($existing['id_annee_acad']) ? (int) $existing['id_annee_acad'] : null;
            $idEtudiant = (string) $existing['id_etudiant'];

            $query = "
                UPDATE inscriptions
                SET id_niv_etude = ?,
                    id_annee_acad = ?,
                    montant_verser = ?,
                    methode_paiement = ?
                WHERE id_inscription = ?
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                (int) $id_niveau,
                (int) $id_annee_acad,
                (float) $montant_versement,
                $methode_paiement,
                (int) $id_inscription,
            ]);

            if ($oldYearId !== null && $oldYearId !== (int) $id_annee_acad) {
                $this->refreshStudentYearBalances($idEtudiant, $oldYearId);
            }

            $this->refreshStudentYearBalances($idEtudiant, $id_annee_acad, $id_niveau);
            $this->synchronizeStudentAcademicContext($idEtudiant, $id_niveau, $id_annee_acad);

            if ($manageTransaction) {
                $this->db->commit();
            }

            return true;
        } catch (Exception $e) {
            if ($manageTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur modifierInscription: " . $e->getMessage());
            return false;
        }
    }

    public function creerEcheance($id_inscription, $montant, $date_echeance)
    {
        if (!$this->tableExists('echeances')) {
            return false;
        }

        $query = "INSERT INTO echeances (id_inscription, montant, date_echeance, statut_echeance)
                  VALUES (?, ?, ?, 'En attente')";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([(int) $id_inscription, (float) $montant, $date_echeance]);
    }

    public function supprimerEcheances($id_inscription)
    {
        if (!$this->tableExists('echeances')) {
            return true;
        }

        $query = "DELETE FROM echeances WHERE id_inscription = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([(int) $id_inscription]);
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
            i.num_carte_etud,
            i.id_niv_etude,
            i.id_annee_acad,
            f.montant AS montant_scolarite,
            COUNT(i.num_versement) as nombre_versements,
            SUM(i.montant_verser) as montant_paye,
            COALESCE(MAX(i.solde), 0) as reste_a_payer,
            COALESCE(MAX(i.solde), 0) as solde
        FROM inscriptions i
        INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
        LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                      AND f.id_niv_etude = i.id_niv_etude
        WHERE i.num_carte_etud = ? AND i.id_annee_acad = ?
        GROUP BY i.num_carte_etud, i.id_niv_etude, i.id_annee_acad, f.montant";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_etudiant, $id_annee_acad]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un versement spécifique
     */
    public function getVersementById($composite_id)
    {
        // Décomposer l'ID composite (format: "num_carte_etud-id_annee_acad-num_versement")
        $parts = explode('-', $composite_id);
        if (count($parts) < 3) {
            return false;
        }

        // Le num_carte_etud peut contenir des tirets, donc on doit gérer ça
        $num_versement = array_pop($parts);
        $id_annee_acad = array_pop($parts);
        $num_carte_etud = implode('-', $parts);

        $query = "SELECT i.*, i.montant_verser AS montant, 'Tranche' AS type_versement,
                         e.nom_etu, e.prenom_etu,
                         n.lib_niv_etude, 
                         COALESCE(f.montant, 0) AS montant_total,
                         CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) as id_inscription
                 FROM inscriptions i
                 INNER JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                 INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                 LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                              AND f.id_niv_etude = i.id_niv_etude
                 WHERE i.num_carte_etud = ? AND i.id_annee_acad = ? AND i.num_versement = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$num_carte_etud, (int) $id_annee_acad, (int) $num_versement]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Supprimer un versement
     */
    public function supprimerVersement($id_inscription)
    {
        return $this->supprimerInscription($id_inscription);
    }

    public function supprimerInscription($id_inscription)
    {
        $manageTransaction = !$this->db->inTransaction();

        try {
            $existing = $this->getInscriptionById($id_inscription);
            if (!is_array($existing) || empty($existing['id_etudiant']) || empty($existing['id_annee_acad'])) {
                return false;
            }

            if ($manageTransaction) {
                $this->db->beginTransaction();
            }

            $query = "DELETE FROM inscriptions WHERE id_inscription = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([(int) $id_inscription]);

            if ($result) {
                $this->refreshStudentYearBalances($existing['id_etudiant'], $existing['id_annee_acad']);
            }

            if ($manageTransaction) {
                $this->db->commit();
            }

            return $result;
        } catch (Exception $e) {
            if ($manageTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur supprimerInscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un versement
     */
    public function updateVersement($id_inscription, $data)
    {
        try {
            $existing = $this->getVersementById($id_inscription);
            if (!is_array($existing) || empty($existing['id_etudiant']) || empty($existing['id_annee_acad'])) {
                return false;
            }

            $query = "UPDATE inscriptions SET 
                     montant_verser = ?,
                     methode_paiement = ?,
                     date_versement = ?,
                     num_piece_mp = ?
                     WHERE id_inscription = ?";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['montant'],
                $data['methode_paiement'],
                $data['date_versement'] ?? ($existing['date_versement'] ?? date('Y-m-d H:i:s')),
                array_key_exists('num_piece', $data) ? $data['num_piece'] : ($existing['num_piece_mp'] ?? null),
                $id_inscription
            ]);

            if ($result) {
                $this->refreshStudentYearBalances($existing['id_etudiant'], $existing['id_annee_acad'], $existing['id_niveau'] ?? null);
                $this->synchronizeStudentAcademicContext($existing['id_etudiant'], $existing['id_niveau'] ?? null, $existing['id_annee_acad']);
            }

            return $result;
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
     * Récupérer la dernière inscription d'un étudiant (pour obtenir id_niv_etude et id_annee_acad)
     */
    public function getDerniereInscription($num_carte_etud)
    {
        $query = "SELECT id_niv_etude, id_annee_acad 
                 FROM inscriptions 
                 WHERE num_carte_etud = ? 
                 ORDER BY id_annee_acad DESC, date_inscription DESC 
                 LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$num_carte_etud]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getInscriptionByEtudiantId($num_carte_etud)
    {
        $derniereInscription = $this->getDerniereInscription($num_carte_etud);
        if (!is_array($derniereInscription) || empty($derniereInscription['id_annee_acad'])) {
            return false;
        }

        $query = "
            SELECT
                i.num_carte_etud,
                i.id_niv_etude,
                i.id_annee_acad,
                COALESCE(f.montant, 0) AS montant_scolarite,
                COALESCE(SUM(i.montant_verser), 0) AS montant_inscription,
                COALESCE(MAX(i.solde), 0) AS reste_a_payer
            FROM inscriptions i
            INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
            LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                          AND f.id_niv_etude = i.id_niv_etude
            WHERE i.num_carte_etud = ? AND i.id_annee_acad = ?
            GROUP BY i.num_carte_etud, i.id_niv_etude, i.id_annee_acad, f.montant
            LIMIT 1
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            (string) $num_carte_etud,
            (int) $derniereInscription['id_annee_acad'],
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addVersement($data)
    {
        // data doit contenir: num_carte_etud, id_niv_etude, id_annee_acad, montant, methode_paiement
        if (
            empty($data['num_carte_etud']) || empty($data['id_niv_etude']) ||
            empty($data['id_annee_acad']) || empty($data['montant']) || empty($data['methode_paiement'])
        ) {
            error_log("addVersement: données incomplètes - " . json_encode($data));
            return false;
        }

        return (bool) $this->creerInscription(
            (string) $data['num_carte_etud'],
            (string) $data['id_niv_etude'],
            (int) $data['id_annee_acad'],
            (float) $data['montant'],
            (string) $data['methode_paiement'],
            $data['num_piece'] ?? null
        );
    }

    /**
     * Récupère une inscription par ID (compatibilité avec les vues de reçu).
     * L'id_inscription est au format: num_carte_etud-id_annee_acad-num_versement
     */
    public function getInscriptionById($id_inscription)
    {
        try {
            // Parser l'ID composite (format: num_carte_etud-id_annee_acad-num_versement)
            $parts = explode('-', $id_inscription);
            if (count($parts) < 3) {
                error_log("Format id_inscription invalide: $id_inscription");
                return false;
            }
            $num_versement = array_pop($parts);
            $id_annee_acad = array_pop($parts);
            $num_carte_etud = implode('-', $parts);

            $sql = "
                SELECT 
                    i.*,
                    n.lib_niv_etude AS nom_niveau,
                    COALESCE(f.montant, 0) AS montant_total,
                    CONCAT(YEAR(a.date_deb), '-', YEAR(a.date_fin)) AS annee_academique,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    e.num_ident_etud AS num_ident_etudiant,
                    i.montant_verser AS montant_premier_versement,
                    i.methode_paiement AS methode_paiement,
                    (
                        SELECT COALESCE(SUM(i2.montant_verser), 0)
                        FROM inscriptions i2
                        WHERE i2.num_carte_etud = i.num_carte_etud
                        AND i2.id_annee_acad = i.id_annee_acad
                    ) AS montant_paye,
                    i.solde AS reste_a_payer,
                    CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) as id_inscription
                FROM inscriptions i
                LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                LEFT JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                LEFT JOIN etudiants e ON i.num_carte_etud = e.num_ident_etud
                LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                              AND f.id_niv_etude = i.id_niv_etude
                WHERE i.num_carte_etud = ?
                  AND i.id_annee_acad = ?
                  AND i.num_versement = ?
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_carte_etud, $id_annee_acad, $num_versement]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getInscriptionById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le dernier versement pour une inscription.
     * L'id_inscription est au format: num_carte_etud-id_annee_acad-num_versement
     */
    public function getLastVersementByInscription($id_inscription)
    {
        try {
            // Parser l'ID composite
            $parts = explode('-', $id_inscription);
            if (count($parts) < 3) {
                return false;
            }
            $num_versement = array_pop($parts);
            $id_annee_acad = array_pop($parts);
            $num_carte_etud = implode('-', $parts);

            $sql = "
                SELECT 
                    i.*,
                    i.num_carte_etud AS num_etu,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) as id_versement
                FROM inscriptions i
                JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
                WHERE i.num_carte_etud = ?
                  AND i.id_annee_acad = ?
                ORDER BY i.num_versement DESC, i.date_versement DESC
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_carte_etud, $id_annee_acad]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getLastVersementByInscription: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule les montants à une date donnée pour l'historique des reçus.
     * L'id_inscription est au format: num_carte_etud-id_annee_acad-num_versement
     */
    public function getMontantsAsOf($id_inscription, $asOfDate)
    {
        try {
            // Parser l'ID composite
            $parts = explode('-', $id_inscription);
            if (count($parts) < 3) {
                return [
                    'montant_total' => 0,
                    'montant_paye' => 0,
                    'reste_a_payer' => 0,
                ];
            }
            $num_versement = array_pop($parts);
            $id_annee_acad = array_pop($parts);
            $num_carte_etud = implode('-', $parts);

            $sqlBase = "
                SELECT
                    COALESCE(f.montant, 0) AS montant_total
                FROM inscriptions i
                LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                LEFT JOIN frais_inscription f ON f.id_annee_acad = i.id_annee_acad 
                                              AND f.id_niv_etude = i.id_niv_etude
                WHERE i.num_carte_etud = ?
                  AND i.id_annee_acad = ?
                  AND i.num_versement = ?
                LIMIT 1
            ";
            $stmtBase = $this->db->prepare($sqlBase);
            $stmtBase->execute([$num_carte_etud, $id_annee_acad, $num_versement]);
            $base = $stmtBase->fetch(PDO::FETCH_ASSOC) ?: ['montant_total' => 0];
            $montantTotal = (float) ($base['montant_total'] ?? 0);

            $sqlPaye = "
                SELECT COALESCE(SUM(i.montant_verser), 0) AS montant_paye
                FROM inscriptions i
                WHERE i.num_carte_etud = ?
                AND i.id_annee_acad = ?
                AND DATE(i.date_versement) <= DATE(?)
            ";
            $stmtPaye = $this->db->prepare($sqlPaye);
            $stmtPaye->execute([$num_carte_etud, $id_annee_acad, $asOfDate]);
            $row = $stmtPaye->fetch(PDO::FETCH_ASSOC) ?: ['montant_paye' => 0];
            $montantPaye = (float) ($row['montant_paye'] ?? 0);

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

    /**
     * Mettre à jour le chemin de la fiche d'inscription d'un étudiant
     *
     * @param int $idInscription ID de l'inscription
     * @param string $fichePath Chemin du fichier
     * @return bool Succès de l'opération
     */
    public function updateFicheInscription($idInscription, $fichePath)
    {
        try {
            $sql = "UPDATE inscriptions SET fiche_inscription = :fiche WHERE id_inscription = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'fiche' => $fichePath,
                'id' => $idInscription
            ]);
        } catch (Exception $e) {
            error_log("Erreur updateFicheInscription: " . $e->getMessage());
            return false;
        }
    }
}
