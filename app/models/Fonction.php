<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Fonction
{
    private $pdo;
    private $logger;
    private $id_fonction;
    private $lib_fonction;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Getters
    public function getIdFonction() { return $this->id_fonction; }
    public function getLibFonction() { return $this->lib_fonction; }

    // Setters
    public function setIdFonction($id) { $this->id_fonction = $id; }
    public function setLibFonction($lib) { $this->lib_fonction = $lib; }

    public function ajouterFonction($lib_fonction)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO fonction (lib_fonction) VALUES (?)");
            return $stmt->execute([$lib_fonction]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout de la fonction : " . $e->getMessage());
            return false;
        }
    }

    public function updateFonction($id_fonction, $lib_fonction)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE fonction SET lib_fonction = ? WHERE id_fonction = ?");
            return $stmt->execute([$lib_fonction, $id_fonction]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour de la fonction : " . $e->getMessage());
            return false;
        }
    }

    public function deleteFonction($id_fonction)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM fonction WHERE id_fonction = ?");
            return $stmt->execute([$id_fonction]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression de la fonction : " . $e->getMessage());
            return false;
        }
    }

    public function getFonctionById($id)
    {
        try {
            $query = "SELECT * FROM fonction WHERE id_fonction = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de la fonction par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getAllFonctions()
    {
        try {
            $query = "SELECT * FROM fonction ORDER BY lib_fonction";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de toutes les fonctions : " . $e->getMessage());
            return [];
        }
    }
}