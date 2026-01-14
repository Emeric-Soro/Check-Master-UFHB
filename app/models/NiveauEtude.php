<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class NiveauEtude
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getAllNiveauxEtudes()
    {
        try {
            $stmt = $this->pdo->query("SELECT n.*, e.nom_enseignant, e.prenom_enseignant 
            FROM niveau_etude n
            LEFT JOIN enseignants e ON n.id_enseignant = e.id_enseignant ORDER BY n.lib_niv_etude");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les niveaux d'études : " . $e->getMessage());
            return [];
        }
    }

    public function getAll()
    {
        return $this->getAllNiveauxEtudes();
    }

    public function ajouterNiveauEtude($lib, $montant_scolarite, $montant_inscription, $id_enseignant = null)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO niveau_etude (lib_niv_etude, montant_scolarite, montant_inscription, id_enseignant) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$lib, $montant_scolarite, $montant_inscription, $id_enseignant]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du niveau d'étude : " . $e->getMessage());
            return false;
        }
    }

    public function updateNiveauEtude($id, $lib, $montant_scolarite, $montant_inscription, $id_enseignant = null)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE niveau_etude SET lib_niv_etude = ?, montant_scolarite = ?, montant_inscription = ?, id_enseignant = ? WHERE id_niv_etude = ?");
            return $stmt->execute([$lib, $montant_scolarite, $montant_inscription, $id_enseignant, $id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du niveau d'étude : " . $e->getMessage());
            return false;
        }
    }

    public function deleteNiveauEtude($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM niveau_etude WHERE id_niv_etude = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du niveau d'étude : " . $e->getMessage());
            return false;
        }
    }

    public function getNiveauEtudeById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT n.*, e.nom_enseignant, e.prenom_enseignant FROM niveau_etude n LEFT JOIN enseignants e ON n.id_enseignant = e.id_enseignant WHERE n.id_niv_etude = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du niveau d'étude par ID : " . $e->getMessage());
            return null;
        }
    }
}