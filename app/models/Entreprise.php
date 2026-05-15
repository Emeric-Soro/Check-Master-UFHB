<?php

class Entreprise
{


    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function ajouterEntreprise($lib_entreprise, $lib_court = '', $email = '', $telephone = '', $logo = '')
    {
        $stmt = $this->db->prepare("INSERT INTO entreprises (lib_long_entreprise, lib_court_en, email, telephone, logo) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$lib_entreprise, $lib_court, $email, $telephone, $logo]);
    }

    public function updateEntreprise($id_entreprise, $lib_entreprise, $lib_court = null, $email = null, $telephone = null, $logo = null)
    {
        $fields = ["lib_long_entreprise = ?"];
        $params = [$lib_entreprise];

        if ($lib_court !== null) {
            $fields[] = "lib_court_en = ?";
            $params[] = $lib_court;
        }
        if ($email !== null) {
            $fields[] = "email = ?";
            $params[] = $email;
        }
        if ($telephone !== null) {
            $fields[] = "telephone = ?";
            $params[] = $telephone;
        }
        if ($logo !== null) {
            $fields[] = "logo = ?";
            $params[] = $logo;
        }

        $params[] = $id_entreprise;
        $sql = "UPDATE entreprises SET " . implode(", ", $fields) . " WHERE id_entreprise = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
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
        $stmt = $this->db->prepare("SELECT * FROM entreprises WHERE lib_long_entreprise = ? OR lib_court_en = ?");
        $stmt->execute([$lib_entreprise, $lib_entreprise]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getAllEntreprises()
    {
        $stmt = $this->db->prepare("SELECT * FROM entreprises ORDER BY lib_long_entreprise");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    public function getLastInsertedId()
    {
        return $this->db->lastInsertId();
    }
}