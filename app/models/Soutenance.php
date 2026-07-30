<?php

use CheckMaster\Models\BaseModel;

class Soutenance extends BaseModel
{
    protected const TABLE = 'programmer_soutenance';
    protected const PRIMARY_KEY = 'num_soutenance';

    /**
     * @return array{date_deb: string, date_fin: string, label: string}|null
     */
    private function getAnneeBounds($id_annee_acad)
    {
        if ((int) $id_annee_acad <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    date_deb,
                    date_fin,
                    CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS label
                FROM annee_academique
                WHERE id_annee_acad = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => (int) $id_annee_acad]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            return [
                'date_deb' => (string) $row['date_deb'],
                'date_fin' => (string) $row['date_fin'],
                'label' => (string) $row['label'],
            ];
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::getAnneeBounds: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getByAnnee($id_annee_acad, array $filters = [], $limit = 200, $offset = 0)
    {
        $bounds = $this->getAnneeBounds($id_annee_acad);
        if ($bounds === null) {
            return [];
        }

        $params = [
            ':date_deb' => $bounds['date_deb'],
            ':date_fin' => $bounds['date_fin'],
        ];

        $where = ["ps.date_soutenance BETWEEN :date_deb AND :date_fin"];

        if (!empty($filters['date_deb'])) {
            $where[] = "ps.date_soutenance >= :f_date_deb";
            $params[':f_date_deb'] = (string) $filters['date_deb'];
        }
        if (!empty($filters['date_fin'])) {
            $where[] = "ps.date_soutenance <= :f_date_fin";
            $params[':f_date_fin'] = (string) $filters['date_fin'];
        }
        if (!empty($filters['id_salle'])) {
            $where[] = "ps.id_salle = :f_salle";
            $params[':f_salle'] = (int) $filters['id_salle'];
        }
        if (!empty($filters['id_domaine'])) {
            $where[] = "ps.id_domaine = :f_domaine";
            $params[':f_domaine'] = (int) $filters['id_domaine'];
        }
        if (!empty($filters['id_session'])) {
            $where[] = "ps.id_session = :f_session";
            $params[':f_session'] = (int) $filters['id_session'];
        }
        if (!empty($filters['etudiant'])) {
            $where[] = "ps.num_etud = :f_etudiant";
            $params[':f_etudiant'] = (string) $filters['etudiant'];
        }
        if (!empty($filters['jury'])) {
            $where[] = "ej.id_enseignant = :f_jury";
            $params[':f_jury'] = (string) $filters['jury'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(
                e.num_carte_etud LIKE :f_search
                OR e.nom_etu LIKE :f_search
                OR e.prenom_etu LIKE :f_search
                OR ps.theme_soutenance LIKE :f_search
            )";
            $params[':f_search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $sql = "
            SELECT
                ps.num_soutenance,
                ps.date_soutenance,
                ps.heure_soutenance,
                ps.num_etud,
                ps.theme_soutenance,
                s.lib_salle,
                d.lib_domaine,
                se.lib_session,
                e.num_carte_etud,
                e.nom_etu,
                e.prenom_etu,
                GROUP_CONCAT(DISTINCT CONCAT(ce.lib_critere, ': ', ROUND(ev.note, 2)) SEPARATOR ' | ') AS notes_detail,
                ROUND(AVG(ev.note), 2) AS note_moyenne,
                GROUP_CONCAT(
                    DISTINCT CONCAT(en.nom_enseignant, ' ', en.prenom_enseignant, ' (', qj.lib_role, ')')
                    SEPARATOR ', '
                ) AS jury
            FROM programmer_soutenance ps
            LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
            LEFT JOIN salles s ON s.id_salle = ps.id_salle
            LEFT JOIN domaine d ON d.id_domaine = ps.id_domaine
            LEFT JOIN session se ON se.id_session = ps.id_session
            LEFT JOIN evaluer ev ON ev.num_etudiant = ps.num_etud
            LEFT JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
            LEFT JOIN enseignant_jury ej ON ej.num_soutenance = ps.num_soutenance
            LEFT JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
            LEFT JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
            WHERE " . implode(' AND ', $where) . "
            GROUP BY
                ps.num_soutenance,
                ps.date_soutenance,
                ps.heure_soutenance,
                ps.num_etud,
                ps.theme_soutenance,
                s.lib_salle,
                d.lib_domaine,
                se.lib_session,
                e.num_carte_etud,
                e.nom_etu,
                e.prenom_etu
            ORDER BY ps.date_soutenance DESC, ps.heure_soutenance ASC
            LIMIT :cm_limit OFFSET :cm_offset
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':cm_limit', max(1, (int) $limit), PDO::PARAM_INT);
            $stmt->bindValue(':cm_offset', max(0, (int) $offset), PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::getByAnnee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countByAnnee($id_annee_acad, array $filters = [])
    {
        $bounds = $this->getAnneeBounds($id_annee_acad);
        if ($bounds === null) {
            return 0;
        }

        $params = [
            ':date_deb' => $bounds['date_deb'],
            ':date_fin' => $bounds['date_fin'],
        ];
        $where = ["ps.date_soutenance BETWEEN :date_deb AND :date_fin"];

        if (!empty($filters['id_salle'])) {
            $where[] = "ps.id_salle = :f_salle";
            $params[':f_salle'] = (int) $filters['id_salle'];
        }
        if (!empty($filters['id_domaine'])) {
            $where[] = "ps.id_domaine = :f_domaine";
            $params[':f_domaine'] = (int) $filters['id_domaine'];
        }
        if (!empty($filters['id_session'])) {
            $where[] = "ps.id_session = :f_session";
            $params[':f_session'] = (int) $filters['id_session'];
        }
        if (!empty($filters['jury'])) {
            $where[] = "EXISTS (
                SELECT 1
                FROM enseignant_jury ej2
                WHERE ej2.num_soutenance = ps.num_soutenance
                  AND ej2.id_enseignant = :f_jury
            )";
            $params[':f_jury'] = (string) $filters['jury'];
        }

        $sql = "
            SELECT COUNT(DISTINCT ps.num_soutenance) AS total
            FROM programmer_soutenance ps
            WHERE " . implode(' AND ', $where);

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::countByAnnee: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * @param string $num_soutenance
     * @return array<string, mixed>|null
     */
    public function getById($num_soutenance)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    ps.*,
                    s.lib_salle,
                    d.lib_domaine,
                    se.lib_session
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON s.id_salle = ps.id_salle
                LEFT JOIN domaine d ON d.id_domaine = ps.id_domaine
                LEFT JOIN session se ON se.id_session = ps.id_session
                WHERE ps.num_soutenance = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $num_soutenance]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @param string $num_soutenance
     * @return array<int, array<string, mixed>>
     */
    public function getJuryBySoutenance($num_soutenance)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    en.id_enseignant,
                    en.nom_enseignant,
                    en.prenom_enseignant,
                    en.mail_enseignant,
                    qj.lib_role,
                    qj.id_role_jury AS code_qltjury,
                    g.lib_grade
                FROM enseignant_jury ej
                INNER JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                INNER JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
                LEFT JOIN avoir a ON a.id_enseignant = en.id_enseignant
                LEFT JOIN grade g ON g.id_grade = a.id_grade
                WHERE ej.num_soutenance = :id
                ORDER BY qj.id_role_jury ASC, en.nom_enseignant ASC
            ");
            $stmt->execute([':id' => $num_soutenance]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::getJuryBySoutenance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param string $num_etud
     * @param string $num_soutenance
     * @param int|null $id_annee_acad
     * @return array<int, array<string, mixed>>
     */
    public function getEvaluationBySoutenance($num_etud, $num_soutenance, $id_annee_acad = null)
    {
        $params = [':num_etud' => $num_etud];
        $jurySql = "";
        if (ctype_digit((string) $num_soutenance)) {
            $jurySql = " AND ev.num_jury = :num_jury";
            $params[':num_jury'] = (int) $num_soutenance;
        }

        $anneeJoin = "";
        if ($id_annee_acad !== null && (int) $id_annee_acad > 0) {
            $anneeJoin = " AND bc.id_annee_acad = :id_annee";
            $params[':id_annee'] = (int) $id_annee_acad;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    ce.lib_critere,
                    ev.note,
                    bc.bareme AS coefficient,
                    (ev.note * COALESCE(bc.bareme, 0) / 20) AS points
                FROM evaluer ev
                INNER JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                LEFT JOIN bareme_critere bc ON bc.id_critere = ce.id_critere {$anneeJoin}
                WHERE ev.num_etudiant = :num_etud {$jurySql}
                ORDER BY ce.id_critere ASC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::getEvaluationBySoutenance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vue calendrier: nombre de soutenances par jour.
     *
     * @param int $id_annee_acad
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarByAnnee($id_annee_acad)
    {
        $bounds = $this->getAnneeBounds($id_annee_acad);
        if ($bounds === null) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    DATE(ps.date_soutenance) AS jour,
                    COUNT(*) AS total_soutenances
                FROM programmer_soutenance ps
                WHERE ps.date_soutenance BETWEEN :date_deb AND :date_fin
                GROUP BY DATE(ps.date_soutenance)
                ORDER BY jour ASC
            ");
            $stmt->execute([
                ':date_deb' => $bounds['date_deb'],
                ':date_fin' => $bounds['date_fin'],
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Soutenance::getCalendarByAnnee: " . $e->getMessage());
            return [];
        }
    }
}
