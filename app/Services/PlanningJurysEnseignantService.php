<?php
/**
 * PlanningJurysEnseignantService
 *
 * Fournit les données pour le planning des jurys d'un enseignant :
 *  - Sessions de soutenance où l'enseignant est membre du jury
 *  - Regroupées par mois/semaine pour affichage calendaire
 */

namespace CheckMaster\Services;

use PDO;

class PlanningJurysEnseignantService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les jurys affectés à un enseignant pour une année donnée.
     *
     * @param string $idEnseignant Identifiant de l'enseignant
     * @param int|null $idAnnee   Année académique
     * @return array
     */
    public function getJurysByEnseignant(string $idEnseignant, ?int $idAnnee = null): array
    {
        if (trim($idEnseignant) === '') {
            return [];
        }

        $params = [':id_enseignant' => $idEnseignant];
        $whereAnnee = '';

        if ($idAnnee !== null && $idAnnee > 0) {
            $whereAnnee = 'AND ps.id_annee_acad = :id_annee';
            $params[':id_annee'] = $idAnnee;
        }

        $sql = "SELECT
                    ej.num_soutenance,
                    ps.num_soutenance,
                    ps.date_soutenance,
                    ps.heure_soutenance,
                    ps.num_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    s.lib_salle,
                    qj.lib_role AS lib_qualite_jury,
                    qj.id_role_jury AS id_qualite_jury
                FROM enseignant_jury ej
                JOIN programmer_soutenance ps ON ej.num_soutenance = ps.num_soutenance
                JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                LEFT JOIN qualite_jury qj ON ej.id_qualite_jury = qj.id_role_jury
                WHERE ej.id_enseignant = :id_enseignant
                $whereAnnee
                ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Regroupe les jurys par mois pour l'affichage calendaire.
     *
     * @param array $jurys
     * @return array ['mois_annee' => [['month_label' => ..., 'items' => [...]]]]
     */
    public function groupByMonth(array $jurys): array
    {
        $grouped = [];
        foreach ($jurys as $jury) {
            $date = $jury['date_soutenance'] ?? '';
            if ($date === '') continue;
            $monthKey = date('Y-m', strtotime($date));
            $monthLabel = date('F Y', strtotime($date));

            if (!isset($grouped[$monthKey])) {
                $grouped[$monthKey] = [
                    'month_key'   => $monthKey,
                    'month_label' => $monthLabel,
                    'items'       => [],
                ];
            }

            // On peut ajouter un numéro de semaine
            $weekNumber = (int) date('W', strtotime($date));
            $grouped[$monthKey]['items'][] = [
                'id_soutenance'     => $jury['num_soutenance'] ?? '',
                'date_soutenance'   => $date,
                'heure_soutenance'  => $jury['heure_soutenance'] ?? '',
                'week_number'       => $weekNumber,
                'jour_semaine'      => $this->frenchDayName(strtotime($date)),
                'jour_numero'       => (int) date('d', strtotime($date)),
                'etudiant'          => ($jury['nom_etu'] ?? '') . ' ' . ($jury['prenom_etu'] ?? ''),
                'num_etud'          => $jury['num_etud'] ?? '',
                'salle'             => $jury['lib_salle'] ?? 'Non définie',
                'qualite_jury'      => $jury['lib_qualite_jury'] ?? 'Membre',
            ];
        }

        // Trier par clé de mois
        ksort($grouped);
        return array_values($grouped);
    }

    /**
     * Statistiques rapides pour l'enseignant.
     */
    public function getStats(string $idEnseignant, ?int $idAnnee = null): array
    {
        $jurys = $this->getJurysByEnseignant($idEnseignant, $idAnnee);
        $total = count($jurys);
        $uniqueDates = [];
        $qualites = [];

        foreach ($jurys as $j) {
            $d = $j['date_soutenance'] ?? '';
            if ($d !== '') $uniqueDates[$d] = true;
            $q = $j['lib_qualite_jury'] ?? 'Membre';
            if (!isset($qualites[$q])) $qualites[$q] = 0;
            $qualites[$q]++;
        }

        return [
            'total_jurys'       => $total,
            'total_jours'       => count($uniqueDates),
            'qualites'          => $qualites,
        ];
    }

    // ---- Helpers ----

    private function getAnneeBounds(int $idAnnee): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT date_deb, date_fin
                FROM annee_academique
                WHERE id_annee_acad = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idAnnee]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    private function frenchDayName(int $timestamp): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $days[(int) date('w', $timestamp)] ?? '';
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

    /**
     * Récupère la liste de tous les enseignants (pour filtre).
     */
    public function getAllEnseignants(): array
    {
        try {
            $stmt = $this->db->query("SELECT id_enseignant, nom_enseignant, prenom_enseignant FROM enseignants ORDER BY nom_enseignant, prenom_enseignant");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }
}
