<?php



class Attribution{
  
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Ajoute une attribution de traitement à un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @return bool Succès de l'opération
     */
    public function ajouterAttribution($id_GU, $id_traitement) {
        $sql = "INSERT INTO rattacher (id_GU, id_traitement) VALUES (:id_GU, :id_traitement)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_GU' => $id_GU,
            ':id_traitement' => $id_traitement
        ]);
    }

    /**
     * Supprime toutes les attributions d'un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @return bool Succès de l'opération
     */
    public function deleteAttribution($id_GU) {
        $sql = "DELETE FROM rattacher WHERE id_GU = :id_GU";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_GU' => $id_GU]);
    }

    /**
     * Récupère tous les traitements attribués à un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @return array Liste des traitements
     */
    public function getTraitementsByGroupe($id_GU) {
        try {
            $sql = "SELECT t.* FROM traitement t 
                    INNER JOIN rattacher r ON t.id_traitement = r.id_traitement 
                    WHERE r.id_GU = :id_GU
                    ORDER BY t.ordre_traitement ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_GU' => $id_GU]);
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur dans getTraitementsByGroupe: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un traitement est attribué à un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @param int $id_traitement ID du traitement
     * @return bool True si le traitement est attribué
     */
    public function isTraitementAttribue($id_GU, $id_traitement) {
        $sql = "SELECT COUNT(*) FROM rattacher 
                WHERE id_GU = :id_GU AND id_traitement = :id_traitement";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_GU' => $id_GU,
            ':id_traitement' => $id_traitement
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function updateAttribution($id_GU, $id_traitement)
    {
        $sql = "UPDATE rattacher SET id_traitement = :id_traitement WHERE id_GU = :id_GU";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_GU' => $this->$id_GU,
            ':id_traitement' => $this->$id_traitement
        ]);
    }
    public function getAttributionById($id_attribution)
    {
        $stmt = $this->db->prepare("SELECT * FROM rattacher WHERE id_GU = ?");
        $stmt->execute([$id_attribution]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    public function getAllAttributionS()
    {
        $stmt = $this->db->prepare("SELECT * FROM rattacher ORDER BY id_GU");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    
}