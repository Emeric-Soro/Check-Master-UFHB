<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class NiveauAccesDonnees
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getAllNiveauxAccesDonnees()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM niveau_acces_donnees ORDER BY lib_niveau_acces_donnees");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les niveaux d'accès aux données : " . $e->getMessage());
            return [];
        }
    }

    public function ajouterNiveauAccesDonnees($lib)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO niveau_acces_donnees (lib_niveau_acces_donnees) VALUES (?)");
            return $stmt->execute([$lib]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du niveau d'accès aux données : " . $e->getMessage());
            return false;
        }
    }

    public function updateNiveauAccesDonnees($id, $lib)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE niveau_acces_donnees SET lib_niveau_acces_donnees = ? WHERE id_niveau_acces_donnees = ?");
            return $stmt->execute([$lib, $id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du niveau d'accès aux données : " . $e->getMessage());
            return false;
        }
    }

    public function deleteNiveauAccesDonnees($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM niveau_acces_donnees WHERE id_niveau_acces_donnees = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du niveau d'accès aux données : " . $e->getMessage());
            return false;
        }
    }

    public function getNiveauAccesDonneesById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM niveau_acces_donnees WHERE id_niveau_acces_donnees = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du niveau d'accès aux données par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getLastNiveauAccesDonnees()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM niveau_acces_donnees ORDER BY id_niveau_acces_donnees DESC LIMIT 1");
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du dernier niveau d'accès aux données : " . $e->getMessage());
            return null;
        }
    }
}