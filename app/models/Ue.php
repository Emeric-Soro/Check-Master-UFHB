<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Ue
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getAllUes()
    {
        try {
            $stmt = $this->pdo->query("SELECT ue.*, n.lib_niv_etude, s.lib_semestre, 
                              CONCAT(YEAR(a.date_deb), ' - ', YEAR(a.date_fin)) AS annee,
                              CONCAT(e.nom_enseignant, ' ', e.prenom_enseignant) AS nom_professeur
                              FROM ue 
                              JOIN niveau_etude n ON ue.id_niveau_etude = n.id_niv_etude
                              JOIN semestre s ON ue.id_semestre = s.id_semestre
                              JOIN annee_academique a ON ue.id_annee_academique = a.id_annee_acad
                              LEFT JOIN enseignants e ON ue.id_enseignant = e.id_enseignant
                              ORDER BY lib_ue");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de toutes les UE : " . $e->getMessage());
            return [];
        }
    }

    public function ajouterUe($lib_ue, $id_niveau_etude, $id_semestre, $id_annee_academique, $credit, $id_enseignant = null)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO ue (lib_ue, id_niveau_etude, id_semestre, id_annee_academique, credit, id_enseignant) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$lib_ue, $id_niveau_etude, $id_semestre, $id_annee_academique, $credit, $id_enseignant]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout de l'UE : " . $e->getMessage());
            return false;
        }
    }

    public function updateUe($id_ue, $lib_ue, $id_niveau_etude, $id_semestre, $id_annee_academique, $credit, $id_enseignant = null)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE ue SET lib_ue = ?, id_niveau_etude = ?, id_semestre = ?, id_annee_academique = ?, credit = ?, id_enseignant = ? WHERE id_ue = ?");
            return $stmt->execute([$lib_ue, $id_niveau_etude, $id_semestre, $id_annee_academique, $credit, $id_enseignant, $id_ue]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour de l'UE : " . $e->getMessage());
            return false;
        }
    }

    public function deleteUe($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM ue WHERE id_ue = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression de l'UE : " . $e->getMessage());
            return false;
        }
    }

    public function getUeById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ue WHERE id_ue = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'UE par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getUesByNiveau(int $niveauId): array
    {
        try {
            $sql = "SELECT DISTINCT u.*, s.lib_semestre 
                    FROM ue u 
                    JOIN semestre s ON u.id_semestre = s.id_semestre 
                    WHERE s.id_niv_etude = :niveau_id";
            
            $params = [':niveau_id' => $niveauId];
            
            $sql .= " ORDER BY s.lib_semestre, u.lib_ue";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des UE par niveau : " . $e->getMessage());
            return [];
        }
    }

    public function getUesByEnseignant($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT ue.*, n.lib_niv_etude, s.lib_semestre, CONCAT(YEAR(a.date_deb), ' - ', YEAR(a.date_fin)) AS annee
                FROM ue 
                JOIN niveau_etude n ON ue.id_niveau_etude = n.id_niv_etude
                JOIN semestre s ON ue.id_semestre = s.id_semestre
                JOIN annee_academique a ON ue.id_annee_academique = a.id_annee_acad
                WHERE ue.id_enseignant = ?
                ORDER BY lib_ue");
            $stmt->execute([$enseignantId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des UE par enseignant : " . $e->getMessage());
            return [];
        }
    }
}