<?php

class CritereEvaluation
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les critères d'évaluation
     */
    public function getAllCriteres()
    {
        try {
            $query = "SELECT * FROM critere_evaluation ORDER BY id_critere";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des critères : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un critère par son ID
     */
    public function getCritereById($id_critere)
    {
        try {
            $query = "SELECT * FROM critere_evaluation WHERE id_critere = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_critere]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du critère : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer un critère par son code
     */
    public function getCritereByCode($code_critere)
    {
        try {
            $query = "SELECT * FROM critere_evaluation WHERE code_critere = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$code_critere]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du critère : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer un nouveau critère
     */
    public function creerCritere($code_critere, $lib_critere)
    {
        try {
            $query = "INSERT INTO critere_evaluation (code_critere, lib_critere) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$code_critere, $lib_critere]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du critère : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier un critère
     */
    public function modifierCritere($id_critere, $code_critere, $lib_critere)
    {
        try {
            $query = "UPDATE critere_evaluation SET code_critere = ?, lib_critere = ? WHERE id_critere = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$code_critere, $lib_critere, $id_critere]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification du critère : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un critère
     */
    public function supprimerCritere($id_critere)
    {
        try {
            $query = "DELETE FROM critere_evaluation WHERE id_critere = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id_critere]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du critère : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les critères avec leur barème pour une année académique
     */
    public function getCriteresAvecBareme($id_annee_acad)
    {
        try {
            $query = "SELECT ce.*, bc.bareme 
                     FROM critere_evaluation ce
                     LEFT JOIN bareme_critere bc ON ce.id_critere = bc.id_critere 
                         AND bc.id_annee_acad = ?
                     ORDER BY ce.id_critere";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_annee_acad]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des critères avec barème : " . $e->getMessage());
            return [];
        }
    }
}
