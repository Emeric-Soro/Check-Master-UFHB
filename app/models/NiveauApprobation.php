<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class NiveauApprobation
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function ajouterNiveauApprobation($lib_approb)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO niveau_approbation (lib_approb) VALUES (?)");
            return $stmt->execute([$lib_approb]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du niveau d'approbation : " . $e->getMessage());
            return false;
        }
    }

    public function updateNiveauApprobation($id_approb, $lib_approb)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE niveau_approbation SET lib_approb = ? WHERE id_approb = ?");
            return $stmt->execute([$lib_approb, $id_approb]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du niveau d'approbation : " . $e->getMessage());
            return false;
        }
    }

    public function deleteNiveauApprobation($id_approb)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM niveau_approbation WHERE id_approb = ?");
            return $stmt->execute([$id_approb]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du niveau d'approbation : " . $e->getMessage());
            return false;
        }
    }

    public function getNiveauApprobationById($id_approb)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM niveau_approbation WHERE id_approb = ?");
            $stmt->execute([$id_approb]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du niveau d'approbation par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getAllNiveauxApprobation()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM niveau_approbation ORDER BY lib_approb");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les niveaux d'approbation : " . $e->getMessage());
            return [];
        }
    }
}