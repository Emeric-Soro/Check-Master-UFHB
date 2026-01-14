<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Grade
{
    private $pdo;
    private $logger;
    private $id_grade;
    private $lib_grade;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Getters
    public function getIdGrade() { return $this->id_grade; }
    public function getLibGrade() { return $this->lib_grade; }

    // Setters
    public function setIdGrade($id) { $this->id_grade = $id; }
    public function setLibGrade($lib) { $this->lib_grade = $lib; }

    // Méthodes CRUD
    public function getAllGrades()
    {
        try {
            $query = "SELECT * FROM grade ORDER BY lib_grade";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les grades : " . $e->getMessage());
            return [];
        }
    }

    public function getGradeById($id)
    {
        try {
            $query = "SELECT * FROM grade WHERE id_grade = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du grade par ID : " . $e->getMessage());
            return null;
        }
    }

    // Ajouter un nouveau grade
    public function ajouterGrade($lib_grade)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO grade (lib_grade) VALUES (?)");
            return $stmt->execute([$lib_grade]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du grade : " . $e->getMessage());
            return false;
        }
    }

    //Modifier un grade
    public function updateGrade($id_grade, $lib_grade)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE grade SET lib_grade = ? WHERE id_grade = ?");
            return $stmt->execute([$lib_grade, $id_grade]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur pendant la maj du grade : " . $e->getMessage());
            return false;
        }
    }

    // Supprimer un grade
    public function deleteGrade($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM grade WHERE id_grade = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du grade : " . $e->getMessage());
            return false;
        }
    }
}