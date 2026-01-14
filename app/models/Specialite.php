<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Specialite
{
    private $pdo;
    private $logger;
    private $id_specialite;
    private $lib_specialite;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Getters
    public function getIdSpecialite()
    {
        return $this->id_specialite;
    }

    public function getLibSpecialite()
    {
        return $this->lib_specialite;
    }

    // Setters
    public function setIdSpecialite($id)
    {
        $this->id_specialite = $id;
    }

    public function setLibSpecialite($lib)
    {
        $this->lib_specialite = $lib;
    }

    // Méthodes CRUD
    public function getAllSpecialites()
    {
        try {
            $query = "SELECT * FROM specialite ORDER BY lib_specialite";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de toutes les spécialités : " . $e->getMessage());
            return [];
        }
    }

    public function getSpecialiteById($id)
    {
        try {
            $query = "SELECT * FROM specialite WHERE id_specialite = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de la spécialité par ID : " . $e->getMessage());
            return null;
        }
    }

    public function ajouterSpecialite($lib)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO specialite (lib_specialite) VALUES (?)");
            return $stmt->execute([$lib]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout de la spécialité : " . $e->getMessage());
            return false;
        }
    }

    public function updateSpecialite($id, $lib)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE specialite SET lib_specialite = ? WHERE id_specialite = ?");
            return $stmt->execute([$lib, $id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour de la spécialité : " . $e->getMessage());
            return false;
        }
    }

    public function deleteSpecialite($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM specialite WHERE id_specialite = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression de la spécialité : " . $e->getMessage());
            return false;
        }
    }
}