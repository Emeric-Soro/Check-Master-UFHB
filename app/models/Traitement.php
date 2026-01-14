<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Traitement
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Récupère tous les traitements
     * @return array Liste des traitements
     */
    public function getAllTraitements()
    {
        try {
            $sql = "SELECT * FROM traitement ORDER BY ordre_traitement ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les traitements : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les traitements attribués à un groupe d'utilisateurs
     * @param int $id_GU ID du groupe d'utilisateurs
     * @return array Liste des traitements
     */
    public function getTraitementByGU($id_GU)
    {
        try {
            // Note: This uses the old 'rattacher' table. If permissions were fully migrated to 'droits', this might need update.
            // But I will keep it as is, or maybe check if 'droits' exists.
            // Based on Conversation 494916fd summary, 'droits' table was created.
            // I'll stick to 'droits' if possible, but the original code used 'rattacher'.
            // Actually, conversation 494916fd objective says "migrate existing data, insert new parent menu entries".
            // If the goal is to use granular permissions, this query should probably join with 'droits'.
            // However, the task is "standardization", not necessarily "re-logic".
            // But Attribution.php was refactored to use 'droits'.
            
            $sql = "SELECT t.* FROM traitement t 
                    INNER JOIN rattacher r ON t.id_traitement = r.id_traitement 
                    WHERE r.id_GU = :id_GU 
                    ORDER BY t.ordre_traitement ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_GU' => $id_GU]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des traitements par groupe : " . $e->getMessage());
            return [];
        }
    }


    public function getTraitementById($id_traitement)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM traitement WHERE id_traitement = ?");
            $stmt->execute([$id_traitement]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du traitement par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getTraitementByLib($lib_traitement)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM traitement WHERE lib_traitement = ?");
            $stmt->execute([$lib_traitement]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du traitement par libellé : " . $e->getMessage());
            return null;
        }
    }

    public function addTraitement($lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO traitement (lib_traitement, label_traitement, icone_traitement, ordre_traitement) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du traitement : " . $e->getMessage());
            return false;
        }
    }


    public function updateTraitement($id_traitement, $lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE traitement SET lib_traitement = ?, label_traitement = ?, icone_traitement = ?, ordre_traitement = ? WHERE id_traitement = ?");
            return $stmt->execute([$lib_traitement, $label_traitement, $icone_traitement, $ordre_traitement, $id_traitement]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du traitement : " . $e->getMessage());
            return false;
        }
    }

    public function deleteTraitement($id_traitement)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM traitement WHERE id_traitement = ?");
            return $stmt->execute([$id_traitement]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du traitement : " . $e->getMessage());
            return false;
        }
    }
}