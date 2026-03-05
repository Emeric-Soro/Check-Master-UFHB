<?php

class Jury
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @return array{date_deb: string, date_fin: string}|null
     */
    private function getAnneeBounds($id_annee_acad)
    {
        if ((int) $id_annee_acad <= 0) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT date_deb, date_fin
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
            ];
        } catch (PDOException $e) {
            error_log("Erreur Jury::getAnneeBounds: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Statistiques de participation des enseignants aux jurys.
     *
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getStatsByEnseignant($id_annee_acad, array $filters = [], $limit = 300, $offset = 0)
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

        if (!empty($filters['enseignant'])) {
            $where[] = "en.id_enseignant = :f_enseignant";
            $params[':f_enseignant'] = (string) $filters['enseignant'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(
                en.id_enseignant LIKE :f_search
                OR en.nom_enseignant LIKE :f_search
                OR en.prenom_enseignant LIKE :f_search
            )";
            $params[':f_search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $sql = "
            SELECT
                en.id_enseignant,
                en.nom_enseignant,
                en.prenom_enseignant,
                g.lib_grade,
                COUNT(DISTINCT ej.num_soutenance) AS total_soutenances,
                SUM(CASE WHEN qj.code_qltjury = 'PJ' THEN 1 ELSE 0 END) AS nb_president,
                SUM(CASE WHEN qj.code_qltjury = 'EX' THEN 1 ELSE 0 END) AS nb_examinateur,
                SUM(CASE WHEN qj.code_qltjury = 'DM' THEN 1 ELSE 0 END) AS nb_directeur,
                SUM(CASE WHEN qj.code_qltjury = 'EN' THEN 1 ELSE 0 END) AS nb_encadrant,
                ROUND(AVG(ev.note), 2) AS moyenne_notes_attribuees
            FROM enseignants en
            LEFT JOIN enseignant_jury ej ON ej.id_enseignant = en.id_enseignant
            LEFT JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
            LEFT JOIN programmer_soutenance ps ON ps.num_soutenance = ej.num_soutenance
            LEFT JOIN evaluer ev ON ev.num_etudiant = ps.num_etud
            LEFT JOIN avoir a ON a.id_enseignant = en.id_enseignant
            LEFT JOIN grade g ON g.id_grade = a.id_grade
            WHERE " . implode(' AND ', $where) . "
            GROUP BY en.id_enseignant, en.nom_enseignant, en.prenom_enseignant, g.lib_grade
            HAVING total_soutenances > 0
            ORDER BY total_soutenances DESC, en.nom_enseignant ASC
            LIMIT :cm_limit OFFSET :cm_offset
        ";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':cm_limit', max(1, (int) $limit), PDO::PARAM_INT);
            $stmt->bindValue(':cm_offset', max(0, (int) $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Jury::getStatsByEnseignant: " . $e->getMessage());
            return [];
        }
    }
}

