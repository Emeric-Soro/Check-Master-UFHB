<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CommissionValidationMembre.php';

class EvaluationRapport
{

    private $pdo;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
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
        $key = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }
        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (Throwable $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function rapportDateExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return $alias . '.date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return $alias . '.date_redaction_rapport';
        }
        return 'NULL';
    }

    private function rapportTitleExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'nom_rapport')) {
            return $alias . '.nom_rapport';
        }
        return $alias . '.theme_rapport';
    }

    private function studentJoinCondition(string $rapportAlias = 'r', string $etudiantAlias = 'e'): string
    {
        return sprintf(
            '(%1$s.num_etu = %2$s.num_carte_etud OR %1$s.num_etu = %2$s.num_ident_etud)',
            $rapportAlias,
            $etudiantAlias
        );
    }

    private function studentCarteExpr(string $etudiantAlias = 'e'): string
    {
        return sprintf(
            "COALESCE(NULLIF(%s.num_carte_etud, ''), NULLIF(%s.num_ident_etud, ''))",
            $etudiantAlias,
            $etudiantAlias
        );
    }

    private function getFallbackStudentYearExpr(string $etudiantAlias = 'e'): string
    {
        return "
            (SELECT i.id_annee_acad
             FROM inscriptions i
             WHERE i.num_carte_etud = " . $this->studentCarteExpr($etudiantAlias) . "
             ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC
             LIMIT 1)
        ";
    }

    private function getAcademicYearFromDateExpr(string $dateExpr): string
    {
        return "
            (SELECT aa.id_annee_acad
             FROM annee_academique aa
             WHERE DATE($dateExpr) BETWEEN aa.date_deb AND aa.date_fin
             ORDER BY aa.date_deb DESC
             LIMIT 1)
        ";
    }

    private function getReportAcademicYearExpr(string $rapportAlias = 'r', string $etudiantAlias = 'e', ?string $depotAlias = null): string
    {
        $candidates = [];

        if ($depotAlias !== null) {
            $candidates[] = $this->getAcademicYearFromDateExpr($depotAlias . '.date_depot');
        }

        $dateExpr = $this->rapportDateExpr($rapportAlias);
        if ($dateExpr !== 'NULL') {
            $candidates[] = $this->getAcademicYearFromDateExpr($dateExpr);
        }

        $candidates[] = $this->getFallbackStudentYearExpr($etudiantAlias);

        return 'COALESCE(' . implode(', ', $candidates) . ')';
    }

    private function normalizeSqlExpr(string $expr): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE($expr, '')), ' ', ''), '-', ''), '''', ''), '.', ''))";
    }

    private function enseignantResolutionSubquery(string $userAlias = 'u'): string
    {
        $userLoginExpr = "LOWER(COALESCE({$userAlias}.login_utilisateur, ''))";
        $userNameExpr = $this->normalizeSqlExpr($userAlias . '.nom_utilisateur');
        $teacherForwardExpr = $this->normalizeSqlExpr("CONCAT(COALESCE(e2.nom_enseignant, ''), COALESCE(e2.prenom_enseignant, ''))");
        $teacherReverseExpr = $this->normalizeSqlExpr("CONCAT(COALESCE(e2.prenom_enseignant, ''), COALESCE(e2.nom_enseignant, ''))");
        $emailMatch = "({$userLoginExpr} <> '' AND LOWER(COALESCE(e2.mail_enseignant, '')) = {$userLoginExpr})";
        $nameMatch = "({$userNameExpr} <> '' AND ({$userNameExpr} = {$teacherForwardExpr} OR {$userNameExpr} = {$teacherReverseExpr}))";

        return "
            (
                SELECT e2.id_enseignant
                FROM enseignants e2
                WHERE {$emailMatch} OR {$nameMatch}
                ORDER BY CASE WHEN {$emailMatch} THEN 0 ELSE 1 END, e2.id_enseignant
                LIMIT 1
            )
        ";
    }

    private function latestCandidatureJoin(string $rapportAlias = 'r', string $etudiantAlias = 'e', string $candidatureAlias = 'cs'): string
    {
        if (!$this->tableExists('candidature_soutenance')) {
            return '';
        }

        return "
            LEFT JOIN candidature_soutenance {$candidatureAlias} ON {$candidatureAlias}.id_candidature = (
                SELECT cs2.id_candidature
                FROM candidature_soutenance cs2
                WHERE (
                    cs2.id_candidature = {$rapportAlias}.id_candidature
                    OR (
                        ({$rapportAlias}.id_candidature IS NULL OR {$rapportAlias}.id_candidature = 0)
                        AND (cs2.num_etu = {$etudiantAlias}.num_carte_etud OR cs2.num_etu = {$etudiantAlias}.num_ident_etud)
                    )
                )
                ORDER BY cs2.date_candidature DESC, cs2.id_candidature DESC
                LIMIT 1
            )
        ";
    }

    private function resolveEnseignantIdFromVoteActor($idEvaluateur): ?string
    {
        $idEvaluateur = trim((string) $idEvaluateur);
        if ($idEvaluateur === '') {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id_enseignant
                FROM enseignants
                WHERE id_enseignant = ?
                LIMIT 1
            ");
            $stmt->execute([$idEvaluateur]);
            $value = $stmt->fetchColumn();
            if ($value !== false) {
                return trim((string) $value);
            }

            if (!$this->tableExists('utilisateur')) {
                return null;
            }

            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("
                SELECT {$enseignantResolution} AS id_enseignant
                FROM utilisateur u
                WHERE u.id_utilisateur = ?
                LIMIT 1
            ");
            $stmt->execute([(int) $idEvaluateur]);
            $value = $stmt->fetchColumn();
            return $value !== false ? trim((string) $value) : null;
        } catch (Throwable $e) {
            error_log("Erreur résolution enseignant depuis l'acteur du vote {$idEvaluateur}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ajoute une évaluation pour un rapport
     */
    public function ajouterEvaluation($id_rapport, $id_evaluateur, $decision, $commentaire)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO evaluations_rapports (id_rapport, id_evaluateur, decision_evaluation, commentaire, date_evaluation)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([$id_rapport, $id_evaluateur, $decision, $commentaire]);

            // Auto-finalisation selon la composition active de la commission.
            if ($result && $decision === 'valider') {
                $this->autoFinaliserSiNecessaire($id_rapport, $id_evaluateur);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur ajout évaluation rapport: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si le rapport a 4+ votes 'valider' et l'auto-finalise si ce n'est pas déjà fait
     */
    private function autoFinaliserSiNecessaire($id_rapport, $id_evaluateur)
    {
        try {
            // Vérifier si déjà dans valider
            $checkValider = $this->pdo->prepare("SELECT COUNT(*) FROM valider WHERE id_rapport = ?");
            $checkValider->execute([$id_rapport]);
            if ((int) $checkValider->fetchColumn() > 0) {
                return;
            }

            // La règle historique était fixée à 4 votes. Elle dépend désormais
            // du nombre de membres actifs votants configuré par la commission.
            $membreModel = new CommissionValidationMembre($this->pdo);
            $nombreMembres = $membreModel->getNombreActifs();
            if ($nombreMembres <= 0) {
                return;
            }

            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT er.id_evaluateur) FROM evaluations_rapports er
                INNER JOIN commission_validation_membres cvm ON cvm.id_utilisateur = er.id_evaluateur
                WHERE er.id_rapport = ? AND er.decision_evaluation = 'valider'
                  AND cvm.actif_votant = 1");
            $stmt->execute([$id_rapport]);
            $totalValide = (int) $stmt->fetchColumn();

            if ($totalValide >= $nombreMembres) {
                $idEnseignant = $this->resolveEnseignantIdFromVoteActor($id_evaluateur);
                if ($idEnseignant === null || $idEnseignant === '') {
                    error_log("Auto-finalisation ignorée pour le rapport {$id_rapport}: impossible de résoudre l'enseignant finalisateur.");
                    return;
                }
                $this->pdo->beginTransaction();
                try {
                    $insertValider = $this->pdo->prepare("INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation) VALUES (?, ?, NOW(), ?, 'valider')");
                    $insertValider->execute([$idEnseignant, $id_rapport, 'Validation automatique (' . $nombreMembres . ' votes atteints)']);
                    $this->pdo->commit();
                    error_log("Auto-finalisation effectuée pour le rapport $id_rapport");
                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    error_log("Erreur auto-finalisation rapport $id_rapport: " . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            error_log("Erreur autoFinaliserSiNecessaire: " . $e->getMessage());
        }
    }

    /**
     * Met à jour une évaluation existante
     */
    public function mettreAJourEvaluation($id_evaluation, $decision, $commentaire)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE evaluations_rapports 
                SET decision_evaluation = ?, commentaire = ?, date_modification = NOW()
                WHERE id_evaluation = ?
            ");
            return $stmt->execute([$decision, $commentaire, $id_evaluation]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour évaluation rapport: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un évaluateur a déjà évalué un rapport
     */
    public function evaluationExiste($id_rapport, $id_evaluateur)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_evaluation FROM evaluations_rapports 
                WHERE id_rapport = ? AND id_evaluateur = ?
            ");
            $stmt->execute([$id_rapport, $id_evaluateur]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur vérification évaluation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les évaluations d'un rapport
     */
    public function getEvaluationsRapport($id_rapport)
    {
        try {
            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("
                SELECT e.*,
                       COALESCE(ens.nom_enseignant, u.nom_utilisateur) AS nom_enseignant,
                       COALESCE(ens.prenom_enseignant, '') AS prenom_enseignant,
                       COALESCE(ens.mail_enseignant, u.login_utilisateur) AS mail_enseignant,
                       u.login_utilisateur
                FROM evaluations_rapports e
                LEFT JOIN utilisateur u ON e.id_evaluateur = u.id_utilisateur
                LEFT JOIN enseignants ens ON ens.id_enseignant = {$enseignantResolution}
                WHERE e.id_rapport = ?
                ORDER BY COALESCE(e.date_modification, e.date_evaluation) DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération évaluations rapport: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le statut des votes pour un rapport
     */
    public function getStatutVotes($id_rapport, $nombreMembresCommission = null)
    {
        try {
            if ($nombreMembresCommission === null && $this->tableExists('commission_validation_membres')) {
                $nombreMembresCommission = (new CommissionValidationMembre($this->pdo))->getNombreActifs();
            }
            $nombreMembresCommission = max(0, (int) $nombreMembresCommission);
            if ($nombreMembresCommission === 0) {
                return [
                    'statut' => 'commission_non_configuree',
                    'message' => 'Aucun membre actif n’est configuré.',
                    'peut_finaliser' => false,
                    'votes_valider' => 0,
                    'votes_rejeter' => 0,
                    'total_votes' => 0,
                    'total_membres' => 0,
                ];
            }
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT er.id_evaluateur) as total_votes,
                    COUNT(DISTINCT CASE WHEN er.decision_evaluation = 'valider' THEN er.id_evaluateur END) as votes_valider,
                    COUNT(DISTINCT CASE WHEN er.decision_evaluation = 'rejeter' THEN er.id_evaluateur END) as votes_rejeter
                FROM evaluations_rapports er
                INNER JOIN commission_validation_membres cvm
                    ON cvm.id_utilisateur = er.id_evaluateur
                   AND cvm.actif_votant = 1
                WHERE er.id_rapport = ?
            ");
            $stmt->execute([$id_rapport]);
            $resultats = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalVotes = (int) $resultats['total_votes'];
            $votesValider = (int) $resultats['votes_valider'];
            $votesRejeter = (int) $resultats['votes_rejeter'];

            // Si tous les membres ont voté
            if ($totalVotes >= $nombreMembresCommission) {
                // Si tous ont validé
                if ($votesValider == $nombreMembresCommission) {
                    return [
                        'statut' => 'unanimite_valider',
                        'message' => 'Tous les membres ont validé le rapport',
                        'peut_finaliser' => true,
                        'decision_finale' => 'valider'
                    ];
                }
                // Si au moins un a rejeté
                elseif ($votesRejeter > 0) {
                    return [
                        'statut' => 'rejet_commission',
                        'message' => 'Le rapport a été rejeté par au moins un membre',
                        'peut_finaliser' => true,
                        'decision_finale' => 'rejeter'
                    ];
                }
            }

            // En cours de vote
            return [
                'statut' => 'en_cours',
                'message' => "Vote en cours ($totalVotes/$nombreMembresCommission membres ont voté)",
                'peut_finaliser' => false,
                'votes_valider' => $votesValider,
                'votes_rejeter' => $votesRejeter,
                'total_votes' => $totalVotes,
                'total_membres' => $nombreMembresCommission
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération statut votes: " . $e->getMessage());
            return [
                'statut' => 'erreur',
                'message' => 'Erreur lors de la récupération du statut',
                'peut_finaliser' => false
            ];
        }
    }

    /**
     * Récupère les rapports avec leur statut de vote
     */
    public function getRapportsAvecStatutVote()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $dateExpr = $this->rapportDateExpr('r');
            $hasEtape = $this->columnExists('rapport_etudiants', 'etape_validation');
            $hasDeposer = $this->tableExists('deposer');
            $hasValider = $this->tableExists('valider');
            $hasCommissionMembres = $this->tableExists('commission_validation_membres');

            $joinDeposer = $hasDeposer
                ? "LEFT JOIN deposer d ON r.id_rapport = d.id_rapport"
                : "";
            $joinValider = $hasValider
                ? "LEFT JOIN valider v ON r.id_rapport = v.id_rapport"
                : "";
            $joinCandidature = $this->latestCandidatureJoin('r', 'e', 'cs');

            $where = [];
            if ($joinCandidature !== '') {
                $where[] = "cs.statut_candidature IN ('En attente', 'Validee', 'Validée')";
            }
            if ($hasEtape) {
                $where[] = "r.etape_validation IN ('approuve_communication', 'en_attente_commission', 'valide', 'desapprouve_commission')";
            } elseif ($hasValider) {
                $where[] = "(v.id_rapport IS NULL OR v.decision_validation IN ('valider', 'rejeter'))";
            }
            $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

            $orderSql = $hasDeposer ? 'ORDER BY d.date_depot DESC' : 'ORDER BY ' . $dateExpr . ' DESC';
            $joinCommissionMembres = $hasCommissionMembres
                ? "LEFT JOIN commission_validation_membres cvm ON cvm.id_utilisateur = ev.id_evaluateur AND cvm.actif_votant = 1"
                : '';
            $voteCountExpr = $hasCommissionMembres
                ? "COUNT(DISTINCT CASE WHEN cvm.id_membre IS NOT NULL THEN ev.id_evaluateur END)"
                : 'COUNT(DISTINCT ev.id_evaluateur)';
            $voteValideExpr = $hasCommissionMembres
                ? "COUNT(DISTINCT CASE WHEN cvm.id_membre IS NOT NULL AND ev.decision_evaluation = 'valider' THEN ev.id_evaluateur END)"
                : "COUNT(DISTINCT CASE WHEN ev.decision_evaluation = 'valider' THEN ev.id_evaluateur END)";
            $voteRejeteExpr = $hasCommissionMembres
                ? "COUNT(DISTINCT CASE WHEN cvm.id_membre IS NOT NULL AND ev.decision_evaluation = 'rejeter' THEN ev.id_evaluateur END)"
                : "COUNT(DISTINCT CASE WHEN ev.decision_evaluation = 'rejeter' THEN ev.id_evaluateur END)";

            $sql = "
                SELECT 
                    r.id_rapport,
                    $titleExpr AS nom_rapport,
                    r.theme_rapport,
                    $dateExpr AS date_rapport,
                    " . ($hasEtape ? "r.etape_validation" : "COALESCE(r.statut_rapport, '')") . " AS etape_validation,
                    r.statut_rapport,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    e.promotion_etu,
                    " . $this->getReportAcademicYearExpr('r', 'e', $hasDeposer ? 'd' : null) . " AS id_annee_acad,
                    " . ($hasDeposer ? "d.date_depot" : "$dateExpr") . " AS date_depot,
                    $voteCountExpr as total_votes,
                    $voteValideExpr as votes_valider,
                    $voteRejeteExpr as votes_rejeter
                FROM rapport_etudiants r
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                $joinDeposer
                $joinValider
                $joinCandidature
                LEFT JOIN evaluations_rapports ev ON r.id_rapport = ev.id_rapport
                $joinCommissionMembres
                $whereSql
                GROUP BY r.id_rapport, nom_rapport, r.theme_rapport, date_rapport, 
                         etape_validation, r.statut_rapport, e.nom_etu, e.prenom_etu, 
                         e.email_etu, e.promotion_etu, e.num_carte_etud, e.num_ident_etud, date_depot, cs.id_candidature, cs.statut_candidature
                $orderSql
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports avec statut: " . $e->getMessage());
            return [];
        }
    }
}
