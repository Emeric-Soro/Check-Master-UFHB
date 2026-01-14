<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;
use Exception;

class Valider
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Insère une nouvelle décision de validation
     */
    public function insererDecision($id_enseignant, $id_rapport, $decision_validation, $commentaire_validation = '')
    {
        try {
            // Vérifier que la décision est valide
            if (!in_array($decision_validation, ['valider', 'rejeter'])) {
                throw new Exception("Décision invalide: $decision_validation");
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation)
                VALUES (?, ?, NOW(), ?, ?)
            ");

            $result = $stmt->execute([$id_enseignant, $id_rapport, $commentaire_validation, $decision_validation]);

            if (!$result) {
                throw new Exception("Échec de l'insertion dans la table valider");
            }

            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur Valider::insererDecision: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère toutes les décisions pour un rapport
     */
    public function getByRapport($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    v.id_enseignant,
                    v.id_rapport,
                    v.date_validation,
                    v.commentaire_validation,
                    v.decision_validation,
                    e.nom_enseignant,
                    e.prenom_enseignant
                FROM valider v
                JOIN enseignants e ON v.id_enseignant = e.id_enseignant
                WHERE v.id_rapport = ?
                ORDER BY v.date_validation DESC
            ");

            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des décisions par rapport : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la dernière décision pour un rapport
     */
    public function getDerniereDecision($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    v.id_enseignant,
                    v.id_rapport,
                    v.date_validation,
                    v.commentaire_validation,
                    v.decision_validation,
                    e.nom_enseignant,
                    e.prenom_enseignant
                FROM valider v
                JOIN enseignants e ON v.id_enseignant = e.id_enseignant
                WHERE v.id_rapport = ?
                ORDER BY v.date_validation DESC
                LIMIT 1
            ");

            $stmt->execute([$id_rapport]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de la dernière décision : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un rapport a été validé
     */
    public function estValide($id_rapport)
    {
        $derniereDecision = $this->getDerniereDecision($id_rapport);
        return $derniereDecision && $derniereDecision['decision_validation'] === 'valider';
    }

    /**
     * Vérifie si un rapport a été rejeté
     */
    public function estRejete($id_rapport)
    {
        $derniereDecision = $this->getDerniereDecision($id_rapport);
        return $derniereDecision && $derniereDecision['decision_validation'] === 'rejeter';
    }

    /**
     * Récupère la liste des rapports validés ou rejetés (décision la plus récente)
     * Exclut les rapports qui ont déjà un compte rendu
     */
    public function getRapportsValides()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT r.id_rapport, r.num_etu, r.theme_rapport, e.prenom_etu, e.nom_etu, v2.decision_validation
                FROM rapport_etudiants r 
                JOIN etudiants e ON r.num_etu = e.num_etu 
                JOIN (
                    SELECT id_rapport, MAX(date_validation) as last_validation 
                    FROM valider 
                    GROUP BY id_rapport
                ) v1 ON r.id_rapport = v1.id_rapport 
                JOIN valider v2 ON v2.id_rapport = v1.id_rapport AND v2.date_validation = v1.last_validation
                LEFT JOIN compte_rendu_rapport crr ON r.id_rapport = crr.id_rapport
                WHERE v2.decision_validation IN ('valider', 'rejeter')
                AND crr.id_rapport IS NULL
                ORDER BY v2.decision_validation DESC, r.theme_rapport
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des rapports validés : " . $e->getMessage());
            return [];
        }
    }
}