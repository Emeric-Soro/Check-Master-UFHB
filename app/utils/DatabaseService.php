<?php

/**
 * Database Service
 * Centralized service for common database operations related to reports, minutes, and documents
 * Provides transaction support and error handling
 */

require_once __DIR__ . '/../config/database.php';

class DatabaseService
{
    private $pdo;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Begin a database transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit the current transaction
     * 
     * @return bool Success status
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback the current transaction
     * 
     * @return bool Success status
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Save a meeting minutes (compte rendu) with associated reports
     * 
     * @param string $num_etu Student number
     * @param string $nom_CR Minutes name
     * @param string $contenu_CR Minutes content
     * @param string $chemin_pdf PDF file path
     * @param string $date_CR Creation date
     * @param array $rapports Array of report IDs
     * @param array $encadrants Array of supervisor assignments [report_id => teacher_id]
     * @param array $directeurs Array of director assignments [report_id => teacher_id]
     * @return int|false ID of created minutes or false on error
     */
    public function saveMinutes(string $num_etu, string $nom_CR, string $contenu_CR, string $chemin_pdf, string $date_CR, array $rapports = [], array $encadrants = [], array $directeurs = [])
    {
        // Input validation
        if (empty($num_etu) || empty($nom_CR) || empty($contenu_CR)) {
            error_log("Invalid input for saveMinutes: num_etu, nom_CR, and contenu_CR are required");
            return false;
        }
        
        try {
            $this->beginTransaction();
            
            // Insert minutes
            $stmt = $this->pdo->prepare("
                INSERT INTO compte_rendu (num_etu, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR]);
            $id_CR = $this->pdo->lastInsertId();
            
            // Link reports to minutes
            if (!empty($rapports)) {
                $stmt = $this->pdo->prepare("INSERT INTO compte_rendu_rapport (id_CR, id_rapport) VALUES (?, ?)");
                foreach ($rapports as $id_rapport) {
                    if (is_numeric($id_rapport) && $id_rapport > 0) {
                        $stmt->execute([$id_CR, $id_rapport]);
                    }
                }
            }
            
            // Assign supervisors and directors
            foreach ($rapports as $id_rapport) {
                if (!is_numeric($id_rapport) || $id_rapport <= 0) {
                    continue;
                }
                
                // Supervisor
                if (!empty($encadrants[$id_rapport])) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) 
                        VALUES (?, ?, NULL, 'encadrant')
                        ON DUPLICATE KEY UPDATE id_enseignant = VALUES(id_enseignant)
                    ");
                    $stmt->execute([$encadrants[$id_rapport], $id_rapport]);
                }
                
                // Director
                if (!empty($directeurs[$id_rapport])) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) 
                        VALUES (?, ?, NULL, 'directeur')
                        ON DUPLICATE KEY UPDATE id_enseignant = VALUES(id_enseignant)
                    ");
                    $stmt->execute([$directeurs[$id_rapport], $id_rapport]);
                }
            }
            
            $this->commit();
            return $id_CR;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error saving minutes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get minutes with associated reports and students
     * 
     * @param int $id_CR Minutes ID
     * @return array|false Minutes data or false if not found
     */
    public function getMinutesWithReports(int $id_CR)
    {
        try {
            // Get minutes with student info
            $stmt = $this->pdo->prepare("
                SELECT cr.*, e.nom_etu, e.prenom_etu, e.email_etu 
                FROM compte_rendu cr 
                JOIN etudiants e ON cr.num_etu = e.num_etu 
                WHERE cr.id_CR = ?
            ");
            $stmt->execute([$id_CR]);
            $minutes = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$minutes) {
                return false;
            }
            
            // Get associated reports
            $stmt = $this->pdo->prepare("
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu,
                       enc.nom AS enc_nom, enc.prenom AS enc_prenom,
                       dir.nom AS dir_nom, dir.prenom AS dir_prenom
                FROM compte_rendu_rapport crr 
                JOIN rapport_etudiants r ON crr.id_rapport = r.id_rapport 
                JOIN etudiants e ON r.num_etu = e.num_etu 
                LEFT JOIN affecter aenc ON r.id_rapport = aenc.id_rapport AND aenc.role = 'encadrant'
                LEFT JOIN enseignants enc ON aenc.id_enseignant = enc.id_enseignant
                LEFT JOIN affecter adir ON r.id_rapport = adir.id_rapport AND adir.role = 'directeur'
                LEFT JOIN enseignants dir ON adir.id_enseignant = dir.id_enseignant
                WHERE crr.id_CR = ?
            ");
            $stmt->execute([$id_CR]);
            $minutes['rapports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $minutes;
            
        } catch (Exception $e) {
            error_log("Error fetching minutes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all minutes with optional filters
     * 
     * @param array $filters Optional filters (search, year, limit, offset)
     * @return array Array of minutes
     */
    public function getAllMinutes(array $filters = []): array
    {
        try {
            $sql = "
                SELECT cr.*, e.nom_etu, e.prenom_etu, e.email_etu,
                       COUNT(DISTINCT crr.id_rapport) as nb_rapports
                FROM compte_rendu cr 
                JOIN etudiants e ON cr.num_etu = e.num_etu 
                LEFT JOIN compte_rendu_rapport crr ON cr.id_CR = crr.id_CR
                WHERE 1=1
            ";
            $params = [];
            
            // Search filter
            if (!empty($filters['search'])) {
                $sql .= " AND (cr.nom_CR LIKE ? OR cr.contenu_CR LIKE ? OR e.nom_etu LIKE ? OR e.prenom_etu LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Year filter
            if (!empty($filters['year'])) {
                $sql .= " AND YEAR(cr.date_CR) = ?";
                $params[] = $filters['year'];
            }
            
            $sql .= " GROUP BY cr.id_CR ORDER BY cr.date_CR DESC";
            
            // Pagination
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . intval($filters['limit']);
                if (!empty($filters['offset'])) {
                    $sql .= " OFFSET " . intval($filters['offset']);
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error fetching all minutes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get report with student and evaluation data
     * 
     * @param int $id_rapport Report ID
     * @return array|false Report data or false if not found
     */
    public function getReportWithDetails(int $id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu, e.niveau_acces,
                       enc.nom AS encadrant_nom, enc.prenom AS encadrant_prenom,
                       dir.nom AS directeur_nom, dir.prenom AS directeur_prenom,
                       v.statut as validation_statut, v.commentaire as validation_commentaire,
                       v.date_validation
                FROM rapport_etudiants r 
                JOIN etudiants e ON r.num_etu = e.num_etu 
                LEFT JOIN affecter aenc ON r.id_rapport = aenc.id_rapport AND aenc.role = 'encadrant'
                LEFT JOIN enseignants enc ON aenc.id_enseignant = enc.id_enseignant
                LEFT JOIN affecter adir ON r.id_rapport = adir.id_rapport AND adir.role = 'directeur'
                LEFT JOIN enseignants dir ON adir.id_enseignant = dir.id_enseignant
                LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE r.id_rapport = ?
            ");
            $stmt->execute([$id_rapport]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$report) {
                return false;
            }
            
            // Get evaluations for this report
            $stmt = $this->pdo->prepare("
                SELECT ev.*, ens.nom, ens.prenom, ens.fonction
                FROM evaluation_rapports ev
                JOIN enseignants ens ON ev.id_evaluateur = ens.id_enseignant
                WHERE ev.id_rapport = ?
                ORDER BY ev.date_evaluation DESC
            ");
            $stmt->execute([$id_rapport]);
            $report['evaluations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $report;
            
        } catch (Exception $e) {
            error_log("Error fetching report details: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update report status
     * 
     * @param int $id_rapport Report ID
     * @param string $status New status
     * @param string|null $commentaire Optional comment
     * @return bool Success status
     */
    public function updateReportStatus(int $id_rapport, string $status, ?string $commentaire = null): bool
    {
        try {
            $this->beginTransaction();
            
            // Update report status
            $stmt = $this->pdo->prepare("
                UPDATE rapport_etudiants 
                SET statut_rapport = ?, date_mise_a_jour = CURRENT_TIMESTAMP
                WHERE id_rapport = ?
            ");
            $stmt->execute([$status, $id_rapport]);
            
            // Insert validation record
            if ($commentaire !== null) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO valider (id_rapport, statut, commentaire, date_validation, id_validateur)
                    VALUES (?, ?, ?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE 
                        statut = VALUES(statut),
                        commentaire = VALUES(commentaire),
                        date_validation = VALUES(date_validation),
                        id_validateur = VALUES(id_validateur)
                ");
                $userId = $_SESSION['id_utilisateur'] ?? null;
                $stmt->execute([$id_rapport, $status, $commentaire, $userId]);
            }
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error updating report status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save evaluation for a report
     * 
     * @param int $id_rapport Report ID
     * @param int $id_evaluateur Evaluator ID
     * @param float $note Grade/score
     * @param string $commentaire Comment
     * @param array $criteria Optional evaluation criteria scores
     * @return bool Success status
     */
    public function saveEvaluation(int $id_rapport, int $id_evaluateur, float $note, string $commentaire, array $criteria = []): bool
    {
        // Input validation
        if ($id_rapport <= 0 || $id_evaluateur <= 0) {
            error_log("Invalid input for saveEvaluation: id_rapport and id_evaluateur must be positive integers");
            return false;
        }
        
        if ($note < 0 || $note > 20) {
            error_log("Invalid note value: must be between 0 and 20");
            return false;
        }
        
        try {
            $this->beginTransaction();
            
            // Insert or update evaluation
            $stmt = $this->pdo->prepare("
                INSERT INTO evaluation_rapports (id_rapport, id_evaluateur, note, commentaire, date_evaluation)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    note = VALUES(note),
                    commentaire = VALUES(commentaire),
                    date_evaluation = VALUES(date_evaluation)
            ");
            $stmt->execute([$id_rapport, $id_evaluateur, $note, $commentaire]);
            $evaluation_id = $this->pdo->lastInsertId() ?: $this->pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
            
            // Save criteria scores if provided
            if (!empty($criteria) && $evaluation_id) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO evaluation_criteres (id_evaluation, id_critere, score)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE score = VALUES(score)
                ");
                
                foreach ($criteria as $id_critere => $score) {
                    if (is_numeric($id_critere) && is_numeric($score)) {
                        $stmt->execute([$evaluation_id, $id_critere, $score]);
                    }
                }
            }
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error saving evaluation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get statistics for dashboard
     * 
     * @return array Statistics data
     */
    public function getDashboardStatistics(): array
    {
        try {
            $stats = [];
            
            // Total reports by status
            $stmt = $this->pdo->query("
                SELECT statut_rapport, COUNT(*) as count 
                FROM rapport_etudiants 
                GROUP BY statut_rapport
            ");
            $stats['rapports_par_statut'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total minutes
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM compte_rendu");
            $stats['total_comptes_rendus'] = $stmt->fetchColumn();
            
            // Minutes this month
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM compte_rendu 
                WHERE MONTH(date_CR) = MONTH(CURRENT_DATE()) 
                AND YEAR(date_CR) = YEAR(CURRENT_DATE())
            ");
            $stats['comptes_rendus_ce_mois'] = $stmt->fetchColumn();
            
            // Pending reports
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM rapport_etudiants 
                WHERE statut_rapport IN ('en_attente', 'en_cours')
            ");
            $stats['rapports_en_attente'] = $stmt->fetchColumn();
            
            // Recent evaluations
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM evaluation_rapports 
                WHERE date_evaluation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stats['evaluations_cette_semaine'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error fetching dashboard statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete minutes and associated data
     * 
     * @param int $id_CR Minutes ID
     * @return bool Success status
     */
    public function deleteMinutes(int $id_CR): bool
    {
        try {
            $this->beginTransaction();
            
            // Delete report associations
            $stmt = $this->pdo->prepare("DELETE FROM compte_rendu_rapport WHERE id_CR = ?");
            $stmt->execute([$id_CR]);
            
            // Delete minutes
            $stmt = $this->pdo->prepare("DELETE FROM compte_rendu WHERE id_CR = ?");
            $stmt->execute([$id_CR]);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error deleting minutes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get PDO instance for custom queries
     * 
     * @return PDO PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
