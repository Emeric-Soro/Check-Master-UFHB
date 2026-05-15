<?php

class Etudiant
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function getAcademicYearLabelById($id_annee_acad)
    {
        if ($id_annee_acad === null || (int) $id_annee_acad <= 0) {
            return '';
        }

        try {
            $stmt = $this->db->prepare("
                SELECT CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle
                FROM annee_academique
                WHERE id_annee_acad = ?
                LIMIT 1
            ");
            $stmt->execute([(int) $id_annee_acad]);
            return (string) ($stmt->fetchColumn() ?: '');
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du libellé d'année académique : " . $e->getMessage());
            return '';
        }
    }

    /**
     * Certaines bases stockent `promotion_etu` comme libellé (2025-2026),
     * d'autres comme identifiant d'année (22625). On accepte les deux.
     *
     * @return array<int, string>
     */
    private function getAcademicYearPromotionValues($id_annee_acad)
    {
        $values = [];
        $id = (int) $id_annee_acad;
        if ($id > 0) {
            $values[] = (string) $id;
            $label = $this->getAcademicYearLabelById($id);
            if ($label !== '' && !in_array($label, $values, true)) {
                $values[] = $label;
            }
        }

        return $values;
    }

    public function getAllEtudiants($id_annee_acad = null)
    {
        try {
            $query = "SELECT e.*, e.num_ident_etud as identifiant_mesrs, 
                            n.lib_niv_etude as lib_niv_etude, 
                            a.date_deb, a.date_fin, g.libelle_genre,
                            i.id_annee_acad, i.id_niv_etude
                     FROM etudiants e 
                     LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                         SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                         FROM inscriptions i2 
                         WHERE i2.num_carte_etud = e.num_carte_etud 
                         ORDER BY i2.id_annee_acad DESC, i2.date_inscription DESC, i2.num_versement DESC
                         LIMIT 1
                     )
                     LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude 
                     LEFT JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                     LEFT JOIN genre g ON e.id_genre = g.id_genre";

            $params = [];
            if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
                $promotionValues = $this->getAcademicYearPromotionValues((int) $id_annee_acad);
                $query .= " WHERE (i.id_annee_acad = ? 
                            OR EXISTS (
                                SELECT 1
                                FROM inscriptions i3
                                WHERE i3.num_carte_etud = e.num_carte_etud
                                  AND i3.id_annee_acad = ?
                            )";
                $params[] = (int) $id_annee_acad;
                $params[] = (int) $id_annee_acad;
                foreach ($promotionValues as $promotionValue) {
                    $query .= " OR e.promotion_etu = ?";
                    $params[] = $promotionValue;
                }
                $query .= ")";
            }

            $query .= " ORDER BY e.promotion_etu DESC, e.nom_etu, e.prenom_etu";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des étudiants : " . $e->getMessage());
            return [];
        }
    }

    public function getAllListeEtudiants($id_annee_acad = null)
    {
        try {
            $query = "SELECT
                        e.*,
                        e.num_ident_etud as identifiant_mesrs,
                        n.lib_niv_etude AS lib_niv_etude,
                        n.id_niv_etude AS id_niv_etude,
                        i.id_annee_acad,
                        a.date_deb,
                        a.date_fin,
                        g.libelle_genre
                      FROM etudiants e
                      LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                          SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                          FROM inscriptions i2
                          WHERE i2.num_carte_etud = e.num_carte_etud
                          " . (($id_annee_acad !== null && (int) $id_annee_acad > 0) ? "AND i2.id_annee_acad = :annee_lookup " : "") . "
                          ORDER BY i2.id_annee_acad DESC, i2.date_inscription DESC, i2.num_versement DESC
                          LIMIT 1
                      )
                      LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                      LEFT JOIN annee_academique a ON a.id_annee_acad = i.id_annee_acad
                      LEFT JOIN genre g ON e.id_genre = g.id_genre
                      WHERE 1 = 1";

            $yearLabel = '';
            if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
                $promotionValues = $this->getAcademicYearPromotionValues((int) $id_annee_acad);
                $query .= " AND (
                                i.id_annee_acad = :annee_filter
                                OR EXISTS (
                                    SELECT 1
                                    FROM inscriptions i3
                                    WHERE i3.num_carte_etud = e.num_carte_etud
                                      AND i3.id_annee_acad = :annee_exists
                                )";
                foreach ($promotionValues as $index => $promotionValue) {
                    $paramName = ':annee_promotion_' . $index;
                    $query .= " OR e.promotion_etu = {$paramName}";
                }
                $query .= ")";
            }

            $query .= " ORDER BY e.nom_etu, e.prenom_etu";
            $stmt = $this->db->prepare($query);
            if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
                $stmt->bindValue(':annee_lookup', (int) $id_annee_acad, PDO::PARAM_INT);
                $stmt->bindValue(':annee_filter', (int) $id_annee_acad, PDO::PARAM_INT);
                $stmt->bindValue(':annee_exists', (int) $id_annee_acad, PDO::PARAM_INT);
                foreach ($promotionValues as $index => $promotionValue) {
                    $stmt->bindValue(':annee_promotion_' . $index, $promotionValue, PDO::PARAM_STR);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des étudiants : " . $e->getMessage());
            return [];
        }
    }

    public function getEtudiantById($num_etu)
    {
        try {
            $query = "SELECT *, num_ident_etud as identifiant_mesrs FROM etudiants WHERE num_carte_etud = :num_etu";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':num_etu', $num_etu);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'étudiant : " . $e->getMessage());
            return null;
        }
    }

    public function getEtudiantByLogin($nomUtilisateur)
    {
        try {
            // Séparer le nom en parties (nom et prénom)
            $parts = explode(' ', trim($nomUtilisateur), 2);

            if (count($parts) < 2) {
                return null;
            }

            $part1 = $parts[0];
            $part2 = $parts[1];

            // Chercher dans etudiants en essayant les deux ordres possibles
            // (NOM Prénom) OU (Prénom NOM)
            $query = "SELECT *, num_ident_etud as identifiant_mesrs FROM etudiants 
                      WHERE (UPPER(nom_etu) = UPPER(:part1) AND UPPER(prenom_etu) = UPPER(:part2))
                         OR (UPPER(prenom_etu) = UPPER(:part1) AND UPPER(nom_etu) = UPPER(:part2))
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['part1' => $part1, 'part2' => $part2]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'étudiant : " . $e->getMessage());
            return null;
        }

    }

    public function getEtudiantByEmail($email)
    {
        try {
            $query = "SELECT *, num_ident_etud as identifiant_mesrs FROM etudiants WHERE LOWER(email_etu) = LOWER(:email) LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['email' => trim($email)]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'étudiant par email : " . $e->getMessage());
            return null;
        }
    }

    public function ajouterEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau = null, $id_annee_acad = null, $identifiant_mesrs = null)
    {
        try {
            $sql = "INSERT INTO etudiants (num_carte_etud, num_ident_etud, nom_etu, prenom_etu, date_naiss_etu, id_genre, email_etu, promotion_etu) 
                    VALUES (:num_etu, :num_ident_etud, :nom_etu, :prenom_etu, :date_naiss_etu, :genre_etu, :email_etu, :promotion_etu)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':num_etu', $num_etu);
            $stmt->bindParam(':num_ident_etud', $identifiant_mesrs);
            $stmt->bindParam(':nom_etu', $nom_etu);
            $stmt->bindParam(':prenom_etu', $prenom_etu);
            $stmt->bindParam(':date_naiss_etu', $date_naiss_etu);
            $stmt->bindParam(':genre_etu', $genre_etu);
            $stmt->bindParam(':email_etu', $email_etu);
            $stmt->bindParam(':promotion_etu', $promotion_etu);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'étudiant : " . $e->getMessage());
            $logPath = __DIR__ . '/../../logs/gestion_etudiants.log';
            $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'gestion_etudiants.log';
            $line = date('c') . ' [gestion_etudiants:add] PDOException ' . $e->getMessage();
            @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
            @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
            return false;
        }
    }

    public function modifierEtudiant($old_num_etu, $new_num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau = null, $id_annee_acad = null, $identifiant_mesrs = null)
    {
        try {
            $sql = "UPDATE etudiants 
                    SET num_carte_etud = :new_num_etu,
                        nom_etu = :nom_etu, 
                        prenom_etu = :prenom_etu, 
                        date_naiss_etu = :date_naiss_etu, 
                        id_genre = :genre_etu, 
                        email_etu = :email_etu,
                        promotion_etu = :promotion_etu,
                        num_ident_etud = :num_ident_etud
                    WHERE num_carte_etud = :old_num_etu";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':old_num_etu', $old_num_etu);
            $stmt->bindParam(':new_num_etu', $new_num_etu);
            $stmt->bindParam(':nom_etu', $nom_etu);
            $stmt->bindParam(':prenom_etu', $prenom_etu);
            $stmt->bindParam(':date_naiss_etu', $date_naiss_etu);
            $stmt->bindParam(':genre_etu', $genre_etu);
            $stmt->bindParam(':email_etu', $email_etu);
            $stmt->bindParam(':promotion_etu', $promotion_etu);
            $stmt->bindParam(':num_ident_etud', $identifiant_mesrs);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de l'étudiant : " . $e->getMessage());
            return false;
        }
    }

    public function supprimerEtudiant($num_etu)
    {
        try {
            $query = "DELETE FROM etudiants WHERE num_carte_etud = :num_etu";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':num_etu', $num_etu);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'étudiant : " . $e->getMessage());
            return false;
        }
    }

    public function getEtudiantsByNiveau($niveauId, $anneeAcadId = null)
    {
        $query = "SELECT e.*, n.lib_niv_etude as niveau_nom, g.libelle_genre 
                 FROM etudiants e 
                 INNER JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                 INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                 LEFT JOIN genre g ON e.id_genre = g.id_genre 
                 WHERE i.id_niv_etude = :niveau_id";

        if ($anneeAcadId !== null && (int) $anneeAcadId > 0) {
            $query .= " AND i.id_annee_acad = :annee_id";
        }

        $query .= "
                 ORDER BY e.nom_etu, e.prenom_etu";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':niveau_id', $niveauId, PDO::PARAM_INT);
        if ($anneeAcadId !== null && (int) $anneeAcadId > 0) {
            $stmt->bindValue(':annee_id', (int) $anneeAcadId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCandidature($num_etu)
    {
        $sql = "SELECT * FROM candidature_soutenance WHERE num_etu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$num_etu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllCandidature()
    {
        $sql = "SELECT cs.*, e.nom_etu, e.prenom_etu, e.promotion_etu, 
                       i.id_annee_acad, a.date_deb, a.date_fin
                FROM candidature_soutenance cs 
                INNER JOIN etudiants e ON e.num_carte_etud = cs.num_etu 
                LEFT JOIN (
                    SELECT i2.num_carte_etud, i2.id_annee_acad, i2.date_inscription,
                           ROW_NUMBER() OVER (PARTITION BY i2.num_carte_etud 
                                             ORDER BY i2.id_annee_acad DESC, i2.date_inscription DESC) as rn
                    FROM inscriptions i2
                ) i ON i.num_carte_etud = e.num_carte_etud AND i.rn = 1
                LEFT JOIN annee_academique a ON a.id_annee_acad = i.id_annee_acad
                ORDER BY cs.date_candidature DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompteRendu($etudiant_id)
    {
        $sql = "SELECT * FROM compte_rendu WHERE num_etu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function traiterCandidature($id_candidature, $decision, $commentaire, $id_pers_admin)
    {
        try {
            $sql = "UPDATE candidature_soutenance 
                   SET statut_candidature = :decision, 
                       commentaire_admin = :commentaire,
                       id_pers_admin = :id_pers_admin,
                       date_traitement = NOW()
                   WHERE id_candidature = :id_candidature";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':decision' => $decision,
                ':commentaire' => $commentaire,
                ':id_pers_admin' => $id_pers_admin,
                ':id_candidature' => $id_candidature
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors du traitement de la candidature: " . $e->getMessage());
            return false;
        }
    }


    public function getNotesEtudiant($numEtu)
    {
        try {
            // D'abord, récupérer le niveau d'étude de l'étudiant
            $sql_niveau = "SELECT i.id_niv_etude 
                          FROM inscriptions i 
                          WHERE i.num_carte_etud = :num_etu 
                          ORDER BY i.id_annee_acad DESC, i.date_inscription DESC
                          LIMIT 1";

            $stmt_niveau = $this->db->prepare($sql_niveau);
            $stmt_niveau->execute([':num_etu' => $numEtu]);
            $niveau_etudiant = $stmt_niveau->fetch(PDO::FETCH_ASSOC);

            if (!$niveau_etudiant) {
                return [
                    'moyenne' => 0,
                    'total_unites' => 0,
                    'unites_validees' => 0,
                    'resultats_disponibles' => false,
                    'message' => 'Niveau d\'étude non trouvé'
                ];
            }

            $id_niveau = $niveau_etudiant['id_niv_etude'];

            // Compter le nombre total d'UE pour ce niveau
            $sql_total_ue = "SELECT COUNT(*) as total_ue 
                            FROM ue 
                            WHERE id_niveau_etude = :id_niveau 
                           ";

            $stmt_total_ue = $this->db->prepare($sql_total_ue);
            $stmt_total_ue->execute([':id_niveau' => $id_niveau]);
            $total_ue = $stmt_total_ue->fetch(PDO::FETCH_ASSOC)['total_ue'];

            // Compter le nombre d'UE où l'étudiant a des notes
            $sql_ue_avec_notes = "SELECT COUNT(DISTINCT u.id_ue) as ue_avec_notes 
                                 FROM ue u 
                                 JOIN notes n ON u.id_ue = n.id_ue 
                                 WHERE u.id_niveau_etude = :id_niveau 
                                 AND n.num_etu = :num_etu ";

            $stmt_ue_avec_notes = $this->db->prepare($sql_ue_avec_notes);
            $stmt_ue_avec_notes->execute([':id_niveau' => $id_niveau, ':num_etu' => $numEtu]);
            $ue_avec_notes = $stmt_ue_avec_notes->fetch(PDO::FETCH_ASSOC)['ue_avec_notes'];



            // Debug: Vérifier les notes de l'étudiant
            $sql_debug_notes = "SELECT n.moyenne, u.lib_ue, u.id_niveau_etude, u.id_annee_academique
                               FROM notes n 
                               JOIN ue u ON n.id_ue = u.id_ue
                               WHERE n.num_etu = :num_etu";
            $stmt_debug = $this->db->prepare($sql_debug_notes);
            $stmt_debug->execute([':num_etu' => $numEtu]);
            $debug_notes = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);

            error_log("DEBUG - Étudiant $numEtu - Niveau: $id_niveau - Notes trouvées: " . count($debug_notes));
            foreach ($debug_notes as $note) {
                error_log("DEBUG - Note: {$note['moyenne']}, UE: {$note['lib_ue']}, Niveau UE: {$note['id_niveau_etude']}, Année UE: {$note['id_annee_academique']}");
            }

            // Calculer la moyenne (version simplifiée pour debug)
            $sql_moyenne = "SELECT AVG(n.moyenne) as moyenne 
                           FROM notes n 
                           JOIN ue u ON n.id_ue = u.id_ue
                           WHERE n.num_etu = :num_etu";

            $stmt_moyenne = $this->db->prepare($sql_moyenne);
            $stmt_moyenne->execute([':num_etu' => $numEtu]);
            $result = $stmt_moyenne->fetch(PDO::FETCH_ASSOC);

            error_log("DEBUG - Moyenne calculée: " . ($result['moyenne'] ?? 'NULL'));

            // Récupérer le total des crédits du niveau
            $sql_total_credits = "SELECT SUM(u.credit) as total_credits
                                 FROM ue u
                                 WHERE u.id_niveau_etude = :id_niveau";

            $stmt_total_credits = $this->db->prepare($sql_total_credits);
            $stmt_total_credits->execute([':id_niveau' => $id_niveau]);
            $total_credits = $stmt_total_credits->fetch(PDO::FETCH_ASSOC)['total_credits'] ?? 0;

            // Calculer les crédits validés selon la moyenne générale
            $moyenne_generale = $result['moyenne'] ?? 0;
            $credits_valides = 0;

            if ($moyenne_generale >= 10) {
                // Si la moyenne générale est ≥ 10, accorder tous les crédits
                $credits_valides = $total_credits;
            } else {
                // Sinon, calculer les crédits validés UE par UE
                $sql_credits_ue = "SELECT SUM(u.credit) as credits_valides
                                  FROM ue u
                                  LEFT JOIN notes n ON u.id_ue = n.id_ue AND n.num_etu = :num_etu
                                  WHERE u.id_niveau_etude = :id_niveau 
                                  AND n.moyenne >= 10";

                $stmt_credits_ue = $this->db->prepare($sql_credits_ue);
                $stmt_credits_ue->execute([':num_etu' => $numEtu, ':id_niveau' => $id_niveau]);
                $credits_valides = $stmt_credits_ue->fetch(PDO::FETCH_ASSOC)['credits_valides'] ?? 0;
            }

            return [
                'moyenne' => $result['moyenne'] ?? 0,
                'total_unites' => $total_credits,
                'unites_validees' => $credits_valides,
                'resultats_disponibles' => true,
                'message' => 'Résultats disponibles'
            ];
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
            return [
                'moyenne' => 0,
                'total_unites' => 0,
                'unites_validees' => 0,
                'resultats_disponibles' => false,
                'message' => 'Erreur lors de la récupération des notes'
            ];
        }
    }


    public function getInfoStage($numEtu)
    {
        try {
            $sql = "SELECT infos_stage.*, e.lib_long_entreprise as nom_entreprise, e.lib_court_en
                   FROM informations_stage infos_stage
                   JOIN entreprises e ON infos_stage.id_entreprise = e.id_entreprise
                   WHERE infos_stage.num_etu = :num_etu";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':num_etu' => $numEtu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'nom_entreprise' => $result['nom_entreprise'],
                    'sujet_stage' => $result['sujet_stage'],
                    'date_debut_stage' => $result['date_debut_stage'],
                    'date_fin_stage' => $result['date_fin_stage'],
                    'encadrant_entreprise' => $result['encadrant_entreprise']
                ];
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations de stage: " . $e->getMessage());
            return null;
        }
    }

    public function getEtudiantByNumEtu($numEtu)
    {
        $sql = "SELECT *, num_ident_etud as identifiant_mesrs FROM etudiants WHERE num_carte_etud = :num_etu";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':num_etu' => $numEtu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSemestreActuel($numEtu)
    {
        try {
            // Récupérer tous les semestres du niveau d'étude de l'étudiant
            $sql = "SELECT s.id_semestre, s.lib_semestre, n.lib_niv_etude
                   FROM inscriptions i
                   JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                   JOIN semestre s ON n.id_niv_etude = s.id_niv_etude
                   WHERE i.num_carte_etud = :num_etu
                   ORDER BY s.lib_semestre ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':num_etu' => $numEtu]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'niveau' => $result[0]['lib_niv_etude'],
                    'semestres' => $result
                ];
            }

            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des semestres: " . $e->getMessage());
            return null;
        }
    }

    public function createCandidature($etudiant_id)
    {
        $sql = "INSERT INTO candidature_soutenance (num_etu, date_candidature, statut_candidature) VALUES (?, NOW(), 'En attente')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$etudiant_id]);
    }

    /**
     * Enregistre ou met à jour le résumé de candidature pour un étudiant
     */
    public function saveResumeCandidature($id_candidature, $num_etu, $resume, $decision)
    {
        $sql = "INSERT INTO resume_candidature (id_candidature, num_etu, resume_json, decision, date_enregistrement)
                VALUES (:id_candidature, :num_etu, :resume_json, :decision, NOW())
                ON DUPLICATE KEY UPDATE resume_json = :resume_json, decision = :decision, date_enregistrement = NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_candidature' => $id_candidature,
            ':num_etu' => $num_etu,
            ':resume_json' => json_encode($resume),
            ':decision' => $decision
        ]);
    }

    /**
     * Récupère le résumé de candidature pour un étudiant
     */
    public function getResumeCandidature($id_candidature)
    {
        $sql = "SELECT * FROM resume_candidature WHERE id_candidature = ? ORDER BY date_enregistrement DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_candidature]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['resume_json'] = json_decode($row['resume_json'], true);
        }
        return $row;
    }

    /**
     * Retourne toutes les candidatures d'un étudiant
     */
    public function getCandidatures($num_etu)
    {
        $sql = "SELECT * FROM candidature_soutenance WHERE num_etu = ? ORDER BY date_candidature DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$num_etu]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastCandidatureByNumEtu($num_etu)
    {
        $sql = "SELECT * FROM candidature_soutenance WHERE num_etu = ? ORDER BY date_candidature DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$num_etu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcule les moyennes majeures, mineures, la moyenne du semestre et la validation
     */
    public function getMoyennesSemestre($numEtu, $id_semestre)
    {
        // Récupérer toutes les notes d'UE de l'étudiant pour ce semestre
        $sql = "SELECT n.moyenne, u.credit
                FROM notes n
                JOIN ue u ON n.id_ue = u.id_ue
                WHERE n.num_etu = :num_etu
                  AND u.id_semestre = :id_semestre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':num_etu' => $numEtu, ':id_semestre' => $id_semestre]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $majeures = $mineures = [];
        foreach ($notes as $note) {
            if ($note['credit'] > 3) {
                $majeures[] = $note['moyenne'];
            } else {
                $mineures[] = $note['moyenne'];
            }
        }

        $moyenne_majeure = count($majeures) ? array_sum($majeures) / count($majeures) : 0;
        $moyenne_mineure = count($mineures) ? array_sum($mineures) / count($mineures) : 0;

        $semestre_valide = ($moyenne_majeure >= 10 && $moyenne_mineure >= 10);

        // Moyenne du semestre = moyenne arithmétique des deux
        $moyenne_semestre = ($moyenne_majeure + $moyenne_mineure) / 2;

        // Crédits attribués
        $credits = 0;
        if ($semestre_valide) {
            $sql = "SELECT SUM(credit) as total_credits FROM ue WHERE id_semestre = :id_semestre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_semestre' => $id_semestre]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $credits = $row['total_credits'] ?? 0;
        }

        return [
            'moyenne_majeure' => round($moyenne_majeure, 2),
            'moyenne_mineure' => round($moyenne_mineure, 2),
            'moyenne_semestre' => round($moyenne_semestre, 2),
            'semestre_valide' => $semestre_valide,
            'credits_attribues' => $credits
        ];
    }

    public function getNiveauByEtudiant($num_etu)
    {
        $query = "SELECT n.* FROM inscriptions i JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude WHERE i.num_carte_etud = ? ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$num_etu]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Liste archive des étudiants pour une année académique.
     *
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getByAnnee($id_annee_acad, array $filters = [], $limit = 100, $offset = 0)
    {
        [$sql, $params] = $this->buildArchiveStudentsQuery($id_annee_acad, $filters, false);
        if ($sql === null) {
            return [];
        }

        $sql .= " ORDER BY e.nom_etu ASC, e.prenom_etu ASC LIMIT :cm_limit OFFSET :cm_offset";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':cm_limit', max(1, (int) $limit), PDO::PARAM_INT);
            $stmt->bindValue(':cm_offset', max(0, (int) $offset), PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur archive getByAnnee (Etudiant): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Nombre total d'étudiants archives pour une année académique.
     *
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countByAnnee($id_annee_acad, array $filters = [])
    {
        [$sql, $params] = $this->buildArchiveStudentsQuery($id_annee_acad, $filters, true);
        if ($sql === null) {
            return 0;
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (PDOException $e) {
            error_log("Erreur archive countByAnnee (Etudiant): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Chronologie détaillée d'un étudiant (timeline PRD8).
     *
     * @param string $matricule
     * @return array<int, array<string, mixed>>
     */
    public function getParcours($matricule)
    {
        $sql = "
            SELECT * FROM (
                SELECT
                    'inscription' AS type,
                    i.date_inscription AS date_event,
                    CONCAT('Inscription ', COALESCE(ne.lib_niv_etude, '')) AS titre,
                    CONCAT('Statut: ', COALESCE(i.statut_inscription, 'N/A')) AS description
                FROM inscriptions i
                LEFT JOIN niveau_etude ne ON ne.id_niv_etude = i.id_niv_etude
                WHERE i.num_carte_etud = :m1

                UNION ALL

                SELECT
                    'candidature' AS type,
                    cs.date_candidature AS date_event,
                    'Candidature soutenance' AS titre,
                    CONCAT('Statut: ', COALESCE(cs.statut_candidature, 'N/A')) AS description
                FROM candidature_soutenance cs
                WHERE cs.num_etu = :m2

                UNION ALL

                SELECT
                    'depot_rapport' AS type,
                    re.date_redaction_rapport AS date_event,
                    CONCAT('Depot rapport v', COALESCE(re.version, 1)) AS titre,
                    COALESCE(re.theme_rapport, '') AS description
                FROM rapport_etudiants re
                WHERE re.num_etu = :m3

                UNION ALL

                SELECT
                    'validation' AS type,
                    v.date_validation AS date_event,
                    'Validation commission' AS titre,
                    CONCAT('Decision: ', COALESCE(v.decision_validation, 'N/A')) AS description
                FROM valider v
                INNER JOIN rapport_etudiants re ON re.id_rapport = v.id_rapport
                WHERE re.num_etu = :m4

                UNION ALL

                SELECT
                    'soutenance' AS type,
                    ps.date_soutenance AS date_event,
                    'Soutenance' AS titre,
                    CONCAT('Salle: ', COALESCE(s.lib_salle, 'N/A'), ' a ', COALESCE(TIME_FORMAT(ps.heure_soutenance, '%H:%i'), '--:--')) AS description
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON s.id_salle = ps.id_salle
                WHERE ps.num_etud = :m5

                UNION ALL

                SELECT
                    'reclamation' AS type,
                    r.date_creation AS date_event,
                    CONCAT('Reclamation: ', COALESCE(r.objet_reclamation, 'N/A')) AS titre,
                    LEFT(COALESCE(r.description_reclamation, ''), 100) AS description
                FROM reclamations r
                WHERE r.num_carte_etud = :m6
            ) AS timeline
            WHERE timeline.date_event IS NOT NULL
            ORDER BY timeline.date_event ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':m1' => $matricule,
                ':m2' => $matricule,
                ':m3' => $matricule,
                ':m4' => $matricule,
                ':m5' => $matricule,
                ':m6' => $matricule,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur archive getParcours (Etudiant): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Liste de tous les documents archivés d'un étudiant.
     *
     * @param string $matricule
     * @param int|null $id_annee_acad
     * @return array<int, array<string, mixed>>
     */
    public function getDocuments($matricule, $id_annee_acad = null)
    {
        $yearFilter = '';
        $params = [':matricule' => $matricule];

        if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
            $yearFilter = " AND i.id_annee_acad = :id_annee";
            $params[':id_annee'] = (int) $id_annee_acad;
        }

        $sql = "
            SELECT
                'fiche_inscription' AS type_doc,
                CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_doc,
                i.fiche_inscription AS chemin,
                CONCAT('Fiche inscription ', COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), '')) AS titre,
                i.date_inscription AS date_document,
                NULL AS taille_fichier
            FROM inscriptions i
            LEFT JOIN annee_academique aa ON aa.id_annee_acad = i.id_annee_acad
            WHERE i.num_carte_etud = :matricule
              AND i.fiche_inscription IS NOT NULL
              AND i.fiche_inscription <> ''
              {$yearFilter}

            UNION ALL

            SELECT
                'rapport' AS type_doc,
                re.id_rapport AS id_doc,
                re.chemin_fichier AS chemin,
                COALESCE(re.theme_rapport, CONCAT('Rapport #', re.id_rapport)) AS titre,
                re.date_redaction_rapport AS date_document,
                re.taille_fichier AS taille_fichier
            FROM rapport_etudiants re
            INNER JOIN inscriptions i ON i.num_carte_etud = re.num_etu
            WHERE re.num_etu = :matricule
              AND re.chemin_fichier IS NOT NULL
              AND re.chemin_fichier <> ''
              {$yearFilter}

            UNION ALL

            SELECT
                'compte_rendu' AS type_doc,
                cr.id_CR AS id_doc,
                cr.chemin_fichier_pdf AS chemin,
                COALESCE(cr.nom_CR, CONCAT('Compte rendu #', cr.id_CR)) AS titre,
                cr.date_CR AS date_document,
                NULL AS taille_fichier
            FROM compte_rendu cr
            INNER JOIN inscriptions i ON i.num_carte_etud = cr.num_etu
            WHERE cr.num_etu = :matricule
              AND cr.chemin_fichier_pdf IS NOT NULL
              AND cr.chemin_fichier_pdf <> ''
              {$yearFilter}

            ORDER BY date_document DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur archive getDocuments (Etudiant): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Dossier complet d'un étudiant pour la fiche archive PRD8.
     *
     * @param string $matricule
     * @param int|null $id_annee_acad
     * @return array<string, mixed>|null
     */
    public function getArchiveProfile($matricule, $id_annee_acad = null)
    {
        $params = [':matricule' => $matricule];
        $yearWhere = '';
        if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
            $yearWhere = " AND i.id_annee_acad = :id_annee";
            $params[':id_annee'] = (int) $id_annee_acad;
        }

        try {
            $stmtBase = $this->db->prepare("
                SELECT
                    e.*,
                    g.libelle_genre,
                    CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_inscription,
                    i.id_annee_acad,
                    i.date_inscription,
                    i.statut_inscription,
                    i.montant_paye,
                    i.reste_a_payer,
                    i.solde,
                    i.fiche_inscription,
                    ne.lib_niv_etude,
                    aa.date_deb,
                    aa.date_fin
                FROM etudiants e
                LEFT JOIN genre g ON g.id_genre = e.id_genre
                LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                    SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                    FROM inscriptions i2
                    WHERE i2.num_carte_etud = e.num_carte_etud
                    " . ($id_annee_acad !== null && (int) $id_annee_acad > 0 ? "AND i2.id_annee_acad = :id_annee" : "") . "
                    ORDER BY i2.date_inscription DESC, i2.id_annee_acad DESC, i2.num_versement DESC
                    LIMIT 1
                )
                LEFT JOIN niveau_etude ne ON ne.id_niv_etude = i.id_niv_etude
                LEFT JOIN annee_academique aa ON aa.id_annee_acad = i.id_annee_acad
                WHERE e.num_carte_etud = :matricule
                LIMIT 1
            ");
            $stmtBase->execute($params);
            $base = $stmtBase->fetch(PDO::FETCH_ASSOC);
            if (!$base) {
                return null;
            }

            $idAnneeFromBase = isset($base['id_annee_acad']) ? (int) $base['id_annee_acad'] : null;
            $yearParams = [':matricule' => $matricule];
            if ($idAnneeFromBase !== null && $idAnneeFromBase > 0) {
                $yearParams[':id_annee'] = $idAnneeFromBase;
            }

            $stmtNotes = $this->db->prepare("
                SELECT n.*
                FROM notes n
                WHERE n.num_etu = :matricule
                " . (($idAnneeFromBase !== null && $idAnneeFromBase > 0) ? "AND n.id_annee_acad = :id_annee" : "") . "
                ORDER BY n.date_creation DESC
                LIMIT 1
            ");
            $stmtNotes->execute($yearParams);
            $notes = $stmtNotes->fetch(PDO::FETCH_ASSOC) ?: null;

            $stmtStage = $this->db->prepare("
                SELECT
                    inf.*,
                    en.lib_long_entreprise,
                    en.email AS entreprise_email,
                    en.telephone AS entreprise_telephone,
                    ms.Nom AS maitre_nom,
                    ms.prenom AS maitre_prenom,
                    ms.email AS maitre_email,
                    ms.telephone AS maitre_tel,
                    f.lib_fonction
                FROM informations_stage inf
                LEFT JOIN entreprises en ON en.id_entreprise = inf.id_entreprise
                LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = inf.id_maitre_stage
                LEFT JOIN fonction f ON f.id_fonction = ms.id_fonction
                WHERE inf.num_etu = :matricule
                ORDER BY inf.id_info_stage DESC
                LIMIT 1
            ");
            $stmtStage->execute([':matricule' => $matricule]);
            $stage = $stmtStage->fetch(PDO::FETCH_ASSOC) ?: null;

            $stmtRapport = $this->db->prepare("
                SELECT re.*
                FROM rapport_etudiants re
                WHERE re.num_etu = :matricule
                ORDER BY re.id_rapport DESC
                LIMIT 1
            ");
            $stmtRapport->execute([':matricule' => $matricule]);
            $rapport = $stmtRapport->fetch(PDO::FETCH_ASSOC) ?: null;

            $encadrement = [];
            if ($rapport && isset($rapport['id_rapport'])) {
                $stmtEnc = $this->db->prepare("
                    SELECT
                        af.role,
                        ens.id_enseignant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant,
                        ens.mail_enseignant
                    FROM affecter af
                    INNER JOIN enseignants ens ON ens.id_enseignant = af.id_enseignant
                    WHERE af.id_rapport = :id_rapport
                    ORDER BY af.role ASC
                ");
                $stmtEnc->execute([':id_rapport' => $rapport['id_rapport']]);
                $encadrement = $stmtEnc->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $stmtSoutenance = $this->db->prepare("
                SELECT
                    ps.*,
                    s.lib_salle,
                    d.lib_domaine,
                    se.lib_session
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON s.id_salle = ps.id_salle
                LEFT JOIN domaine d ON d.id_domaine = ps.id_domaine
                LEFT JOIN session se ON se.id_session = ps.id_session
                WHERE ps.num_etud = :matricule
                ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC
                LIMIT 1
            ");
            $stmtSoutenance->execute([':matricule' => $matricule]);
            $soutenance = $stmtSoutenance->fetch(PDO::FETCH_ASSOC) ?: null;

            $jury = [];
            $evaluation = [];
            if ($soutenance && !empty($soutenance['num_soutenance'])) {
                $stmtJury = $this->db->prepare("
                    SELECT
                        en.id_enseignant,
                        en.nom_enseignant,
                        en.prenom_enseignant,
                        en.mail_enseignant,
                        qj.lib_role,
                        g.lib_grade
                    FROM enseignant_jury ej
                    INNER JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                    INNER JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
                    LEFT JOIN avoir a ON a.id_enseignant = en.id_enseignant
                    LEFT JOIN grade g ON g.id_grade = a.id_grade
                    WHERE ej.num_soutenance = :num_soutenance
                    ORDER BY qj.id_role_jury ASC
                ");
                $stmtJury->execute([':num_soutenance' => $soutenance['num_soutenance']]);
                $jury = $stmtJury->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $numJury = ctype_digit((string) $soutenance['num_soutenance'])
                    ? (int) $soutenance['num_soutenance']
                    : null;
                if ($numJury !== null) {
                    $evalParams = [
                        ':num_etud' => $matricule,
                        ':num_jury' => $numJury,
                    ];
                    $evalSql = "
                        SELECT
                            ce.lib_critere,
                            ev.note,
                            bc.bareme AS coefficient,
                            (ev.note * COALESCE(bc.bareme, 0) / 20) AS points
                        FROM evaluer ev
                        INNER JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                        LEFT JOIN bareme_critere bc ON bc.id_critere = ce.id_critere
                    ";
                    if ($idAnneeFromBase !== null && $idAnneeFromBase > 0) {
                        $evalSql .= " AND bc.id_annee_acad = :id_annee";
                        $evalParams[':id_annee'] = $idAnneeFromBase;
                    }
                    $evalSql .= " WHERE ev.num_etudiant = :num_etud AND ev.num_jury = :num_jury";

                    $stmtEval = $this->db->prepare($evalSql);
                    $stmtEval->execute($evalParams);
                    $evaluation = $stmtEval->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }

            $stmtCandidatures = $this->db->prepare("
                SELECT
                    cs.*,
                    CONCAT(pa.nom_pers_admin, ' ', pa.prenom_pers_admin) AS traite_par
                FROM candidature_soutenance cs
                LEFT JOIN personnel_admin pa ON pa.id_pers_admin = cs.id_pers_admin
                WHERE cs.num_etu = :matricule
                ORDER BY cs.date_candidature DESC
            ");
            $stmtCandidatures->execute([':matricule' => $matricule]);
            $candidatures = $stmtCandidatures->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmtReclamations = $this->db->prepare("
                SELECT
                    r.*,
                    sr.libelle_statut_reclamation
                FROM reclamations r
                LEFT JOIN statut_reclamation sr ON sr.id_statut_reclamation = r.statut_reclamation
                WHERE r.num_carte_etud = :matricule
                ORDER BY r.date_creation DESC
            ");
            $stmtReclamations->execute([':matricule' => $matricule]);
            $reclamations = $stmtReclamations->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'base' => $base,
                'notes' => $notes,
                'stage' => $stage,
                'rapport' => $rapport,
                'encadrement' => $encadrement,
                'soutenance' => $soutenance,
                'jury' => $jury,
                'evaluation' => $evaluation,
                'candidatures' => $candidatures,
                'reclamations' => $reclamations,
                'documents' => $this->getDocuments($matricule, $idAnneeFromBase),
                'parcours' => $this->getParcours($matricule),
            ];
        } catch (PDOException $e) {
            error_log("Erreur archive getArchiveProfile (Etudiant): " . $e->getMessage());
            return null;
        }
    }

    /**
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @param bool $countOnly
     * @return array{0: string|null, 1: array<string, mixed>}
     */
    private function buildArchiveStudentsQuery($id_annee_acad, array $filters, $countOnly = false)
    {
        $idAnnee = (int) $id_annee_acad;
        if ($idAnnee <= 0) {
            return [null, []];
        }

        $yearLabel = $this->getAcademicYearLabelById($idAnnee);
        $params = [':id_annee' => $idAnnee];

        $yearWhere = "i.id_annee_acad = :id_annee";
        if ($yearLabel !== '') {
            $yearWhere .= " OR e.promotion_etu = :annee_label";
            $params[':annee_label'] = $yearLabel;
        }

        $select = $countOnly
            ? "COUNT(DISTINCT e.num_carte_etud) AS total"
            : "
                DISTINCT
                e.num_carte_etud,
                e.nom_etu,
                e.prenom_etu,
                e.email_etu,
                e.promotion_etu,
                i.id_annee_acad,
                ne.lib_niv_etude,
                en.lib_long_entreprise,
                re.id_rapport,
                re.theme_rapport,
                re.chemin_fichier,
                re.date_redaction_rapport,
                n.moyenne_M1,
                n.moyenne_M2,
                enc.encadreur_nom,
                v.decision_validation AS statut_brut,
                CASE
                    WHEN v.decision_validation = 'valider' THEN 'Validé'
                    WHEN v.decision_validation = 'rejeter' THEN 'Rejeté'
                    ELSE 'En cours'
                END AS statut
            ";

        $sql = "
            SELECT {$select}
            FROM etudiants e
            LEFT JOIN inscriptions i ON i.num_carte_etud = e.num_carte_etud
            LEFT JOIN niveau_etude ne ON ne.id_niv_etude = i.id_niv_etude
            LEFT JOIN (
                SELECT x.*
                FROM informations_stage x
                INNER JOIN (
                    SELECT num_etu, MAX(id_info_stage) AS last_id
                    FROM informations_stage
                    GROUP BY num_etu
                ) m ON m.last_id = x.id_info_stage
            ) inf ON inf.num_etu = e.num_carte_etud
            LEFT JOIN entreprises en ON en.id_entreprise = inf.id_entreprise
            LEFT JOIN (
                SELECT x.*
                FROM rapport_etudiants x
                INNER JOIN (
                    SELECT num_etu, MAX(id_rapport) AS last_id
                    FROM rapport_etudiants
                    GROUP BY num_etu
                ) m ON m.last_id = x.id_rapport
            ) re ON re.num_etu = e.num_carte_etud
            LEFT JOIN (
                SELECT
                    v.id_rapport,
                    SUBSTRING_INDEX(GROUP_CONCAT(v.decision_validation ORDER BY v.date_validation DESC), ',', 1) AS decision_validation
                FROM valider v
                GROUP BY v.id_rapport
            ) v ON v.id_rapport = re.id_rapport
            LEFT JOIN notes n ON n.num_etu = e.num_carte_etud AND n.id_annee_acad = :id_annee
            LEFT JOIN (
                SELECT
                    a.id_rapport,
                    GROUP_CONCAT(DISTINCT CONCAT(en2.nom_enseignant, ' ', en2.prenom_enseignant) SEPARATOR ', ') AS encadreur_nom
                FROM affecter a
                INNER JOIN enseignants en2 ON en2.id_enseignant = a.id_enseignant
                WHERE a.role IN ('encadrant', 'encadreur')
                GROUP BY a.id_rapport
            ) enc ON enc.id_rapport = re.id_rapport
            WHERE ({$yearWhere})
        ";

        if (!empty($filters['promotion'])) {
            $sql .= " AND e.promotion_etu = :f_promotion";
            $params[':f_promotion'] = (string) $filters['promotion'];
        }

        // NOTE: Le filtre de spécialité est désactivé car la table niveau_etude n'a plus de lien avec enseignants/specialite
        // Pour réactiver ce filtre, il faudrait trouver une autre relation (via affecter, avoir, etc.)
        /*
        if (!empty($filters['specialite'])) {
            $sql .= " AND sp.id_specialite = :f_specialite";
            $params[':f_specialite'] = (int) $filters['specialite'];
        }
        */

        if (!empty($filters['statut'])) {
            $statut = strtolower(trim((string) $filters['statut']));
            if ($statut === 'en_cours' || $statut === 'encours' || $statut === 'en cours') {
                $sql .= " AND (v.decision_validation IS NULL OR v.decision_validation = '')";
            } elseif (in_array($statut, ['valider', 'validé', 'valide'], true)) {
                $sql .= " AND v.decision_validation = 'valider'";
            } elseif (in_array($statut, ['rejeter', 'rejeté', 'rejete'], true)) {
                $sql .= " AND v.decision_validation = 'rejeter'";
            }
        }

        if (!empty($filters['entreprise'])) {
            $sql .= " AND en.lib_long_entreprise LIKE :f_entreprise";
            $params[':f_entreprise'] = '%' . trim((string) $filters['entreprise']) . '%';
        }

        if (!empty($filters['encadreur'])) {
            $sql .= " AND enc.encadreur_nom LIKE :f_encadreur";
            $params[':f_encadreur'] = '%' . trim((string) $filters['encadreur']) . '%';
        }

        if (isset($filters['note_min']) && $filters['note_min'] !== '') {
            $sql .= " AND COALESCE(n.moyenne_M2, n.moyenne_M1, 0) >= :f_note_min";
            $params[':f_note_min'] = (float) $filters['note_min'];
        }

        if (isset($filters['note_max']) && $filters['note_max'] !== '') {
            $sql .= " AND COALESCE(n.moyenne_M2, n.moyenne_M1, 0) <= :f_note_max";
            $params[':f_note_max'] = (float) $filters['note_max'];
        }

        if (!empty($filters['search'])) {
            $sql .= "
                AND (
                    e.num_carte_etud LIKE :f_search
                    OR e.nom_etu LIKE :f_search
                    OR e.prenom_etu LIKE :f_search
                    OR re.theme_rapport LIKE :f_search
                    OR en.lib_long_entreprise LIKE :f_search
                )
            ";
            $params[':f_search'] = '%' . trim((string) $filters['search']) . '%';
        }

        return [$sql, $params];
    }

}
