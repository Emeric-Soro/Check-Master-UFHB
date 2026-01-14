<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class StatutJury
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getAllStatutsJury()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM statut_jury ORDER BY lib_jury");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les statuts de jury : " . $e->getMessage());
            return [];
        }
    }

    public function ajouterStatutJury($lib)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO statut_jury (lib_jury) VALUES (?)");
            return $stmt->execute([$lib]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du statut de jury : " . $e->getMessage());
            return false;
        }
    }

    public function updateStatutJury($id, $lib)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE statut_jury SET lib_jury = ? WHERE id_jury = ?");
            return $stmt->execute([$lib, $id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du statut de jury : " . $e->getMessage());
            return false;
        }
    }

    public function deleteStatutJury($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM statut_jury WHERE id_jury = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du statut de jury : " . $e->getMessage());
            return false;
        }
    }

    public function getStatutJuryById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM statut_jury WHERE id_jury = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du statut de jury par ID : " . $e->getMessage());
            return null;
        }
    }
}