<?php

/**
 * Archive Model - Handles archived student data retrieval and management
 */
class Archive
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get student history with filters
     */
    public function getStudentHistory($anneeAcad = null, $statut = null, $search = null, $limit = 50, $offset = 0)
    {
        $anneeExpr = "COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), CASE WHEN e.promotion_etu IS NOT NULL AND e.promotion_etu != '' THEN CONCAT(e.promotion_etu, '-', CAST(e.promotion_etu AS UNSIGNED) + 1) END)";
        $sql = "
            SELECT DISTINCT
                e.num_carte_etud as matricule,
                e.nom_etu as nom,
                e.prenom_etu as prenoms,
                r.theme_rapport as theme,
                ent.lib_long_entreprise as entreprise,
                r.statut_rapport as statut,
                aa.date_deb,
                aa.date_fin,
                " . $anneeExpr . " as annee_academique,
                r.id_rapport
            FROM etudiants e
            LEFT JOIN rapport_etudiants r ON e.num_carte_etud = r.num_etu
            LEFT JOIN informations_stage ist ON e.num_carte_etud = ist.num_etu
            LEFT JOIN entreprises ent ON ist.id_entreprise = ent.id_entreprise
            LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE 1=1
        ";

        $params = [];

        if ($anneeAcad) {
            $sql .= " AND " . $anneeExpr . " = :annee_acad";
            $params['annee_acad'] = $anneeAcad;
        }

        if ($statut) {
            $sql .= " AND r.statut_rapport = :statut";
            $params['statut'] = $statut;
        }

        if ($search) {
            $sql .= " AND (e.nom_etu LIKE :search OR e.prenom_etu LIKE :search OR e.num_carte_etud LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY COALESCE(aa.date_deb, STR_TO_DATE(CONCAT(e.promotion_etu, '-09-01'), '%Y-%m-%d')) DESC, e.nom_etu ASC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count total students for pagination
     */
    public function countStudents($anneeAcad = null, $statut = null, $search = null)
    {
        $anneeExpr = "COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), CASE WHEN e.promotion_etu IS NOT NULL AND e.promotion_etu != '' THEN CONCAT(e.promotion_etu, '-', CAST(e.promotion_etu AS UNSIGNED) + 1) END)";
        $sql = "
            SELECT COUNT(DISTINCT e.num_carte_etud) as total
            FROM etudiants e
            LEFT JOIN rapport_etudiants r ON e.num_carte_etud = r.num_etu
            LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE 1=1
        ";

        $params = [];

        if ($anneeAcad) {
            $sql .= " AND " . $anneeExpr . " = :annee_acad";
            $params['annee_acad'] = $anneeAcad;
        }

        if ($statut) {
            $sql .= " AND r.statut_rapport = :statut";
            $params['statut'] = $statut;
        }

        if ($search) {
            $sql .= " AND (e.nom_etu LIKE :search OR e.prenom_etu LIKE :search OR e.num_carte_etud LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error counting students: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get complete student file/dossier
     */
    public function getStudentCompleteFile($numEtu)
    {
        try {
            // Student basic info
            $student = $this->getStudentInfo($numEtu);
            if (!$student) {
                return null;
            }

            // Stage info
            $student['stage'] = $this->getStageInfo($numEtu);

            // Rapport/Theme info
            $student['rapport'] = $this->getRapportInfo($numEtu);

            // Encadrement
            $student['encadrement'] = $this->getEncadrementInfo($numEtu);

            // Soutenance info
            $student['soutenance'] = $this->getSoutenanceInfo($numEtu);

            // Averages for grade report
            $student['moyenne_m1'] = $this->getMoyenneM1($numEtu);
            $student['moyenne_m2_s1'] = $this->getMoyenneM2S1($numEtu);

            return $student;
        } catch (PDOException $e) {
            error_log("Error getting complete student file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get M1 Average
     */
    private function getMoyenneM1($numEtu)
    {
        $sql = "
            SELECT SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne
            FROM notes n
            INNER JOIN ue u ON n.id_ue = u.id_ue
            WHERE n.num_etu = :num_etu AND u.id_niveau_etude = 10 AND n.moyenne IS NOT NULL
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['num_etu' => $numEtu]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['moyenne'] ?? null;
    }

    /**
     * Get M2 S1 Average
     */
    private function getMoyenneM2S1($numEtu)
    {
        $sql = "
            SELECT SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne
            FROM notes n
            INNER JOIN ue u ON n.id_ue = u.id_ue
            WHERE n.num_etu = :num_etu AND u.id_semestre > 21 AND n.moyenne IS NOT NULL
            GROUP BY u.id_semestre
            ORDER BY u.id_semestre ASC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['num_etu' => $numEtu]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['moyenne'] ?? null;
    }

    /**
     * Get student basic info
     */
    private function getStudentInfo($numEtu)
    {
        $anneeExpr = "COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), CASE WHEN e.promotion_etu IS NOT NULL AND e.promotion_etu != '' THEN CONCAT(e.promotion_etu, '-', CAST(e.promotion_etu AS UNSIGNED) + 1) END)";
        $sql = "
            SELECT 
                e.*,
                i.date_inscription,
                " . $anneeExpr . " as annee_academique
            FROM etudiants e
            LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE e.num_carte_etud = :num_etu
            ORDER BY i.date_inscription DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['num_etu' => $numEtu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get stage information
     */
    private function getStageInfo($numEtu)
    {
        $sql = "
            SELECT ist.*, ent.lib_long_entreprise, ent.lib_court_en
            FROM informations_stage ist
            LEFT JOIN entreprises ent ON ist.id_entreprise = ent.id_entreprise
            WHERE ist.num_etu = :num_etu
            ORDER BY ist.date_debut_stage DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['num_etu' => $numEtu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get rapport information
     */
    private function getRapportInfo($numEtu)
    {
        $sql = "
            SELECT r.*, v.date_validation, v.commentaire_validation, v.decision_validation
            FROM rapport_etudiants r
            LEFT JOIN valider v ON r.id_rapport = v.id_rapport
            WHERE r.num_etu = :num_etu
            ORDER BY r.date_rapport DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['num_etu' => $numEtu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get encadrement information
     */
    private function getEncadrementInfo($numEtu)
    {
        $sql = "
            SELECT 
                a.role,
                e.id_enseignant,
                e.nom_enseignant,
                e.prenom_enseignant,
                e.mail_enseignant
            FROM rapport_etudiants r
            INNER JOIN affecter a ON r.id_rapport = a.id_rapport
            INNER JOIN enseignants e ON a.id_enseignant = e.id_enseignant
            WHERE r.num_etu = :num_etu
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['num_etu' => $numEtu]);

        $encadrement = [
            'encadrant' => null,
            'directeur' => null
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['role'] === 'encadrant') {
                $encadrement['encadrant'] = $row;
            } elseif ($row['role'] === 'directeur') {
                $encadrement['directeur'] = $row;
            }
        }

        return $encadrement;
    }

    /**
     * Get soutenance information
     */
    private function getSoutenanceInfo($numEtu)
    {
        $sql = "
            SELECT 
                p.*,
                s.lib_salle,
                GROUP_CONCAT(
                    CONCAT(e.nom_enseignant, ' ', e.prenom_enseignant, ':', rj.lib_role) 
                    SEPARATOR '|'
                ) as jury_members
            FROM programmer p
            LEFT JOIN salles s ON p.id_salle = s.id_salle
            LEFT JOIN composer_jury cj ON p.num_jury = cj.num_jury
            LEFT JOIN enseignants e ON cj.id_enseignant = e.id_enseignant
            LEFT JOIN roles_jury rj ON cj.id_qualite_jury = rj.id_role_jury
            WHERE p.num_etud = :num_etu
            GROUP BY p.id_programmation
            ORDER BY p.date_soutenance DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':num_etu', $numEtu);
        $stmt->execute();
        $soutenance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($soutenance) {
            // Get notes
            $soutenance['notes'] = $this->getNotesEtudiant($numEtu, $soutenance['num_jury']);
        }

        return $soutenance;
    }

    /**
     * Get student notes/evaluations
     */
    private function getNotesEtudiant($numEtu, $numJury)
    {
        $sql = "
            SELECT 
                ev.*,
                ce.lib_critere
            FROM evaluer ev
            LEFT JOIN critere_evaluation ce ON ev.id_critere = ce.id_critere
            WHERE ev.num_etudiant = :num_etu AND ev.num_jury = :num_jury
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':num_etu', $numEtu);
        $stmt->bindValue(':num_jury', $numJury);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get jury history with filters
     */
    public function getJuryHistory($anneeAcad = null, $session = null, $limit = 50, $offset = 0)
    {
        $anneeExpr = "COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), CASE WHEN e.promotion_etu IS NOT NULL AND e.promotion_etu != '' THEN CONCAT(e.promotion_etu, '-', CAST(e.promotion_etu AS UNSIGNED) + 1) END)";
        $sql = "
            SELECT 
                p.date_soutenance,
                e.num_carte_etud as etudiant_matricule,
                CONCAT(e.nom_etu, ' ', e.prenom_etu) as etudiant_nom,
                p.theme_soutenance,
                MAX(CASE WHEN cj.id_qualite_jury = 1 THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as president,
                MAX(CASE WHEN cj.id_qualite_jury = 2 THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as encadreur,
                MAX(CASE WHEN cj.id_qualite_jury = 3 THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as examinateur,
                MAX(CASE WHEN cj.id_qualite_jury = 4 THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as directeur,
                aa.date_deb,
                aa.date_fin,
                " . $anneeExpr . " as annee_academique
            FROM programmer p
            INNER JOIN etudiants e ON p.num_etud = e.num_carte_etud
            LEFT JOIN composer_jury cj ON p.num_jury = cj.num_jury
            LEFT JOIN enseignants ens ON cj.id_enseignant = ens.id_enseignant
            LEFT JOIN roles_jury rj ON cj.id_qualite_jury = rj.id_role_jury
            LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE 1=1
        ";

        $params = [];

        if ($anneeAcad) {
            $sql .= " AND " . $anneeExpr . " = :annee_acad";
            $params['annee_acad'] = $anneeAcad;
        }

        $sql .= " GROUP BY p.id_programmation, e.num_carte_etud, aa.date_deb, aa.date_fin, e.promotion_etu";
        $sql .= " ORDER BY p.date_soutenance DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting jury history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Global counters for statistics tab
     */
    public function getGlobalStats()
    {
        $stats = [
            'total_students' => 0,
            'total_soutenances' => 0,
            'total_entreprises' => 0,
            'total_encadreurs' => 0,
        ];

        try {
            $stats['total_students'] = (int) $this->db->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
            $stats['total_soutenances'] = (int) $this->db->query("SELECT COUNT(*) FROM programmer WHERE date_soutenance IS NOT NULL AND date_soutenance <> '0000-00-00'")->fetchColumn();
            $stats['total_entreprises'] = (int) $this->db->query("SELECT COUNT(DISTINCT id_entreprise) FROM entreprises")->fetchColumn();
            $stats['total_encadreurs'] = (int) $this->db->query("SELECT COUNT(DISTINCT id_enseignant) FROM affecter WHERE role = 'encadrant'")->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting global stats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Evolution par annee academique
     */
    public function getYearlyEvolution()
    {
        $sql = "
            SELECT 
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) as annee,
                COUNT(DISTINCT i.id_etudiant) as inscrits,
                SUM(CASE WHEN re.statut_rapport = 'valider' THEN 1 ELSE 0 END) as admis,
                ROUND(AVG(ev.note), 2) as moyenne_note
            FROM inscriptions i
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            LEFT JOIN etudiants e ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN rapport_etudiants re ON re.num_etu = e.num_carte_etud
            LEFT JOIN evaluer ev ON ev.num_etudiant = e.num_carte_etud
            GROUP BY aa.date_deb, aa.date_fin
            HAVING annee IS NOT NULL
            ORDER BY aa.date_deb DESC
        ";

        try {
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['admis'] = (int) $row['admis'];
                $row['inscrits'] = (int) $row['inscrits'];
                $row['taux'] = $row['inscrits'] > 0 ? round($row['admis'] * 100 / $row['inscrits'], 1) : 0;
                $row['moyenne_note'] = $row['moyenne_note'] !== null ? (float) $row['moyenne_note'] : null;
            }
            return $rows;
        } catch (PDOException $e) {
            error_log("Error getting yearly evolution: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Distribution des mentions sur la base des moyennes d'evaluation
     */
    public function getMentionsDistribution()
    {
        $mentions = [
            'Tres bien' => 0,
            'Bien' => 0,
            'Assez bien' => 0,
            'Passable' => 0,
        ];

        $sql = "
            SELECT num_etudiant, AVG(note) as moyenne
            FROM evaluer
            GROUP BY num_etudiant
        ";

        try {
            $stmt = $this->db->query($sql);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($students as $row) {
                $avg = (float) $row['moyenne'];
                if ($avg >= 16) {
                    $mentions['Tres bien']++;
                } elseif ($avg >= 14) {
                    $mentions['Bien']++;
                } elseif ($avg >= 12) {
                    $mentions['Assez bien']++;
                } else {
                    $mentions['Passable']++;
                }
            }
        } catch (PDOException $e) {
            error_log("Error getting mentions distribution: " . $e->getMessage());
        }

        return $mentions;
    }

    /**
     * Top entreprises par nombre de stages declares
     */
    public function getTopEntreprises($limit = 10)
    {
        $sql = "
            SELECT 
                ent.lib_long_entreprise,
                ent.lib_court_en,
                COUNT(*) as total
            FROM informations_stage ist
            INNER JOIN entreprises ent ON ent.id_entreprise = ist.id_entreprise
            GROUP BY ent.id_entreprise, ent.lib_long_entreprise, ent.lib_court_en
            ORDER BY total DESC
            LIMIT :limit
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting top entreprises: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available academic years
     */
    public function getAcademicYears()
    {
        $sql = "
            SELECT DISTINCT 
                CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) as annee_academique
            FROM annee_academique
            ORDER BY date_deb DESC
        ";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting academic years: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update student information
     */
    public function updateStudentInfo($numEtu, $data)
    {
        try {
            $this->db->beginTransaction();

            // Update student basic info
            if (isset($data['nom_etu']) || isset($data['prenom_etu']) || isset($data['email_etu'])) {
                $updateFields = [];
                $params = ['num_etu' => $numEtu];

                if (isset($data['nom_etu'])) {
                    $updateFields[] = "nom_etu = :nom_etu";
                    $params['nom_etu'] = $data['nom_etu'];
                }
                if (isset($data['prenom_etu'])) {
                    $updateFields[] = "prenom_etu = :prenom_etu";
                    $params['prenom_etu'] = $data['prenom_etu'];
                }
                if (isset($data['email_etu'])) {
                    $updateFields[] = "email_etu = :email_etu";
                    $params['email_etu'] = $data['email_etu'];
                }

                if (!empty($updateFields)) {
                    $sql = "UPDATE etudiants SET " . implode(', ', $updateFields) . " WHERE num_carte_etud = :num_etu";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                }
            }

            // Update stage info
            if (isset($data['stage'])) {
                $this->updateStageInfo($numEtu, $data['stage']);
            }

            // Update rapport info
            if (isset($data['rapport'])) {
                $this->updateRapportInfo($numEtu, $data['rapport']);
            }

            // Update soutenance info
            if (isset($data['soutenance'])) {
                $this->updateSoutenanceInfo($numEtu, $data['soutenance']);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error updating student info: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update stage information
     */
    private function updateStageInfo($numEtu, $stageData)
    {
        // Implementation for updating stage info
        // Add fields as needed
    }

    /**
     * Update rapport information
     */
    private function updateRapportInfo($numEtu, $rapportData)
    {
        // Get the rapport ID
        $stmt = $this->db->prepare("SELECT id_rapport FROM rapport_etudiants WHERE num_etu = :num_etu ORDER BY date_rapport DESC LIMIT 1");
        $stmt->execute(['num_etu' => $numEtu]);
        $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rapport) {
            $updateFields = [];
            $params = ['id_rapport' => $rapport['id_rapport']];

            if (isset($rapportData['theme_rapport'])) {
                $updateFields[] = "theme_rapport = :theme_rapport";
                $params['theme_rapport'] = $rapportData['theme_rapport'];
            }
            if (isset($rapportData['statut_rapport'])) {
                $updateFields[] = "statut_rapport = :statut_rapport";
                $params['statut_rapport'] = $rapportData['statut_rapport'];
            }

            if (!empty($updateFields)) {
                $sql = "UPDATE rapport_etudiants SET " . implode(', ', $updateFields) . " WHERE id_rapport = :id_rapport";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    /**
     * Update soutenance information
     */
    private function updateSoutenanceInfo($numEtu, $soutenanceData)
    {
        // Implementation for updating soutenance info
        // Add fields as needed
    }
}
