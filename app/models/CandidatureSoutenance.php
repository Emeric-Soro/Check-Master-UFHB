<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class CandidatureSoutenance
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getStatutByEtudiant($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT statut_candidature FROM candidature_soutenance WHERE num_etu = ? ORDER BY date_candidature DESC LIMIT 1");
            $stmt->execute([$num_etu]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['statut_candidature'] : null;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du statut de candidature : " . $e->getMessage());
            return null;
        }
    }
}