<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Evaluation
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Récupère les évaluations en attente pour un enseignant
     */
    public function getEvaluationsEnAttente($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id_evaluation,
                    e.id_cours,
                    c.nom_cours,
                    e.type_evaluation,
                    e.date_limite,
                    e.nombre_devoirs,
                    COUNT(d.id_devoir) as devoirs_soumis
                FROM evaluations e
                JOIN cours c ON e.id_cours = c.id_cours
                LEFT JOIN devoirs d ON e.id_evaluation = d.id_evaluation
                WHERE c.id_enseignant = ? 
                AND e.statut = 'en_attente'
                GROUP BY e.id_evaluation, e.id_cours, c.nom_cours, e.type_evaluation, e.date_limite, e.nombre_devoirs
                ORDER BY e.date_limite ASC
            ");
            $stmt->execute([$enseignantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération évaluations en attente: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les évaluations terminées pour un enseignant
     */
    public function getEvaluationsTerminees($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id_evaluation,
                    e.id_cours,
                    c.nom_cours,
                    e.type_evaluation,
                    e.date_limite,
                    e.nombre_devoirs,
                    COUNT(d.id_devoir) as devoirs_evalues
                FROM evaluations e
                JOIN cours c ON e.id_cours = c.id_cours
                LEFT JOIN devoirs d ON e.id_evaluation = d.id_evaluation AND d.statut = 'evalue'
                WHERE c.id_enseignant = ? 
                AND e.statut = 'terminee'
                GROUP BY e.id_evaluation, e.id_cours, c.nom_cours, e.type_evaluation, e.date_limite, e.nombre_devoirs
                ORDER BY e.date_limite DESC
            ");
            $stmt->execute([$enseignantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération évaluations terminées: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les échéances d'évaluation pour un enseignant
     */
    public function getEcheancesEvaluation($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id_evaluation,
                    e.id_cours,
                    c.nom_cours,
                    e.type_evaluation,
                    e.date_limite,
                    DATEDIFF(e.date_limite, CURDATE()) as jours_restants
                FROM evaluations e
                JOIN cours c ON e.id_cours = c.id_cours
                WHERE c.id_enseignant = ? 
                AND e.statut = 'en_attente'
                AND e.date_limite >= CURDATE()
                ORDER BY e.date_limite ASC
                LIMIT 10
            ");
            $stmt->execute([$enseignantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération échéances: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques d'évaluation pour un enseignant
     */
    public function getStatsEvaluations($enseignantId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT e.id_evaluation) as total_evaluations,
                    COUNT(CASE WHEN e.statut = 'en_attente' THEN 1 END) as evaluations_en_attente,
                    COUNT(CASE WHEN e.statut = 'terminee' THEN 1 END) as evaluations_terminees,
                    COUNT(DISTINCT d.id_devoir) as total_devoirs,
                    COUNT(CASE WHEN d.statut = 'soumis' THEN 1 END) as devoirs_soumis,
                    COUNT(CASE WHEN d.statut = 'evalue' THEN 1 END) as devoirs_evalues
                FROM evaluations e
                JOIN cours c ON e.id_cours = c.id_cours
                LEFT JOIN devoirs d ON e.id_evaluation = d.id_evaluation
                WHERE c.id_enseignant = ?
            ");
            $stmt->execute([$enseignantId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération stats évaluations: " . $e->getMessage());
            return [
                'total_evaluations' => 0,
                'evaluations_en_attente' => 0,
                'evaluations_terminees' => 0,
                'total_devoirs' => 0,
                'devoirs_soumis' => 0,
                'devoirs_evalues' => 0
            ];
        }
    }

    /**
     * Récupère les évaluations récentes pour un enseignant
     */
    public function getEvaluationsRecentes($enseignantId, $limit = 5)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id_evaluation,
                    e.id_cours,
                    c.nom_cours,
                    e.type_evaluation,
                    e.date_creation,
                    e.statut
                FROM evaluations e
                JOIN cours c ON e.id_cours = c.id_cours
                WHERE c.id_enseignant = ?
                ORDER BY e.date_creation DESC
                LIMIT ?
            ");
            $stmt->execute([$enseignantId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération évaluations récentes: " . $e->getMessage());
            return [];
        }
    }
}