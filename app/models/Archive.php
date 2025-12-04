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
        $sql = "
            SELECT DISTINCT
                e.num_etu as matricule,
                e.nom_etu as nom,
                e.prenom_etu as prenoms,
                r.theme_rapport as theme,
                ent.lib_entreprise as entreprise,
                r.statut_rapport as statut,
                aa.date_deb,
                aa.date_fin,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) as annee_academique,
                r.id_rapport
            FROM etudiants e
            LEFT JOIN rapport_etudiants r ON e.num_etu = r.num_etu
            LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
            LEFT JOIN entreprises ent ON ist.id_entreprise = ent.id_entreprise
            LEFT JOIN inscriptions i ON e.num_etu = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($anneeAcad) {
            $sql .= " AND CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) = :annee_acad";
            $params['annee_acad'] = $anneeAcad;
        }
        
        if ($statut) {
            $sql .= " AND r.statut_rapport = :statut";
            $params['statut'] = $statut;
        }
        
        if ($search) {
            $sql .= " AND (e.nom_etu LIKE :search OR e.prenom_etu LIKE :search OR e.num_etu LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY aa.date_deb DESC, e.nom_etu ASC LIMIT :limit OFFSET :offset";
        
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
        $sql = "
            SELECT COUNT(DISTINCT e.num_etu) as total
            FROM etudiants e
            LEFT JOIN rapport_etudiants r ON e.num_etu = r.num_etu
            LEFT JOIN inscriptions i ON e.num_etu = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($anneeAcad) {
            $sql .= " AND CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) = :annee_acad";
            $params['annee_acad'] = $anneeAcad;
        }
        
        if ($statut) {
            $sql .= " AND r.statut_rapport = :statut";
            $params['statut'] = $statut;
        }
        
        if ($search) {
            $sql .= " AND (e.nom_etu LIKE :search OR e.prenom_etu LIKE :search OR e.num_etu LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
            
            return $student;
        } catch (PDOException $e) {
            error_log("Error getting complete student file: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get student basic info
     */
    private function getStudentInfo($numEtu)
    {
        $sql = "SELECT * FROM etudiants WHERE num_etu = :num_etu";
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
            SELECT ist.*, ent.lib_entreprise
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
        $stmt->execute(['num_etu' => $numEtu]);
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
        $stmt->execute(['num_etu' => $numEtu, 'num_jury' => $numJury]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get jury history with filters
     */
    public function getJuryHistory($anneeAcad = null, $session = null, $limit = 50, $offset = 0)
    {
        $sql = "
            SELECT 
                p.date_soutenance,
                e.num_etu as matricule,
                CONCAT(e.nom_etu, ' ', e.prenom_etu) as etudiant,
                p.theme_soutenance,
                MAX(CASE WHEN rj.lib_role = 'Président du jury' THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as president,
                MAX(CASE WHEN rj.lib_role = 'Examinateur' THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as examinateur,
                MAX(CASE WHEN rj.lib_role = 'Encadreur' THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as encadreur,
                MAX(CASE WHEN rj.lib_role = 'Directeur de mémoire' THEN CONCAT(ens.nom_enseignant, ' ', ens.prenom_enseignant) END) as directeur,
                aa.date_deb,
                aa.date_fin,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) as annee_academique
            FROM programmer p
            INNER JOIN etudiants e ON p.num_etud = e.num_etu
            LEFT JOIN composer_jury cj ON p.num_jury = cj.num_jury
            LEFT JOIN enseignants ens ON cj.id_enseignant = ens.id_enseignant
            LEFT JOIN roles_jury rj ON cj.id_qualite_jury = rj.id_role_jury
            LEFT JOIN inscriptions i ON e.num_etu = i.id_etudiant
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($anneeAcad) {
            $sql .= " AND CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) = :annee_acad";
            $params['annee_acad'] = $anneeAcad;
        }
        
        $sql .= " GROUP BY p.id_programmation, e.num_etu, aa.date_deb, aa.date_fin";
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
                    $sql = "UPDATE etudiants SET " . implode(', ', $updateFields) . " WHERE num_etu = :num_etu";
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
