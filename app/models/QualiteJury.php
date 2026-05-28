<?php

class QualiteJury
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les rôles de jury
     */
    public function getAllRoles()
    {
        try {
            $query = "SELECT id_role_jury, id_role_jury AS code_qltjury, lib_role FROM qualite_jury ORDER BY id_role_jury";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rôles : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un rôle par son ID
     */
    public function getRoleById($id_role_jury)
    {
        try {
            $query = "SELECT id_role_jury, id_role_jury AS code_qltjury, lib_role FROM qualite_jury WHERE id_role_jury = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_role_jury]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du rôle : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer un rôle par son code
     */
    public function getRoleByCode($code_qltjury)
    {
        try {
            $query = "SELECT id_role_jury, id_role_jury AS code_qltjury, lib_role FROM qualite_jury WHERE id_role_jury = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$code_qltjury]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du rôle : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer un nouveau rôle
     */
    public function creerRole($code_qltjury, $lib_role)
    {
        try {
            $query = "INSERT INTO qualite_jury (id_role_jury, lib_role) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$code_qltjury, $lib_role]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du rôle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier un rôle
     */
    public function modifierRole($id_role_jury, $code_qltjury, $lib_role)
    {
        try {
            $query = "UPDATE qualite_jury SET id_role_jury = ?, lib_role = ? WHERE id_role_jury = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$code_qltjury, $lib_role, $id_role_jury]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification du rôle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un rôle
     */
    public function supprimerRole($id_role_jury)
    {
        try {
            $query = "DELETE FROM qualite_jury WHERE id_role_jury = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id_role_jury]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du rôle : " . $e->getMessage());
            return false;
        }
    }
}
