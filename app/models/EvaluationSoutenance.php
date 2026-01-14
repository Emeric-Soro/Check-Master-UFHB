<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * EvaluationSoutenance Model - Gestion de l'évaluation des soutenances
 */
class EvaluationSoutenance
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Récupérer toutes les soutenances programmées pour évaluation
     */
    public function getSoutenancesProgrammees(): array
    {
        try {
            $sql = "
                SELECT 
                    p.id_programmation,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    e.num_etu,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_etu as matricule_etudiant,
                    e.promotion_etu,
                    s.lib_salle as nom_salle,
                    (SELECT CONCAT(ens1.prenom_enseignant, ' ', ens1.nom_enseignant) 
                     FROM composer_jury cj1 
                     JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                     JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                     WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                     LIMIT 1) as president_nom,
                    (SELECT CONCAT(ens2.prenom_enseignant, ' ', ens2.nom_enseignant) 
                     FROM composer_jury cj2 
                     JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                     JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                     WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                     LIMIT 1) as examinateur_nom,
                    (SELECT CONCAT(ens3.prenom_enseignant, ' ', ens3.nom_enseignant) 
                     FROM composer_jury cj3 
                     JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                     JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                     WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                     LIMIT 1) as directeur_nom,
                    (SELECT CONCAT(ens4.prenom_enseignant, ' ', ens4.nom_enseignant) 
                     FROM composer_jury cj4 
                     JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                     JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                     WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                     LIMIT 1) as encadreur_nom,
                    ist.encadrant_entreprise as maitre_stage_nom,
                    (SELECT COUNT(*) FROM evaluer ev WHERE ev.num_etudiant = e.num_etu) as est_evalue,
                    (SELECT SUM(ev.note) FROM evaluer ev WHERE ev.num_etudiant = e.num_etu) as note_finale
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                WHERE p.id_salle IS NOT NULL 
                AND p.date_soutenance IS NOT NULL 
                AND p.heure_soutenance IS NOT NULL
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getSoutenancesProgrammees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les évaluations d'un étudiant
     */
    public function getEvaluations(string $numEtu): array
    {
        try {
            $sql = "
                SELECT e.id_critere, e.note, e.date_eval, c.lib_critere
                FROM evaluer e
                JOIN critere_evaluation c ON e.id_critere = c.id_critere
                WHERE e.num_etudiant = ?
                ORDER BY e.date_eval DESC, e.id_critere
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$numEtu]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getEvaluations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprimer les évaluations d'un étudiant
     */
    public function deleteEvaluations(string $numEtu, ?int $numJury = null): bool
    {
        try {
            $sql = "DELETE FROM evaluer WHERE num_etudiant = ?";
            $params = [$numEtu];
            if ($numJury !== null) {
                $sql .= " AND num_jury = ?";
                $params[] = $numJury;
            }
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            $this->logger->error("Erreur deleteEvaluations: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insérer une note d'évaluation
     */
    public function saveNote(string $numEtu, int $numJury, int $idCritere, string $dateEval, float $note): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$numEtu, $numJury, $idCritere, $dateEval, $note]);
        } catch (Exception $e) {
            $this->logger->error("Erreur saveNote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer le numéro de jury d'un étudiant
     */
    public function getNumJury(string $numEtu): ?int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT num_jury FROM programmer WHERE num_etud = ?");
            $stmt->execute([$numEtu]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int)$res['num_jury'] : null;
        } catch (Exception $e) {
            $this->logger->error("Erreur getNumJury: " . $e->getMessage());
            return null;
        }
    }
}
