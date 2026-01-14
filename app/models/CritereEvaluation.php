<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * CritereEvaluation Model - Gestion des critères d'évaluation et barèmes
 */
class CritereEvaluation
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Récupérer tous les critères avec leurs barèmes
     */
    public function getAllCriteres(): array
    {
        try {
            $sql = "
                SELECT 
                    ce.id_critere as id,
                    ce.lib_critere as libelle,
                    c.id_annee_acad as annee_id,
                    CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) as annee_lib,
                    c.bareme
                FROM critere_evaluation ce
                LEFT JOIN correspondre c ON ce.id_critere = c.id_critere
                LEFT JOIN annee_academique aa ON c.id_annee_acad = aa.id_annee_acad
                ORDER BY ce.id_critere, c.id_annee_acad DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getAllCriteres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer un nouveau critère
     */
    public function createCritere(string $libelle): int
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO critere_evaluation (lib_critere) VALUES (?)");
            $stmt->execute([trim($libelle)]);
            return (int)$this->pdo->lastInsertId();
        } catch (Exception $e) {
            $this->logger->error("Erreur createCritere: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour un critère
     */
    public function updateCritere(int $id, string $libelle): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE critere_evaluation SET lib_critere = ? WHERE id_critere = ?");
            return $stmt->execute([trim($libelle), $id]);
        } catch (Exception $e) {
            $this->logger->error("Erreur updateCritere: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer un critère
     */
    public function deleteCritere(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM critere_evaluation WHERE id_critere = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            $this->logger->error("Erreur deleteCritere: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ajouter un barème pour un critère et une année
     */
    public function addBareme(int $idCritere, string $idAnnee, int $bareme): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO correspondre (id_critere, id_annee_acad, bareme) VALUES (?, ?, ?)");
            return $stmt->execute([$idCritere, $idAnnee, $bareme]);
        } catch (Exception $e) {
            $this->logger->error("Erreur addBareme: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer tous les barèmes d'un critère
     */
    public function deleteBaremesByCritere(int $idCritere): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM correspondre WHERE id_critere = ?");
            return $stmt->execute([$idCritere]);
        } catch (Exception $e) {
            $this->logger->error("Erreur deleteBaremesByCritere: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les totaux actuels par année (pour validation)
     */
    public function getTotauxParAnnee(?int $critereIdExclure = null): array
    {
        try {
            $sqlExclusion = $critereIdExclure ? "AND ce.id_critere != ?" : "";
            $sql = "
                SELECT 
                    aa.id_annee_acad,
                    CONCAT(YEAR(aa.date_deb), ' - ', YEAR(aa.date_fin)) as lib_annee,
                    COALESCE(SUM(c.bareme), 0) as total_actuel
                FROM annee_academique aa
                LEFT JOIN correspondre c ON aa.id_annee_acad = c.id_annee_acad
                LEFT JOIN critere_evaluation ce ON c.id_critere = ce.id_critere
                WHERE 1=1 $sqlExclusion
                GROUP BY aa.id_annee_acad, aa.date_deb, aa.date_fin
                ORDER BY aa.date_deb DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            if ($critereIdExclure) {
                $stmt->execute([$critereIdExclure]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getTotauxParAnnee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si un critère existe
     */
    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM critere_evaluation WHERE id_critere = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
