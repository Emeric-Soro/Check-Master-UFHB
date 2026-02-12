<?php

class Entreprise
{


    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function ajouterEntreprise($lib_entreprise)
    {
        $stmt = $this->db->prepare("INSERT INTO entreprises (lib_entreprise, lien_logo_entreprise) VALUES (?, '')");
        return $stmt->execute([$lib_entreprise]);
    }

    public function updateEntreprise($id_entreprise, $lib_entreprise)
    {
        $stmt = $this->db->prepare("UPDATE entreprises SET lib_entreprise = ? WHERE id_entreprise = ?");
        return $stmt->execute([$lib_entreprise, $id_entreprise]);
    }

    public function deleteEntreprise($id_entreprise)
    {
        $stmt = $this->db->prepare("DELETE FROM entreprises WHERE id_entreprise = ?");
        return $stmt->execute([$id_entreprise]);
    }

    public function getEntrepriseById($id)
    {
        $sql = "SELECT * FROM entreprises WHERE id_entreprise = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getEntrepriseByLibelle($lib_entreprise)
    {
        $stmt = $this->db->prepare("SELECT id_entreprise FROM entreprises WHERE lib_entreprise = ?");
        $stmt->execute([$lib_entreprise]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getAllEntreprises()
    {
        $stmt = $this->db->prepare("SELECT * FROM entreprises ORDER BY lib_entreprise");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    public function getLastInsertedId()
    {
        $sql = "SELECT id_entreprise FROM entreprises ORDER BY id_entreprise DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id_entreprise'] : null;
    }
}