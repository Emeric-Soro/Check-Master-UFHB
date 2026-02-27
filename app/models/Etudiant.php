<?php

class Etudiant
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllEtudiants()
    {
        try {
            $query = "SELECT e.*, e.num_ident_etud as identifiant_mesrs, n.lib_niv_etude, a.date_deb, a.date_fin, g.libelle_genre
                     FROM etudiants e 
                     LEFT JOIN niveau_etude n ON e.id_niveau = n.id_niv_etude 
                     LEFT JOIN annee_academique a ON e.id_annee_acad = a.id_annee_acad
                     LEFT JOIN genre g ON e.genre_etu = g.id_genre
                     ORDER BY e.nom_etu, e.prenom_etu";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des étudiants : " . $e->getMessage());
            return [];
        }
    }

    public function getAllListeEtudiants()
    {
        try {
            $query = "SELECT e.*, e.num_ident_etud as identifiant_mesrs, n.lib_niv_etude, n.id_niv_etude, a.id_annee_acad, a.date_deb, a.date_fin, g.libelle_genre
                      FROM etudiants e
                      LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                      LEFT JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                      LEFT JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                      LEFT JOIN genre g ON e.genre_etu = g.id_genre
                      WHERE i.id_inscription = (
                          SELECT i2.id_inscription FROM inscriptions i2
                          WHERE i2.id_etudiant = e.num_carte_etud
                          ORDER BY i2.date_inscription DESC LIMIT 1
                      )
                      ORDER BY e.nom_etu, e.prenom_etu";
            $stmt = $this->db->prepare($query);
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

    public function ajouterEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau = null, $id_annee_acad = null, $identifiant_mesrs = null)
    {
        try {
            $sql = "INSERT INTO etudiants (num_carte_etud, num_ident_etud, nom_etu, prenom_etu, date_naiss_etu, genre_etu, email_etu, promotion_etu, id_niveau, id_annee_acad) 
                    VALUES (:num_etu, :num_ident_etud, :nom_etu, :prenom_etu, :date_naiss_etu, :genre_etu, :email_etu, :promotion_etu, :id_niveau, :id_annee_acad)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':num_etu', $num_etu);
            $stmt->bindParam(':num_ident_etud', $identifiant_mesrs);
            $stmt->bindParam(':nom_etu', $nom_etu);
            $stmt->bindParam(':prenom_etu', $prenom_etu);
            $stmt->bindParam(':date_naiss_etu', $date_naiss_etu);
            $stmt->bindParam(':genre_etu', $genre_etu);
            $stmt->bindParam(':email_etu', $email_etu);
            $stmt->bindParam(':promotion_etu', $promotion_etu);
            if ($id_niveau === null) {
                $stmt->bindValue(':id_niveau', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':id_niveau', (int) $id_niveau, PDO::PARAM_INT);
            }
            if ($id_annee_acad === null) {
                $stmt->bindValue(':id_annee_acad', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':id_annee_acad', (int) $id_annee_acad, PDO::PARAM_INT);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'étudiant : " . $e->getMessage() . " | id_niveau=" . var_export($id_niveau, true) . " | id_annee_acad=" . var_export($id_annee_acad, true));
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
                        genre_etu = :genre_etu, 
                        email_etu = :email_etu,
                        promotion_etu = :promotion_etu,
                        id_niveau = :id_niveau,
                        id_annee_acad = :id_annee_acad,
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
            if ($id_niveau === null) {
                $stmt->bindValue(':id_niveau', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':id_niveau', (int) $id_niveau, PDO::PARAM_INT);
            }
            if ($id_annee_acad === null) {
                $stmt->bindValue(':id_annee_acad', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':id_annee_acad', (int) $id_annee_acad, PDO::PARAM_INT);
            }
            $stmt->bindParam(':num_ident_etud', $identifiant_mesrs);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de l'étudiant : " . $e->getMessage() . " | id_niveau=" . var_export($id_niveau, true) . " | id_annee_acad=" . var_export($id_annee_acad, true));
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

    public function getEtudiantsByNiveau($niveauId)
    {
        $query = "SELECT e.*, n.lib_niv_etude as niveau_nom, g.libelle_genre 
                 FROM etudiants e 
                 INNER JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                 INNER JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                 LEFT JOIN genre g ON e.genre_etu = g.id_genre 
                 WHERE i.id_niveau = :niveau_id 
                 ORDER BY e.nom_etu, e.prenom_etu";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':niveau_id', $niveauId, PDO::PARAM_INT);
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
        $sql = "SELECT cs.*, e.nom_etu, e.prenom_etu 
                FROM candidature_soutenance cs 
                INNER JOIN etudiants e ON e.num_carte_etud = cs.num_etu 
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

    public function traiterCandidature($numEtu, $decision, $commentaire, $id_pers_admin)
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
                ':id_candidature' => $numEtu
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
            $sql_niveau = "SELECT i.id_niveau 
                          FROM inscriptions i 
                          WHERE i.id_etudiant = :num_etu 
                          ";

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

            $id_niveau = $niveau_etudiant['id_niveau'];

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
                   JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                   JOIN semestre s ON n.id_niv_etude = s.id_niv_etude
                   WHERE i.id_etudiant = :num_etu
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
        $query = "SELECT n.* FROM inscriptions i JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude WHERE i.id_etudiant = ? ORDER BY i.id_inscription DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$num_etu]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

}
