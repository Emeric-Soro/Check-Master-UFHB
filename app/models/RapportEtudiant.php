<?php

class RapportEtudiant
{
    public $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllRapports()
    {
        $stmt = $this->pdo->query("
            SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu 
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            ORDER BY r.date_rapport DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRapportById($id_rapport)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.*, 
                e.nom_etu, 
                e.prenom_etu, 
                e.email_etu,
                e.promotion_etu,
                d.date_depot
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
            WHERE r.id_rapport = ?
        ");
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRapportDetail($id_rapport)
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu, d.date_depot
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
            WHERE r.id_rapport = ?
        ");
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getRapportByIdAndEtudiant($id_rapport, $num_etu)
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            WHERE r.id_rapport = ? AND r.num_etu = ?
        ");
        $stmt->execute([$id_rapport, $num_etu]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getRapportsByEtudiant($num_etu)
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            WHERE r.num_etu = ?
            ORDER BY r.date_rapport DESC
        ");
        $stmt->execute([$num_etu]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function ajouterRapport($num_etu, $nom_rapport, $theme_rapport)
    {
        try {
            if (!$this->isEtudiantExist($num_etu)) {
                error_log("Tentative d'ajout de rapport pour étudiant inexistant: " . $num_etu);
                return false;
            }

            $stmt = $this->pdo->prepare("
            INSERT INTO rapport_etudiants (num_etu, nom_rapport, theme_rapport, date_rapport, statut_rapport, version) 
            VALUES (?, ?, ?, NOW(), 'en_attente', 1)
        ");

            if ($stmt->execute([$num_etu, $nom_rapport, $theme_rapport])) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur d'ajout de rapport: " . $e->getMessage());
            return false;
        }
    }

    public function updateRapport($id_rapport, $num_etu, $nom_rapport, $theme_rapport)
    {
        try {
            // Vérifier que le rapport appartient bien à l'étudiant
            if (!$this->isRapportOwnedByEtudiant($id_rapport, $num_etu)) {
                error_log("Tentative de modification de rapport non autorisée: rapport $id_rapport par étudiant $num_etu");
                return false;
            }

            $stmt = $this->pdo->prepare("
                UPDATE rapport_etudiants 
                SET nom_rapport = ?, theme_rapport = ?, date_rapport = NOW()
                WHERE id_rapport = ? AND num_etu = ?
            ");
            return $stmt->execute([$nom_rapport, $theme_rapport, $id_rapport, $num_etu]);
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour de rapport: " . $e->getMessage());
            return false;
        }
    }

    public function deleteRapport($id_rapport, $num_etu)
    {
        try {
            // Vérifier que le rapport appartient bien à l'étudiant
            if (!$this->isRapportOwnedByEtudiant($id_rapport, $num_etu)) {
                error_log("Tentative de suppression de rapport non autorisée: rapport $id_rapport par étudiant $num_etu");
                return false;
            }

            $stmt = $this->pdo->prepare("DELETE FROM rapport_etudiants WHERE id_rapport = ? AND num_etu = ?");
            return $stmt->execute([$id_rapport, $num_etu]);
        } catch (PDOException $e) {
            error_log("Erreur de suppression de rapport: " . $e->getMessage());
            return false;
        }
    }

    public function isRapportExist($id_rapport)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE id_rapport = ?");
        $stmt->execute([$id_rapport]);
        return $stmt->fetchColumn() > 0;
    }

    public function isRapportOwnedByEtudiant($id_rapport, $num_etu)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE id_rapport = ? AND num_etu = ?");
        $stmt->execute([$id_rapport, $num_etu]);
        return $stmt->fetchColumn() > 0;
    }

    public function isEtudiantExist($num_etu)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE num_carte_etud = ?");
        $stmt->execute([$num_etu]);
        return $stmt->fetchColumn() > 0;
    }

    public function isRapportNomExist($nom_rapport, $num_etu, $id_rapport = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM rapport_etudiants WHERE nom_rapport = ? AND num_etu = ?";
            $params = [$nom_rapport, $num_etu];

            // Si on vérifie pour une modification (ID existe)
            if ($id_rapport !== null) {
                $sql .= " AND id_rapport != ?";
                $params[] = $id_rapport;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur vérification nom rapport: " . $e->getMessage());
            return true; // En cas d'erreur, on considère que le nom existe pour éviter les doublons
        }
    }

    public function getStatsEtudiant($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_rapports,
                    COUNT(CASE WHEN DATE(date_rapport) = CURDATE() THEN 1 END) as rapports_aujourd_hui,
                    COUNT(CASE WHEN date_rapport >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as rapports_semaine,
                    COUNT(CASE WHEN date_rapport >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as rapports_mois,
                    MAX(date_rapport) as dernier_rapport
                FROM rapport_etudiants 
                WHERE num_etu = ?
            ");
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération stats étudiant: " . $e->getMessage());
            return null;
        }
    }

    public function searchRapports($search_term, $num_etu = null)
    {
        try {
            $sql = "
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                WHERE (r.nom_rapport LIKE ? OR r.theme_rapport LIKE ?)
            ";

            $params = ["%$search_term%", "%$search_term%"];

            if ($num_etu !== null) {
                $sql .= " AND r.num_etu = ?";
                $params[] = $num_etu;
            }

            $sql .= " ORDER BY r.date_rapport DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur recherche rapports: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentRapports($limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu 
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                ORDER BY r.date_rapport DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports récents: " . $e->getMessage());
            return [];
        }
    }

    public function countRapportsByEtudiant($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE num_etu = ?");
            $stmt->execute([$num_etu]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur comptage rapports étudiant: " . $e->getMessage());
            return 0;
        }
    }

    public function getEtudiantInfo($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, COUNT(r.id_rapport) as nb_rapports
                FROM etudiants e
                LEFT JOIN rapport_etudiants r ON e.num_carte_etud = r.num_etu
                WHERE e.num_carte_etud = ?
                GROUP BY e.num_carte_etud
            ");
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération info étudiant: " . $e->getMessage());
            return null;
        }
    }

    public function updateStatutRapport($id_rapport, $statut)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ?, date_modification = NOW() WHERE id_rapport = ?");
            return $stmt->execute([$statut, $id_rapport]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut rapport: " . $e->getMessage());
            return false;
        }
    }

    public function setRapportEnCours($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = 'en_cours', date_modification = NOW() WHERE id_rapport = ?");
            return $stmt->execute([$id_rapport]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut rapport en cours: " . $e->getMessage());
            return false;
        }
    }

    public function updateCheminFichier($id_rapport, $chemin_fichier, $taille_fichier = null)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET chemin_fichier = ?, taille_fichier = ?, date_modification = NOW() WHERE id_rapport = ?");
            return $stmt->execute([$chemin_fichier, $taille_fichier, $id_rapport]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour chemin fichier: " . $e->getMessage());
            return false;
        }
    }

    public function getRapportsByStatut($statut)
    {
        try {
            $stmt = $this->pdo->prepare("
            SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu 
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            WHERE r.statut_rapport = ?
            ORDER BY r.date_rapport DESC
        ");
            $stmt->execute([$statut]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports par statut: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterEvaluation($id_rapport, $id_evaluateur, $type_evaluateur, $commentaire, $note = null)
    {
        try {
            $stmt = $this->pdo->prepare("
            INSERT INTO evaluations_rapports (id_rapport, id_evaluateur, type_evaluateur, commentaire, note) 
            VALUES (?, ?, ?, ?, ?)
        ");
            return $stmt->execute([$id_rapport, $id_evaluateur, $type_evaluateur, $commentaire, $note]);
        } catch (PDOException $e) {
            error_log("Erreur ajout évaluation: " . $e->getMessage());
            return false;
        }
    }

    public function getEvaluationsRapport($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   CASE 
                       WHEN e.type_evaluateur = 'enseignant' THEN ens.nom_enseignant
                       ELSE pa.nom_pers_admin 
                   END as nom_evaluateur,
                   CASE 
                       WHEN e.type_evaluateur = 'enseignant' THEN ens.prenom_enseignant
                       ELSE pa.prenom_pers_admin 
                   END as prenom_evaluateur
            FROM evaluations_rapports e
            LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant AND e.type_evaluateur = 'enseignant'
            LEFT JOIN personnel_admin pa ON e.id_evaluateur = pa.id_pers_admin AND e.type_evaluateur = 'personnel_admin'
            WHERE e.id_rapport = ?
            ORDER BY e.date_evaluation DESC
        ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération évaluations: " . $e->getMessage());
            return [];
        }
    }

    public function getRapportsDeposes()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu, d.date_depot
                FROM deposer d
                JOIN rapport_etudiants r ON d.id_rapport = r.id_rapport
                JOIN etudiants e ON d.num_etu = e.num_carte_etud
                ORDER BY d.date_depot DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports déposés: " . $e->getMessage());
            return [];
        }
    }

    public function getDecisionsEvaluation($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.*,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.nom_enseignant
                        ELSE pa.nom_pers_admin 
                    END as nom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.prenom_enseignant
                        ELSE pa.prenom_pers_admin 
                    END as prenom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN 'Enseignant'
                        ELSE 'Personnel administratif'
                    END as fonction_evaluateur
                FROM evaluations_rapports e
                LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant AND e.type_evaluateur = 'enseignant'
                LEFT JOIN personnel_admin pa ON e.id_evaluateur = pa.id_pers_admin AND e.type_evaluateur = 'personnel_admin'
                WHERE e.id_rapport = ? AND e.statut_evaluation = 'terminee'
                ORDER BY e.date_evaluation DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération décisions évaluation: " . $e->getMessage());
            return [];
        }
    }
}