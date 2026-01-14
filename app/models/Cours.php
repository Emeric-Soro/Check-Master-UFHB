<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Cours
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Récupère tous les cours assignés à un enseignant
     */
    public function getCoursByEnseignant($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id_cours,
                    c.nom_cours,
                    c.code_cours,
                    c.niveau,
                    c.credits,
                    COUNT(ie.num_etu) as nombre_etudiants
                FROM cours c
                LEFT JOIN inscription_ecue ie ON c.id_cours = ie.id_ecue
                WHERE c.id_enseignant = ?
                GROUP BY c.id_cours, c.nom_cours, c.code_cours, c.niveau, c.credits
                ORDER BY c.nom_cours
            ");
            $stmt->execute([$enseignantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération cours enseignant: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le nombre d'étudiants inscrits à un cours
     */
    public function getNombreEtudiants($coursId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT ie.num_etu) as nombre_etudiants
                FROM inscription_ecue ie
                WHERE ie.id_ecue = ?
            ");
            $stmt->execute([$coursId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['nombre_etudiants'] ?? 0;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération nombre étudiants: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les statistiques des cours pour un enseignant
     */
    public function getStatsCoursEnseignant($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT c.id_cours) as total_cours,
                    SUM(c.credits) as total_credits,
                    COUNT(DISTINCT ie.num_etu) as total_etudiants
                FROM cours c
                LEFT JOIN inscription_ecue ie ON c.id_cours = ie.id_ecue
                WHERE c.id_enseignant = ?
            ");
            $stmt->execute([$enseignantId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération stats cours: " . $e->getMessage());
            return [
                'total_cours' => 0,
                'total_credits' => 0,
                'total_etudiants' => 0
            ];
        }
    }

    /**
     * Récupère les cours récents d'un enseignant
     */
    public function getCoursRecents($enseignantId, $limit = 5)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id_cours,
                    c.nom_cours,
                    c.code_cours,
                    c.niveau,
                    c.date_creation
                FROM cours c
                WHERE c.id_enseignant = ?
                ORDER BY c.date_creation DESC
                LIMIT ?
            ");
            $stmt->execute([$enseignantId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération cours récents: " . $e->getMessage());
            return [];
        }
    }
}