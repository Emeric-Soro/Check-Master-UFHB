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

            // La table ue n'existe plus - retourner des valeurs par défaut
            error_log("INFO - La table 'ue' n'existe plus dans la base de données. Fonctionnalité de calcul des moyennes désactivée.");
            
            return [
                'moyenne' => 0,
                'total_unites' => 0,
                'unites_validees' => 0,
                'resultats_disponibles' => false,
                'message' => 'Calcul des moyennes temporairement indisponible'
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
     * NOTE: La table 'ue' n'existe plus - retourne des valeurs par défaut
     */
    public function getMoyennesSemestre($numEtu, $id_semestre)
    {
        error_log("INFO - La table 'ue' n'existe plus. Calcul des moyennes par semestre désactivé.");
        
        return [
            'moyenne_majeure' => 0,
            'moyenne_mineure' => 0,
            'moyenne_semestre' => 0,
            'semestre_valide' => false,
            'credits_attribues' => 0
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
