<?php
/**
 * StatsEncadrementEnseignantService
 *
 * Fournit les statistiques d'encadrement pour un enseignant :
 *  - Nombre d'étudiants encadrés
 *  - Taux de réussite
 *  - Notes moyennes
 *
 * Tables : affecter, rapport_etudiants, valider
 */

namespace CheckMaster\Services;

use PDO;

class StatsEncadrementEnseignantService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les statistiques d'encadrement pour un enseignant.
     */
    public function getStats(string $idEnseignant, ?int $idAnnee = null): array
    {
        $params = [':id_enseignant' => $idEnseignant];
        $yearJoin = '';
        $notesJoin = 'LEFT JOIN notes n ON n.num_etu = re.num_etu';

        if ($idAnnee !== null && $idAnnee > 0) {
            $yearJoin = "
                INNER JOIN (
                    SELECT DISTINCT num_carte_etud
                    FROM inscriptions
                    WHERE id_annee_acad = :id_annee_inscriptions
                ) ins ON (ins.num_carte_etud = re.num_etu OR ins.num_carte_etud = (SELECT e.num_ident_etud FROM etudiants e WHERE e.num_carte_etud = re.num_etu) OR (SELECT e.num_ident_etud FROM etudiants e WHERE e.num_carte_etud = ins.num_carte_etud) = re.num_etu)
            ";
            $notesJoin = 'LEFT JOIN notes n ON n.num_etu = re.num_etu AND n.id_annee_acad = :id_annee_notes';
            $params[':id_annee_inscriptions'] = $idAnnee;
            $params[':id_annee_notes'] = $idAnnee;
        }

        $sqlDetails = "
            SELECT
                re.num_etu,
                e.nom_etu,
                e.prenom_etu,
                SUBSTRING_INDEX(
                    GROUP_CONCAT(COALESCE(re.nom_rapport, re.theme_rapport) ORDER BY re.id_rapport DESC SEPARATOR '||'),
                    '||',
                    1
                ) AS titre_rapport,
                GROUP_CONCAT(DISTINCT a.role ORDER BY a.role SEPARATOR ', ') AS role,
                SUBSTRING_INDEX(
                    GROUP_CONCAT(COALESCE(v.decision_validation, 'en_cours') ORDER BY v.date_validation DESC SEPARATOR ','),
                    ',',
                    1
                ) AS valide,
                MAX(COALESCE(n.moyenne_M2, n.moyenne_M1)) AS note
            FROM affecter a
            JOIN rapport_etudiants re ON a.id_rapport = re.id_rapport
            JOIN etudiants e ON (re.num_etu = e.num_carte_etud OR re.num_etu = e.num_ident_etud)
            {$yearJoin}
            LEFT JOIN valider v ON re.id_rapport = v.id_rapport
            {$notesJoin}
            WHERE a.id_enseignant = :id_enseignant
              AND a.role IN ('encadrant', 'encadreur', 'directeur')
            GROUP BY re.num_etu, e.nom_etu, e.prenom_etu
            ORDER BY e.nom_etu, e.prenom_etu
        ";

        $stmt = $this->db->prepare($sqlDetails);
        $stmt->execute($params);
        $detailEtudiants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalEtudiants = count($detailEtudiants);
        $totalValides = 0;
        $rolesRepartition = [];
        $notesValidees = [];

        foreach ($detailEtudiants as &$row) {
            $decision = strtolower(trim((string) ($row['valide'] ?? 'en_cours')));
            if ($decision === 'valider') {
                $totalValides++;
                if ($row['note'] !== null && $row['note'] !== '') {
                    $notesValidees[] = (float) $row['note'];
                }
            }

            $roles = array_filter(array_map('trim', explode(',', (string) ($row['role'] ?? ''))));
            foreach ($roles as $role) {
                if (!isset($rolesRepartition[$role])) {
                    $rolesRepartition[$role] = 0;
                }
                $rolesRepartition[$role]++;
            }
        }
        unset($row);

        $tauxReussite = $totalEtudiants > 0 ? round(($totalValides / $totalEtudiants) * 100, 1) : 0;
        $noteMoyenne = $notesValidees !== []
            ? round(array_sum($notesValidees) / count($notesValidees), 2)
            : null;

        return [
            'total_etudiants'  => $totalEtudiants,
            'total_valides'    => $totalValides,
            'taux_reussite'    => $tauxReussite,
            'note_moyenne'     => $noteMoyenne,
            'roles_repartition' => $rolesRepartition,
            'detail_etudiants' => $detailEtudiants,
        ];
    }

    /**
     * Retourne l'ID de l'enseignant connecté.
     */
    public function getConnectedEnseignantId(): ?string
    {
        $login = $_SESSION['login_utilisateur'] ?? '';
        if ($login === '') return null;

        try {
            $stmt = $this->db->prepare("SELECT id_enseignant FROM enseignants WHERE mail_enseignant = :login LIMIT 1");
            $stmt->execute([':login' => $login]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string) $row['id_enseignant'] : null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}
