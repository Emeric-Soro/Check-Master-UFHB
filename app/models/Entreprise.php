<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Entreprise
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function ajouterEntreprise($lib_entreprise)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO entreprises (lib_entreprise) VALUES (?)");
            return $stmt->execute([$lib_entreprise]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout de l'entreprise : " . $e->getMessage());
            return false;
        }
    }

    public function updateEntreprise($id_entreprise, $lib_entreprise)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE entreprises SET lib_entreprise = ? WHERE id_entreprise = ?");
            return $stmt->execute([$lib_entreprise, $id_entreprise]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour de l'entreprise : " . $e->getMessage());
            return false;
        }
    }

    public function deleteEntreprise($id_entreprise)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM entreprises WHERE id_entreprise = ?");
            return $stmt->execute([$id_entreprise]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression de l'entreprise : " . $e->getMessage());
            return false;
        }
    }

    public function getEntrepriseById($id)
    {
        try {
            $sql = "SELECT * FROM entreprises WHERE id_entreprise = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'entreprise par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getEntrepriseByLibelle($lib_entreprise)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id_entreprise FROM entreprises WHERE lib_entreprise = ?");
            $stmt->execute([$lib_entreprise]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'entreprise par libellé : " . $e->getMessage());
            return null;
        }
    }

    public function getAllEntreprises()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM entreprises ORDER BY lib_entreprise");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de toutes les entreprises : " . $e->getMessage());
            return [];
        }
    }


    public function getLastInsertedId()
    {
        try {
            $sql = "SELECT id_entreprise FROM entreprises ORDER BY id_entreprise DESC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id_entreprise'] : null;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du dernier ID inséré : " . $e->getMessage());
            return null;
        }
    }
}