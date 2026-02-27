<?php
class InfoStage
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getStageInfo($num_etu)
    {
        $query = "SELECT i.*, e.lib_long_entreprise as nom_entreprise, e.lib_court_en
                 FROM informations_stage i 
                 INNER JOIN entreprises e ON e.id_entreprise = i.id_entreprise
                 WHERE i.num_etu = :num_etu";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':num_etu', $num_etu, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    public function getEntreprises()
    {
        $query = "SELECT id_entreprise, lib_long_entreprise, lib_court_en FROM entreprises ORDER BY lib_long_entreprise";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStageInfo($etudiant_id, $stage_data)
    {
        $sql = "UPDATE informations_stage SET 
                id_entreprise = ?, 
                date_debut_stage = ?, 
                date_fin_stage = ?, 
                sujet_stage = ?, 
                encadrant_entreprise = ?, 
                email_encadrant = ?, 
                telephone_encadrant = ?,
                id_maitre_stage = ? 
                WHERE num_etu = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $stage_data['nom_entreprise'],
            $stage_data['date_debut_stage'],
            $stage_data['date_fin_stage'],
            $stage_data['sujet_stage'],
            $stage_data['encadrant_entreprise'],
            $stage_data['email_encadrant'],
            $stage_data['telephone_encadrant'],
            $stage_data['id_maitre_stage'] ?? null,
            $etudiant_id
        ]);
    }

    public function createStageInfo($etudiant_id, $stage_data)
    {
        $sql = "INSERT INTO informations_stage (num_etu, id_entreprise, date_debut_stage, date_fin_stage, sujet_stage, encadrant_entreprise, email_encadrant, telephone_encadrant, id_maitre_stage) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $etudiant_id,
            $stage_data['nom_entreprise'],
            $stage_data['date_debut_stage'],
            $stage_data['date_fin_stage'],
            $stage_data['sujet_stage'],
            $stage_data['encadrant_entreprise'],
            $stage_data['email_encadrant'],
            $stage_data['telephone_encadrant'],
            $stage_data['id_maitre_stage'] ?? null
        ]);
    }
}