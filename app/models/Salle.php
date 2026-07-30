<?php

use CheckMaster\Models\BaseModel;

class Salle extends BaseModel
{
    protected const TABLE = 'salles';
    protected const PRIMARY_KEY = 'id_salle';

    /**
     * Récupérer toutes les salles
     */
    public function getAllSalles()
    {
        try {
            $query = "SELECT * FROM salles ORDER BY lib_salle";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des salles : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une salle par son ID
     */
    public function getSalleById($id_salle)
    {
        try {
            $query = "SELECT * FROM salles WHERE id_salle = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_salle]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la salle : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer une salle par son nom
     */
    public function getSalleByName($lib_salle)
    {
        try {
            $query = "SELECT * FROM salles WHERE lib_salle = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$lib_salle]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la salle par nom : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer une nouvelle salle
     */
    public function creerSalle($lib_salle)
    {
        try {
            $query = "INSERT INTO salles (lib_salle) VALUES (?)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$lib_salle]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la salle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier une salle
     */
    public function modifierSalle($id_salle, $lib_salle)
    {
        try {
            $query = "UPDATE salles SET lib_salle = ? WHERE id_salle = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$lib_salle, $id_salle]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de la salle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une salle
     */
    public function supprimerSalle($id_salle)
    {
        try {
            $query = "DELETE FROM salles WHERE id_salle = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$id_salle]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la salle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier la disponibilité d'une salle pour une date et heure données
     */
    public function verifierDisponibilite($id_salle, $date_soutenance, $heure_soutenance, $num_soutenance_exclure = null)
    {
        try {
            $query = "SELECT COUNT(*) as count FROM programmer_soutenance
                     WHERE id_salle = ?
                     AND date_soutenance = ?
                     AND heure_soutenance = ?";

            $params = [$id_salle, $date_soutenance, $heure_soutenance];

            if ($num_soutenance_exclure) {
                $query .= " AND num_soutenance != ?";
                $params[] = $num_soutenance_exclure;
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] == 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de disponibilité : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les salles disponibles pour une date et heure données
     */
    public function getSallesDisponibles($date_soutenance, $heure_soutenance)
    {
        try {
            $query = "SELECT s.* FROM salles s
                     WHERE s.id_salle NOT IN (
                         SELECT ps.id_salle FROM programmer_soutenance ps
                         WHERE ps.date_soutenance = ?
                         AND ps.heure_soutenance = ?
                         AND ps.id_salle IS NOT NULL
                     )
                     ORDER BY s.lib_salle";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$date_soutenance, $heure_soutenance]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des salles disponibles : " . $e->getMessage());
            return [];
        }
    }
}
