<?php

class Note
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer les notes d'un étudiant
     */
    public function getByStudent($studentId, $anneeAcadId = null)
    {
        try {
            $query = "SELECT n.*, a.date_deb, a.date_fin
                     FROM notes n 
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.num_etu = ?";

            $params = [$studentId];

            if ($anneeAcadId) {
                $query .= " AND n.id_annee_acad = ?";
                $params[] = $anneeAcadId;
            }

            $query .= " ORDER BY n.date_creation DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer ou mettre à jour les notes d'un étudiant pour une année académique
     */
    public function saveNotes($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId)
    {
        try {
            // Vérifier si une note existe déjà pour cet étudiant et cette année académique
            $existing = $this->getByStudentAndYear($numEtu, $anneeAcadId);

            if ($existing) {
                // Mise à jour
                return $this->updateNote($existing->id, $moyenneM1, $moyenneM2);
            } else {
                // Création
                return $this->createNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId);
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement des notes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les notes d'un étudiant pour une année académique spécifique
     */
    public function getByStudentAndYear($studentId, $anneeAcadId)
    {
        try {
            $query = "SELECT n.*, a.date_deb, a.date_fin
                     FROM notes n 
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.num_etu = ? AND n.id_annee_acad = ?
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId, $anneeAcadId]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer une nouvelle entrée de notes
     */
    private function createNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId)
    {
        try {
            $query = "INSERT INTO notes (num_etu, moyenne_M1, moyenne_M2, id_annee_acad) 
                     VALUES (?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([$numEtu, $moyenneM1, $moyenneM2, $anneeAcadId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour une note existante
     */
    private function updateNote($noteId, $moyenneM1, $moyenneM2)
    {
        try {
            $query = "UPDATE notes 
                     SET moyenne_M1 = ?, moyenne_M2 = ?
                     WHERE id = ?";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([$moyenneM1, $moyenneM2, $noteId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une note
     */
    public function deleteNote($noteId)
    {
        try {
            $query = "DELETE FROM notes WHERE id = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$noteId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer la moyenne générale d'un étudiant pour une année académique
     */
    public function getMoyenneGenerale($studentId, $anneeAcadId = null)
    {
        try {
            $note = $anneeAcadId
                ? $this->getByStudentAndYear($studentId, $anneeAcadId)
                : $this->getLatestNote($studentId);

            if (!$note) {
                return null;
            }

            // Moyenne générale = (M1 + M2) / 2
            $moyenneGenerale = ($note->moyenne_M1 + $note->moyenne_M2) / 2;

            return (object) [
                'moyenne_M1' => $note->moyenne_M1,
                'moyenne_M2' => $note->moyenne_M2,
                'moyenne_generale' => round($moyenneGenerale, 2)
            ];
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul de la moyenne: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer la note la plus récente d'un étudiant
     */
    public function getLatestNote($studentId)
    {
        try {
            $query = "SELECT n.*, a.date_deb, a.date_fin
                     FROM notes n 
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.num_etu = ?
                     ORDER BY n.date_creation DESC
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la dernière note: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer toutes les notes d'un niveau pour une année académique
     */
    public function getNotesByNiveauAndYear($niveauId, $anneeAcadId)
    {
        try {
            $query = "SELECT n.*, e.nom_etu, e.prenom_etu, e.num_carte_etud,
                            a.date_deb, a.date_fin
                     FROM notes n
                     INNER JOIN etudiants e ON n.num_etu = e.num_carte_etud
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     LEFT JOIN (
                        SELECT i1.id_etudiant, i1.id_niveau
                        FROM inscriptions i1
                        INNER JOIN (
                            SELECT id_etudiant, MAX(id_inscription) AS max_id
                            FROM inscriptions
                            GROUP BY id_etudiant
                        ) latest ON latest.id_etudiant = i1.id_etudiant
                               AND latest.max_id = i1.id_inscription
                     ) ins ON ins.id_etudiant = e.num_carte_etud
                     WHERE n.id_annee_acad = ?
                       AND COALESCE(ins.id_niveau, e.id_niveau) = ?
                     ORDER BY e.nom_etu, e.prenom_etu";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$anneeAcadId, $niveauId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes par niveau: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les notes d'une année académique (sans filtre niveau)
     */
    public function getNotesByYear($anneeAcadId)
    {
        try {
            $query = "SELECT n.*, e.nom_etu, e.prenom_etu, e.num_carte_etud,
                            a.date_deb, a.date_fin
                     FROM notes n
                     INNER JOIN etudiants e ON n.num_etu = e.num_carte_etud
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.id_annee_acad = ?
                     ORDER BY e.nom_etu, e.prenom_etu";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$anneeAcadId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes par année: " . $e->getMessage());
            return [];
        }
    }
}
