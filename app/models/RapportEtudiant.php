<?php

class RapportEtudiant
{
    public $pdo;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Throwable $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists($tableName, $columnName)
    {
        $cacheKey = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }
        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$cacheKey] = $exists;
            return $exists;
        } catch (Throwable $e) {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }
    }

    private function getReportDateColumn()
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return 'date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return 'date_redaction_rapport';
        }
        return null;
    }

    private function getReportTitleColumn()
    {
        return $this->columnExists('rapport_etudiants', 'nom_rapport') ? 'nom_rapport' : 'theme_rapport';
    }

    private function getReportSelectExtras($alias = 'r')
    {
        $titleCol = $this->getReportTitleColumn();
        $dateCol = $this->getReportDateColumn();
        $parts = [
            $alias . '.' . $titleCol . ' AS nom_rapport',
            $dateCol ? ($alias . '.' . $dateCol . ' AS date_rapport') : 'NULL AS date_rapport',
        ];
        return implode(', ', $parts);
    }

    private function getReportOrderBy($alias = 'r')
    {
        $dateCol = $this->getReportDateColumn();
        if ($dateCol !== null) {
            return 'ORDER BY ' . $alias . '.' . $dateCol . ' DESC';
        }
        return 'ORDER BY ' . $alias . '.id_rapport DESC';
    }

    public function getAllRapports()
    {
        try {
            $sql = "
                SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                    (SELECT i.id_annee_acad FROM inscriptions i 
                     WHERE i.num_carte_etud = e.num_carte_etud 
                     ORDER BY i.id_annee_acad DESC, i.date_inscription DESC LIMIT 1) AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                " . $this->getReportOrderBy('r') . "
            ";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur getAllRapports: " . $e->getMessage());
            return [];
        }
    }

    public function getRapportById($id_rapport)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.*, 
                " . $this->getReportSelectExtras('r') . ",
                e.nom_etu, 
                e.prenom_etu, 
                e.email_etu,
                e.promotion_etu,
                (SELECT i.id_annee_acad FROM inscriptions i 
                 WHERE i.num_carte_etud = e.num_carte_etud 
                 ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad,
                d.date_depot
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
            WHERE r.id_rapport = ?
        ");
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRapportDetail($id_rapport)
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                (SELECT i.id_annee_acad FROM inscriptions i 
                 WHERE i.num_carte_etud = e.num_carte_etud 
                 ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad, d.date_depot
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
            WHERE r.id_rapport = ?
        ");
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getRapportByIdAndEtudiant($id_rapport, $num_etu)
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                (SELECT i.id_annee_acad FROM inscriptions i 
                 WHERE i.num_carte_etud = e.num_carte_etud 
                 ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            WHERE r.id_rapport = ? AND r.num_etu = ?
        ");
        $stmt->execute([$id_rapport, $num_etu]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getRapportsByEtudiant($num_etu)
    {
        try {
            $sql = "
                SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                    (SELECT i.id_annee_acad FROM inscriptions i 
                     WHERE i.num_carte_etud = e.num_carte_etud 
                     ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                WHERE r.num_etu = ?
                " . $this->getReportOrderBy('r') . "
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$num_etu]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur getRapportsByEtudiant: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterRapport($num_etu, $nom_rapport, $theme_rapport)
    {
        try {
            if (!$this->isEtudiantExist($num_etu)) {
                error_log("Tentative d'ajout de rapport pour étudiant inexistant: " . $num_etu);
                return false;
            }

            $dateCol = $this->getReportDateColumn();
            $hasNomRapport = $this->columnExists('rapport_etudiants', 'nom_rapport');
            $theme = trim((string) $theme_rapport) !== '' ? $theme_rapport : $nom_rapport;

            $fields = ['num_etu'];
            $values = [$num_etu];
            $placeholders = ['?'];

            if ($hasNomRapport) {
                $fields[] = 'nom_rapport';
                $values[] = $nom_rapport;
                $placeholders[] = '?';
            }

            $fields[] = 'theme_rapport';
            $values[] = $theme;
            $placeholders[] = '?';

            if ($dateCol !== null) {
                $fields[] = $dateCol;
                $placeholders[] = 'NOW()';
            }
            if ($this->columnExists('rapport_etudiants', 'statut_rapport')) {
                $fields[] = 'statut_rapport';
                $values[] = 'en_attente';
                $placeholders[] = '?';
            }
            if ($this->columnExists('rapport_etudiants', 'version')) {
                $fields[] = 'version';
                $values[] = 1;
                $placeholders[] = '?';
            }

            $sql = 'INSERT INTO rapport_etudiants (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute($values)) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur d'ajout de rapport: " . $e->getMessage());
            return false;
        }
    }

    public function updateRapport($id_rapport, $num_etu, $nom_rapport, $theme_rapport)
    {
        try {
            // Vérifier que le rapport appartient bien à l'étudiant
            if (!$this->isRapportOwnedByEtudiant($id_rapport, $num_etu)) {
                error_log("Tentative de modification de rapport non autorisée: rapport $id_rapport par étudiant $num_etu");
                return false;
            }

            $hasNomRapport = $this->columnExists('rapport_etudiants', 'nom_rapport');
            $dateCol = $this->getReportDateColumn();
            $theme = trim((string) $theme_rapport) !== '' ? $theme_rapport : $nom_rapport;

            $setParts = [];
            $values = [];

            if ($hasNomRapport) {
                $setParts[] = 'nom_rapport = ?';
                $values[] = $nom_rapport;
            }
            $setParts[] = 'theme_rapport = ?';
            $values[] = $theme;

            if ($dateCol !== null) {
                $setParts[] = $dateCol . ' = NOW()';
            }

            $sql = 'UPDATE rapport_etudiants SET ' . implode(', ', $setParts) . ' WHERE id_rapport = ? AND num_etu = ?';
            $values[] = $id_rapport;
            $values[] = $num_etu;

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour de rapport: " . $e->getMessage());
            return false;
        }
    }

    public function deleteRapport($id_rapport, $num_etu)
    {
        try {
            // Vérifier que le rapport appartient bien à l'étudiant
            if (!$this->isRapportOwnedByEtudiant($id_rapport, $num_etu)) {
                error_log("Tentative de suppression de rapport non autorisée: rapport $id_rapport par étudiant $num_etu");
                return false;
            }

            $stmt = $this->pdo->prepare("DELETE FROM rapport_etudiants WHERE id_rapport = ? AND num_etu = ?");
            return $stmt->execute([$id_rapport, $num_etu]);
        } catch (PDOException $e) {
            error_log("Erreur de suppression de rapport: " . $e->getMessage());
            return false;
        }
    }

    public function isRapportExist($id_rapport)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE id_rapport = ?");
        $stmt->execute([$id_rapport]);
        return $stmt->fetchColumn() > 0;
    }

    public function isRapportOwnedByEtudiant($id_rapport, $num_etu)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE id_rapport = ? AND num_etu = ?");
        $stmt->execute([$id_rapport, $num_etu]);
        return $stmt->fetchColumn() > 0;
    }

    public function isEtudiantExist($num_etu)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE num_carte_etud = ?");
        $stmt->execute([$num_etu]);
        return $stmt->fetchColumn() > 0;
    }

    public function isRapportNomExist($nom_rapport, $num_etu, $id_rapport = null)
    {
        try {
            $titleCol = $this->getReportTitleColumn();
            $sql = "SELECT COUNT(*) FROM rapport_etudiants WHERE $titleCol = ? AND num_etu = ?";
            $params = [$nom_rapport, $num_etu];

            // Si on vérifie pour une modification (ID existe)
            if ($id_rapport !== null) {
                $sql .= " AND id_rapport != ?";
                $params[] = $id_rapport;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur vérification nom rapport: " . $e->getMessage());
            return true; // En cas d'erreur, on considère que le nom existe pour éviter les doublons
        }
    }

    public function getStatsEtudiant($num_etu)
    {
        try {
            $dateCol = $this->getReportDateColumn();
            if ($dateCol !== null) {
                $sql = "
                    SELECT 
                        COUNT(*) as total_rapports,
                        COUNT(CASE WHEN DATE($dateCol) = CURDATE() THEN 1 END) as rapports_aujourd_hui,
                        COUNT(CASE WHEN $dateCol >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as rapports_semaine,
                        COUNT(CASE WHEN $dateCol >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as rapports_mois,
                        MAX($dateCol) as dernier_rapport
                    FROM rapport_etudiants 
                    WHERE num_etu = ?
                ";
            } else {
                $sql = "
                    SELECT 
                        COUNT(*) as total_rapports,
                        0 as rapports_aujourd_hui,
                        0 as rapports_semaine,
                        0 as rapports_mois,
                        NULL as dernier_rapport
                    FROM rapport_etudiants 
                    WHERE num_etu = ?
                ";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération stats étudiant: " . $e->getMessage());
            return null;
        }
    }

    public function searchRapports($search_term, $num_etu = null)
    {
        try {
            $hasNomRapport = $this->columnExists('rapport_etudiants', 'nom_rapport');
            $sql = "
                SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                    (SELECT i.id_annee_acad FROM inscriptions i 
                     WHERE i.num_carte_etud = e.num_carte_etud 
                     ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                WHERE " . ($hasNomRapport ? "(r.nom_rapport LIKE ? OR r.theme_rapport LIKE ?)" : "(r.theme_rapport LIKE ?)") . "
            ";

            $params = $hasNomRapport ? ["%$search_term%", "%$search_term%"] : ["%$search_term%"];

            if ($num_etu !== null) {
                $sql .= " AND r.num_etu = ?";
                $params[] = $num_etu;
            }

            $sql .= " " . $this->getReportOrderBy('r');

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur recherche rapports: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentRapports($limit = 10)
    {
        try {
            $limit = (int) $limit;
            if ($limit <= 0) {
                $limit = 10;
            }

            $sql = "
                SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                    (SELECT i.id_annee_acad FROM inscriptions i 
                     WHERE i.num_carte_etud = e.num_carte_etud 
                     ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                " . $this->getReportOrderBy('r') . "
                LIMIT " . $limit . "
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports récents: " . $e->getMessage());
            return [];
        }
    }

    public function countRapportsByEtudiant($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE num_etu = ?");
            $stmt->execute([$num_etu]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur comptage rapports étudiant: " . $e->getMessage());
            return 0;
        }
    }

    public function getEtudiantInfo($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, COUNT(r.id_rapport) as nb_rapports
                FROM etudiants e
                LEFT JOIN rapport_etudiants r ON e.num_carte_etud = r.num_etu
                WHERE e.num_carte_etud = ?
                GROUP BY e.num_carte_etud
            ");
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération info étudiant: " . $e->getMessage());
            return null;
        }
    }

    public function updateStatutRapport($id_rapport, $statut)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ?, date_modification = NOW() WHERE id_rapport = ?");
            return $stmt->execute([$statut, $id_rapport]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut rapport: " . $e->getMessage());
            return false;
        }
    }

    public function setRapportEnCours($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = 'en_cours', date_modification = NOW() WHERE id_rapport = ?");
            return $stmt->execute([$id_rapport]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut rapport en cours: " . $e->getMessage());
            return false;
        }
    }

    public function updateCheminFichier($id_rapport, $chemin_fichier, $taille_fichier = null)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET chemin_fichier = ?, taille_fichier = ?, date_modification = NOW() WHERE id_rapport = ?");
            return $stmt->execute([$chemin_fichier, $taille_fichier, $id_rapport]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour chemin fichier: " . $e->getMessage());
            return false;
        }
    }

    public function getRapportsByStatut($statut)
    {
        try {
            $sql = "
                SELECT r.*, " . $this->getReportSelectExtras('r') . ", e.nom_etu, e.prenom_etu, e.email_etu, 
                    (SELECT i.id_annee_acad FROM inscriptions i 
                     WHERE i.num_carte_etud = e.num_carte_etud 
                     ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                WHERE r.statut_rapport = ?
                " . $this->getReportOrderBy('r') . "
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$statut]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports par statut: " . $e->getMessage());
            return [];
        }
    }

    public function ajouterEvaluation($id_rapport, $id_evaluateur, $type_evaluateur, $commentaire, $note = null)
    {
        try {
            $stmt = $this->pdo->prepare("
            INSERT INTO evaluations_rapports (id_rapport, id_evaluateur, type_evaluateur, commentaire, note) 
            VALUES (?, ?, ?, ?, ?)
        ");
            return $stmt->execute([$id_rapport, $id_evaluateur, $type_evaluateur, $commentaire, $note]);
        } catch (PDOException $e) {
            error_log("Erreur ajout évaluation: " . $e->getMessage());
            return false;
        }
    }

    public function getEvaluationsRapport($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
            SELECT e.*, 
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.nom_enseignant
                        ELSE pa.nom_pers_admin 
                    END as nom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.prenom_enseignant
                        ELSE pa.prenom_pers_admin 
                    END as prenom_evaluateur
            FROM evaluations_rapports e
            LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant AND e.type_evaluateur = 'enseignant'
            LEFT JOIN personnel_admin pa ON e.id_evaluateur = pa.id_pers_admin AND e.type_evaluateur = 'personnel_admin'
            WHERE e.id_rapport = ?
            ORDER BY e.date_evaluation DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération évaluations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si le rapport est validé par la communication (chargée de communication)
     * @param string $numEtu
     * @return bool
     */
    public function estRapportValideCommunication(string $numEtu): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM rapport_etudiants r
                JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE r.num_etu = ? AND a.decision = 'approuve'
                ORDER BY a.date_approv DESC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Erreur estRapportValideCommunication: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer la dernière décision de commission pour un étudiant
     * @param string $numEtu
     * @return string 'favorable', 'defavorable' ou vide
     */
    public function getDerniereDecisionCommission(string $numEtu): string
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.decision_validation
                FROM rapport_etudiants r
                JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE r.num_etu = ?
                ORDER BY v.date_validation DESC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['decision_validation'])) {
                $decision = strtolower($result['decision_validation']);
                return ($decision === 'valider') ? 'favorable' : 'defavorable';
            }
            
            return '';
        } catch (PDOException $e) {
            error_log("Erreur getDerniereDecisionCommission: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Vérifier si l'évaluation de soutenance est complète pour un étudiant
     * @param string $numEtu
     * @return bool
     */
    public function estEvaluationSoutenanceComplete(string $numEtu): bool
    {
        try {
            // Get the latest soutenance for this student
            $soutenance = $this->getDerniereSoutenance($numEtu);
            if (!$soutenance) {
                return false;
            }
            
            $idRapport = $soutenance['id_rapport'] ?? null;
            if (!$idRapport) {
                return false;
            }
            
            // Check if all evaluations are present (we expect 4 evaluators)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM evaluations_rapports
                WHERE id_rapport = ?
            ");
            $stmt->execute([$idRapport]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalEvaluations = $result['total'] ?? 0;
            
            // Consider complete if we have at least 4 evaluations (jury complet)
            return $totalEvaluations >= 4;
        } catch (PDOException $e) {
            error_log("Erreur estEvaluationSoutenanceComplete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer la dernière soutenance pour un étudiant
     * @param string $numEtu
     * @return array|null
     */
    public function getDerniereSoutenance(string $numEtu): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, 
                       " . $this->getReportSelectExtras('r') . ", 
                       e.nom_etu, e.prenom_etu, e.email_etu,
                       (SELECT i.id_annee_acad FROM inscriptions i 
                        WHERE i.num_carte_etud = e.num_carte_etud 
                        ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                WHERE r.num_etu = ?
                ORDER BY r.date_redaction_rapport DESC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getDerniereSoutenance: " . $e->getMessage());
            return null;
        }
    }

    public function getRapportsDeposes()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu, e.promotion_etu, 
                    (SELECT i.id_annee_acad FROM inscriptions i 
                     WHERE i.num_carte_etud = e.num_carte_etud 
                     ORDER BY i.date_inscription DESC LIMIT 1) AS id_annee_acad, d.date_depot
                FROM deposer d
                JOIN rapport_etudiants r ON d.id_rapport = r.id_rapport
                JOIN etudiants e ON d.num_etu = e.num_carte_etud
                ORDER BY d.date_depot DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports déposés: " . $e->getMessage());
            return [];
        }
    }

    public function getDecisionsEvaluation($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.*,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.nom_enseignant
                        ELSE pa.nom_pers_admin 
                    END as nom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.prenom_enseignant
                        ELSE pa.prenom_pers_admin 
                    END as prenom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN 'Enseignant'
                        ELSE 'Personnel administratif'
                    END as fonction_evaluateur
                FROM evaluations_rapports e
                LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant AND e.type_evaluateur = 'enseignant'
                LEFT JOIN personnel_admin pa ON e.id_evaluateur = pa.id_pers_admin AND e.type_evaluateur = 'personnel_admin'
                WHERE e.id_rapport = ? AND e.statut_evaluation = 'terminee'
                ORDER BY e.date_evaluation DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération décisions évaluation: " . $e->getMessage());
            return [];
        }
    }
}
