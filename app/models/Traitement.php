<?php

class Traitement
{
    private $db;
    private $tableTraitement;
    private $tableRattacher;
    private $hasTableTraitement = false;
    private $hasTableRattacher = false;

    public function __construct($db) {
        $this->db = $db;
        $tableTraitement = $this->resolveExistingTable(['traitement', 'traitement_legacy']);
        $this->tableTraitement = $tableTraitement['table'];
        $this->hasTableTraitement = $tableTraitement['exists'];

        $tableRattacher = $this->resolveExistingTable(['rattacher', 'rattacher_legacy']);
        $this->tableRattacher = $tableRattacher['table'];
        $this->hasTableRattacher = $tableRattacher['exists'];
    }

    private function resolveExistingTable(array $candidates): array
    {
        foreach ($candidates as $table) {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->fetchColumn()) {
                return ['table' => $table, 'exists' => true];
            }
        }

        return ['table' => $candidates[0], 'exists' => false];
    }

    public function hasTraitementTable(): bool
    {
        return (bool) $this->hasTableTraitement;
    }

    private function logMissingTable(string $methodName, string $tableName): void
    {
        error_log("Traitement::{$methodName} - table '{$tableName}' introuvable dans le schéma courant.");
    }

    /**
     * Récupère tous les traitements
     * @return array Liste des traitements
     */
    public function getAllTraitements() {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return [];
        }

        $sql = "SELECT * FROM `{$this->tableTraitement}` ORDER BY ordre_traitement ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupère les traitements attribués à un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @return array Liste des traitements
     */
    public function getTraitementByGU($id_GU) {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return [];
        }
        if (!$this->hasTableRattacher) {
            $this->logMissingTable(__FUNCTION__, $this->tableRattacher);
            return [];
        }

        $sql = "SELECT t.* FROM `{$this->tableTraitement}` t 
                INNER JOIN `{$this->tableRattacher}` r ON t.id_traitement = r.id_traitement 
                WHERE r.id_GU = :id_GU 
                ORDER BY t.ordre_traitement ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_GU' => $id_GU]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
    

    public function getTraitementById($id_traitement) {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM `{$this->tableTraitement}` WHERE id_traitement = ?");
        $stmt->execute([$id_traitement]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getTraitementByLib($lib_traitement) {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM `{$this->tableTraitement}` WHERE lib_traitement = ?");
        $stmt->execute([$lib_traitement]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function addTraitement($lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement) {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO `{$this->tableTraitement}` (lib_traitement, label_traitement, icone_traitement, ordre_traitement) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$lib_traitement,$label_traitement,$icone_traitement,$ordre_traitement]);
    }

    

    public function updateTraitement($id_traitement, $lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement) {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return false;
        }

        $stmt = $this->db->prepare("UPDATE `{$this->tableTraitement}` SET lib_traitement = ?, label_traitement = ?, icone_traitement = ?, ordre_traitement = ? WHERE id_traitement = ?");
        return $stmt->execute([$lib_traitement,$label_traitement,$icone_traitement,$ordre_traitement,$id_traitement]);
    }

    public function deleteTraitement($id_traitement) {
        if (!$this->hasTableTraitement) {
            $this->logMissingTable(__FUNCTION__, $this->tableTraitement);
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM `{$this->tableTraitement}` WHERE id_traitement = ?");
        return $stmt->execute([$id_traitement]);
    }
}

    
    
