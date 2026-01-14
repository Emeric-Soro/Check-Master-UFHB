<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Semestre
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function ajouterSemestre($lib_semestre, $id_niv_etude)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO semestre (lib_semestre,id_niv_etude) VALUES (?,?)");
            return $stmt->execute([$lib_semestre, $id_niv_etude]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du semestre : " . $e->getMessage());
            return false;
        }
    }

    public function updateSemestre($id_semestre, $lib_semestre, $id_niv_etude)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE semestre SET lib_semestre = ?,id_niv_etude= ?  WHERE id_semestre = ?");
            return $stmt->execute([$lib_semestre, $id_niv_etude, $id_semestre]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du semestre : " . $e->getMessage());
            return false;
        }
    }

    public function deleteSemestre($id_semestre)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM semestre WHERE id_semestre = ?");
            return $stmt->execute([$id_semestre]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du semestre : " . $e->getMessage());
            return false;
        }
    }

    public function getSemestreById($id_semestre)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM semestre WHERE id_semestre = ?");
            $stmt->execute([$id_semestre]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du semestre par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getAllSemestres()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT s.*, n.lib_niv_etude FROM semestre s
         JOIN niveau_etude n ON s.id_niv_etude = n.id_niv_etude ORDER BY s.lib_semestre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les semestres : " . $e->getMessage());
            return [];
        }
    }

    public function getSemestresByNiveau(int $niveauId, ?int $studentId = null): array
    {
        try {
            $sql = "SELECT DISTINCT s.* 
                    FROM semestre s 
                    WHERE s.id_niv_etude = :niveau_id";
            
            $params = [':niveau_id' => $niveauId];
            
            if ($studentId) {
                $sql .= " AND s.id_semestre IN (
                    SELECT DISTINCT u.id_semestre 
                    FROM ue u 
                    JOIN notes n ON u.id_ue = n.id_ue 
                    WHERE n.num_etu = :student_id
                )";
                $params[':student_id'] = $studentId;
            }
            
            $sql .= " ORDER BY s.lib_semestre";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des semestres par niveau : " . $e->getMessage());
            return [];
        }
    }
}