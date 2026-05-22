<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EvaluationRapport.php';
require_once __DIR__ . '/../utils/AcademicYear.php';
require_once __DIR__ . '/../Security/PermissionRegistry.php';

use PDO;
use Exception;
use CheckMaster\Security\PermissionRegistry;

/**
 * Service métier pour le processus de validation des rapports
 *
 * Contient toute la logique métier extraite de ProcessusValidationController :
 * - Statistiques de validation
 * - Récupération des rapports avec évaluations
 * - Gestion des votes et finalisation
 * - Vérification des membres de la commission
 */
class ProcessusValidationService
{
    /** @var PDO */
    private $pdo;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    /**
     * @param PDO $pdo Connexion à la base de données
     */
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function rapportTitleExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'nom_rapport')) {
            return $alias . '.nom_rapport';
        }
        return $alias . '.theme_rapport';
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

        if ($depotAlias !== null && $this->tableExists('deposer')) {
            $candidates[] = $this->getAcademicYearFromDateExpr($depotAlias . '.date_depot');
        }

        $dateExpr = $this->rapportDateExpr($rapportAlias);
        if ($dateExpr !== 'NULL') {
            $candidates[] = $this->getAcademicYearFromDateExpr($dateExpr);
        }

        $candidates[] = $this->getFallbackStudentYearExpr($etudiantAlias);

        return 'COALESCE(' . implode(', ', $candidates) . ')';
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

    private function validatedCandidatureWhere(string $candidatureAlias = 'cs', string $rapportAlias = 'r'): string
    {
        $conditions = [];

        if ($this->tableExists('candidature_soutenance')) {
            $conditions[] = "{$candidatureAlias}.statut_candidature IN ('Validee', 'Validée')";
        }

        if ($this->columnExists('rapport_etudiants', 'etape_validation')) {
            $conditions[] = "{$rapportAlias}.etape_validation IN ('approuve_communication', 'en_attente_commission', 'valide', 'desapprouve_commission')";
        } elseif ($this->columnExists('rapport_etudiants', 'statut_rapport')) {
            $conditions[] = "COALESCE({$rapportAlias}.statut_rapport, '') IN ('valider', 'valide', 'rejeter')";
        }

        if ($conditions === []) {
            return '';
        }

        return ' AND (' . implode(' OR ', $conditions) . ')';
    }

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function yearCondition(string $rapportAlias = 'r', string $etudiantAlias = 'e', ?string $depotAlias = null): array
    {
        $selectedYearId = $this->getSelectedYearId();
        if ($selectedYearId === null || $selectedYearId <= 0) {
            return ['sql' => '', 'params' => []];
        }

        return [
            'sql' => " AND " . $this->getReportAcademicYearExpr($rapportAlias, $etudiantAlias, $depotAlias) . " = :id_annee_acad",
            'params' => [':id_annee_acad' => $selectedYearId],
        ];
    }

    private function normalizeSqlExpr(string $expr): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE($expr, '')), ' ', ''), '-', ''), '''', ''), '.', ''))";
    }

    private function enseignantResolutionSubquery(string $userAlias = 'u'): string
    {

            private function getAdminLikeGroupIds(): array
            {
                $groups = PermissionRegistry::groups();
                $ids = [];
                if (isset($groups['administrateur'])) {
                    $ids[] = (int) $groups['administrateur'];
                }
                if (isset($groups['admin_responsable_filiere'])) {
                    $ids[] = (int) $groups['admin_responsable_filiere'];
                }

                return array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
            }
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

    private function getRapportYearId(int $idRapport): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.id_annee_acad
                FROM (
                    SELECT " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " AS id_annee_acad
                    FROM rapport_etudiants r
                    JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                    WHERE r.id_rapport = ?
                    LIMIT 1
                ) i
                LIMIT 1
            ");
            $stmt->execute([$idRapport]);
            $value = $stmt->fetchColumn();
            return is_numeric($value) ? (int) $value : null;
        } catch (Exception $e) {
            error_log('Erreur getRapportYearId: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques pour le tableau de bord
     *
     * @return array{total_rapports: int, en_cours: int, valides: int, rejetes: int}
     */
    public function getStatistiques()
    {
        try {
            $yearFilter = $this->yearCondition('r', 'e', 'd');
            $joinCandidature = $this->latestCandidatureJoin('r', 'e', 'cs');
            $candidatureWhere = $this->validatedCandidatureWhere('cs', 'r');
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_rapports
                FROM rapport_etudiants r
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                {$joinCandidature}
                WHERE 1=1" . $yearFilter['sql'] . $candidatureWhere
            );
            $stmt->execute($yearFilter['params']);
            $totalRapports = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_rapports'] ?? 0);

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as en_cours
                FROM rapport_etudiants r
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                {$joinCandidature}
                LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.id_rapport IS NULL
                " . $yearFilter['sql'] . $candidatureWhere . "
            ");
            $stmt->execute($yearFilter['params']);
            $enCours = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['en_cours'] ?? 0);

            // Rapports validés par la commission
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT v.id_rapport) as valides
                FROM valider v
                JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                {$joinCandidature}
                WHERE v.decision_validation = 'valider'" . $yearFilter['sql'] . $candidatureWhere . "
            ");
            $stmt->execute($yearFilter['params']);
            $valides = $stmt->fetch(PDO::FETCH_ASSOC)['valides'];

            // Rapports rejetés par la commission
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT v.id_rapport) as rejetes
                FROM valider v
                JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                {$joinCandidature}
                WHERE v.decision_validation = 'rejeter'" . $yearFilter['sql'] . $candidatureWhere . "
            ");
            $stmt->execute($yearFilter['params']);
            $rejetes = $stmt->fetch(PDO::FETCH_ASSOC)['rejetes'];

            return [
                'total_rapports' => $totalRapports,
                'en_cours' => $enCours,
                'valides' => $valides,
                'rejetes' => $rejetes
            ];

        } catch (Exception $e) {
            error_log("Erreur récupération statistiques: " . $e->getMessage());
            return [
                'total_rapports' => 0,
                'en_cours' => 0,
                'valides' => 0,
                'rejetes' => 0
            ];
        }
    }

    /**
     * Récupère tous les rapports approuvés avec leurs évaluations
     *
     * @return array
     */
    public function getRapportsAvecEvaluations()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $dateExpr = $this->rapportDateExpr('r');
            $hasEtape = $this->columnExists('rapport_etudiants', 'etape_validation');
            $yearFilter = $this->yearCondition('r', 'e', 'd');
            $joinCandidature = $this->latestCandidatureJoin('r', 'e', 'cs');
            $candidatureWhere = $this->validatedCandidatureWhere('cs', 'r');

            $sql = "
                SELECT 
                    r.id_rapport,
                    $titleExpr AS nom_rapport,
                    r.theme_rapport,
                    $dateExpr AS date_rapport,
                    " . ($hasEtape ? "r.etape_validation" : "COALESCE(r.statut_rapport, '')") . " AS etape_validation,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    e.promotion_etu,
                    " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " AS id_annee_acad,
                    v.date_validation AS date_approv,
                    v.commentaire_validation AS commentaire_approv,
                    ens.nom_enseignant AS nom_pers_admin,
                    ens.prenom_enseignant AS prenom_pers_admin
                FROM rapport_etudiants r
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                LEFT JOIN deposer d ON d.id_rapport = r.id_rapport
                {$joinCandidature}
                LEFT JOIN (
                    SELECT vv.id_rapport, MAX(vv.date_validation) AS max_date
                    FROM valider vv
                    GROUP BY vv.id_rapport
                ) lv ON lv.id_rapport = r.id_rapport
                LEFT JOIN valider v ON v.id_rapport = lv.id_rapport AND v.date_validation = lv.max_date
                LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                WHERE 1=1" . $yearFilter['sql'] . $candidatureWhere . "
                ORDER BY COALESCE(v.date_validation, $dateExpr) DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($yearFilter['params']);
            $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Pour chaque rapport, récupérer les évaluations
            foreach ($rapports as &$rapport) {
                $rapport['evaluations'] = $this->getEvaluationsRapport($rapport['id_rapport']);
                $rapport['statut_vote'] = $this->getStatutVoteRapport($rapport['id_rapport']);
            }

            return $rapports;

        } catch (Exception $e) {
            error_log("Erreur récupération rapports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les évaluations d'un rapport spécifique
     *
     * @param int $id_rapport
     * @return array
     */
    public function getEvaluationsRapport($id_rapport)
    {
        try {
            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("
                SELECT 
                    er.id_evaluation,
                    er.id_evaluateur,
                    er.decision_evaluation,
                    er.commentaire,
                    er.date_evaluation,
                    COALESCE(e.nom_enseignant, u.nom_utilisateur) AS nom_enseignant,
                    COALESCE(e.prenom_enseignant, '') AS prenom_enseignant,
                    COALESCE(e.mail_enseignant, u.login_utilisateur) AS mail_enseignant,
                    u.login_utilisateur
                FROM evaluations_rapports er
                LEFT JOIN utilisateur u ON er.id_evaluateur = u.id_utilisateur
                LEFT JOIN enseignants e ON e.id_enseignant = {$enseignantResolution}
                WHERE er.id_rapport = ?
                ORDER BY COALESCE(er.date_modification, er.date_evaluation) DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erreur récupération évaluations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le statut de vote d'un rapport
     *
     * @param int $id_rapport
     * @return array{statut: string, message: string, total_votes: int, votes_valider: int, votes_rejeter: int, finalise: bool}
     */
    public function getStatutVoteRapport($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_votes,
                    COUNT(CASE WHEN decision_evaluation = 'valider' THEN 1 END) as votes_valider,
                    COUNT(CASE WHEN decision_evaluation = 'rejeter' THEN 1 END) as votes_rejeter
                FROM evaluations_rapports 
                WHERE id_rapport = ?
            ");
            $stmt->execute([$id_rapport]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalVotes = $result['total_votes'];
            $votesValider = $result['votes_valider'];
            $votesRejeter = $result['votes_rejeter'];

            // Vérifier si le rapport a été finalisé
            $stmt = $this->pdo->prepare("
                SELECT decision_validation 
                FROM valider 
                WHERE id_rapport = ? 
                ORDER BY date_validation DESC 
                LIMIT 1
            ");
            $stmt->execute([$id_rapport]);
            $decisionFinale = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($decisionFinale) {
                return [
                    'statut' => $decisionFinale['decision_validation'] === 'valider' ? 'valide' : 'rejete',
                    'message' => $decisionFinale['decision_validation'] === 'valider' ?
                        "Validé ($totalVotes/4 votes)" : "Rejeté ($totalVotes/4 votes)",
                    'total_votes' => $totalVotes,
                    'votes_valider' => $votesValider,
                    'votes_rejeter' => $votesRejeter,
                    'finalise' => true
                ];
            } else {
                if ($totalVotes == 0) {
                    return [
                        'statut' => 'en_cours',
                        'message' => "En attente (0/4 votes)",
                        'total_votes' => 0,
                        'votes_valider' => 0,
                        'votes_rejeter' => 0,
                        'finalise' => false
                    ];
                } elseif ($totalVotes == 4) {
                    return [
                        'statut' => 'pret_a_finaliser',
                        'message' => "Prêt à finaliser (4/4 votes)",
                        'total_votes' => 4,
                        'votes_valider' => $votesValider,
                        'votes_rejeter' => $votesRejeter,
                        'finalise' => false
                    ];
                } else {
                    return [
                        'statut' => 'en_cours',
                        'message' => "En cours ($totalVotes/4 votes)",
                        'total_votes' => $totalVotes,
                        'votes_valider' => $votesValider,
                        'votes_rejeter' => $votesRejeter,
                        'finalise' => false
                    ];
                }
            }

        } catch (Exception $e) {
            error_log("Erreur récupération statut vote: " . $e->getMessage());
            return [
                'statut' => 'erreur',
                'message' => 'Erreur',
                'total_votes' => 0,
                'votes_valider' => 0,
                'votes_rejeter' => 0,
                'finalise' => false
            ];
        }
    }

    /**
     * Récupère la liste des membres de la commission
     *
     * @return array
     */
    public function getMembresCommission()
    {
        try {
            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    e.mail_enseignant
                FROM utilisateur u
                JOIN enseignants e ON e.id_enseignant = {$enseignantResolution}
                WHERE u.id_GU IN (11, 5)
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erreur récupération membres commission: " . $e->getMessage());
            return [];
        }
    }

    private function getUtilisateursCommissionPourVote(): array
    {
        try {
            if (!$this->tableExists('utilisateur')) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT DISTINCT
                    u.id_utilisateur,
                    u.nom_utilisateur,
                    u.login_utilisateur,
                    u.id_GU
                FROM utilisateur u
                WHERE u.id_GU = 11
                  AND u.statut_utilisateur = 'Actif'
                ORDER BY u.nom_utilisateur
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur récupération utilisateurs commission pour vote: " . $e->getMessage());
            return [];
        }
    }

    public function appliquerVoteAdminAuxMembres($id_rapport, $id_utilisateur, array $session = []): array
    {
        $idRapport = (int) $id_rapport;
        $idUtilisateur = (int) $id_utilisateur;

        if ($idRapport <= 0) {
            return [
                'success' => false,
                'message' => 'Rapport non spécifié.'
            ];
        }

                    $groupIds = array_merge([11], $this->getAdminLikeGroupIds());
                    $groupIds = array_values(array_unique(array_filter($groupIds, static fn ($id) => $id > 0)));
                    if (empty($groupIds)) {
                        return [];
                    }
                    $placeholders = implode(', ', array_fill(0, count($groupIds), '?'));
        if ($idUtilisateur <= 0) {
                    $stmt->execute($groupIds);
                'success' => false,
                'message' => 'Utilisateur connecté introuvable.'
            ];
        }

        $adminGroupIds = $this->getAdminLikeGroupIds();
        if (!in_array((int) ($session['id_GU'] ?? 0), $adminGroupIds, true)) {
            return [
                'success' => false,
                'message' => "Action réservée à l'administrateur."
            ];
        }

        try {
            $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $this->getRapportYearId($idRapport), 'un vote groupé de rapport');
            if (!$writeGuard['success']) {
                return [
                    'success' => false,
                    'message' => $writeGuard['message']
                ];
            }

            $stmt = $this->pdo->prepare("
                SELECT id_evaluation, decision_evaluation, commentaire
                FROM evaluations_rapports
                WHERE id_rapport = ?
                  AND id_evaluateur = ?
                  AND decision_evaluation IN ('valider', 'rejeter')
                ORDER BY COALESCE(date_modification, date_evaluation) DESC, id_evaluation DESC
                LIMIT 1
            ");
            $stmt->execute([$idRapport, $idUtilisateur]);
            $adminVote = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adminVote) {
                return [
                    'success' => false,
                    'message' => "Vous devez d'abord voter sur ce rapport avant d'appliquer votre vote aux autres membres."
                ];
            }

            $status = $this->getStatutVoteRapport($idRapport);
            if (!empty($status['finalise'])) {
                return [
                    'success' => false,
                    'message' => 'Ce rapport est déjà finalisé.'
                ];
            }

            $membres = $this->getUtilisateursCommissionPourVote();
            if (empty($membres)) {
                return [
                    'success' => false,
                    'message' => 'Aucun membre de commission actif trouvé.'
                ];
            }

            $stmt = $this->pdo->prepare("
                SELECT DISTINCT id_evaluateur
                FROM evaluations_rapports
                WHERE id_rapport = ?
            ");
            $stmt->execute([$idRapport]);
            $votants = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            $votantsMap = array_fill_keys($votants, true);
            $missingSlots = max(0, 4 - count($votants));
            if ($missingSlots === 0) {
                return [
                    'success' => false,
                    'message' => 'Le rapport possède déjà les 4 votes attendus.'
                ];
            }

            $targets = [];
            foreach ($membres as $membre) {
                $idMembre = (int) ($membre['id_utilisateur'] ?? 0);
                if ($idMembre <= 0 || $idMembre === $idUtilisateur || isset($votantsMap[$idMembre])) {
                    continue;
                }
                $targets[] = $idMembre;
            }
            $targets = array_slice($targets, 0, $missingSlots);

            if (empty($targets)) {
                return [
                    'success' => false,
                    'message' => 'Tous les membres concernés ont déjà voté pour ce rapport.'
                ];
            }

            $this->pdo->beginTransaction();
            try {
                $insert = $this->pdo->prepare("
                    INSERT INTO evaluations_rapports
                        (id_rapport, id_evaluateur, decision_evaluation, commentaire, date_evaluation)
                    VALUES
                        (?, ?, ?, ?, NOW())
                ");
                foreach ($targets as $idMembre) {
                    $insert->execute([
                        $idRapport,
                        $idMembre,
                        (string) $adminVote['decision_evaluation'],
                        $adminVote['commentaire']
                    ]);
                }
                $this->pdo->commit();
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }

            $decisionLabel = (string) $adminVote['decision_evaluation'] === 'valider' ? 'validation' : 'rejet';
            return [
                'success' => true,
                'message' => count($targets) . ' vote(s) ajouté(s) avec la même décision de ' . $decisionLabel . '.'
            ];
        } catch (Exception $e) {
            error_log("Erreur vote groupé admin: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors du vote groupé : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère les données complètes pour la page
     *
     * @return array{statistiques: array, rapports: array, membres_commission: array}
     */
    public function getDonneesPage()
    {
        return [
            'statistiques' => $this->getStatistiques(),
            'rapports' => $this->getRapportsAvecEvaluations(),
            'membres_commission' => $this->getMembresCommission()
        ];
    }

    /**
     * Vérifie si un ID enseignant existe dans la table enseignants
     *
     * @param int $id_enseignant
     * @return bool
     */
    public function verifierIdEnseignant($id_enseignant)
    {
        try {
            $id_enseignant = trim((string) $id_enseignant);
            if ($id_enseignant === '') {
                return false;
            }
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM enseignants WHERE id_enseignant = ?");
            $stmt->execute([$id_enseignant]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Erreur vérification ID enseignant: " . $e->getMessage());
            return false;
        }
    }

    private function findEnseignantIdByLogin(string $login): ?string
    {
        try {
            $login = trim($login);
            if ($login === '') {
                return null;
            }
            $stmt = $this->pdo->prepare("
                SELECT id_enseignant
                FROM enseignants
                WHERE LOWER(mail_enseignant) = LOWER(?)
                LIMIT 1
            ");
            $stmt->execute([$login]);
            $value = $stmt->fetchColumn();
            return $value !== false ? trim((string) $value) : null;
        } catch (Exception $e) {
            error_log("Erreur recherche enseignant par login: " . $e->getMessage());
            return null;
        }
    }

    private function findEnseignantIdByUtilisateurId($idUtilisateur): ?string
    {
        try {
            $idUtilisateur = (int) $idUtilisateur;
            if ($idUtilisateur <= 0 || !$this->tableExists('utilisateur')) {
                return null;
            }
            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("
                SELECT {$enseignantResolution} AS id_enseignant
                FROM utilisateur u
                WHERE u.id_utilisateur = ?
                LIMIT 1
            ");
            $stmt->execute([$idUtilisateur]);
            $value = $stmt->fetchColumn();
            return $value !== false ? trim((string) $value) : null;
        } catch (Exception $e) {
            error_log("Erreur recherche enseignant par utilisateur: " . $e->getMessage());
            return null;
        }
    }

    public function resolveEnseignantIdFromSession(array $session): ?string
    {
        foreach (['id_enseignant', 'enseignant_id'] as $key) {
            $candidate = trim((string) ($session[$key] ?? ''));
            if ($candidate !== '' && $this->verifierIdEnseignant($candidate)) {
                return $candidate;
            }
        }

        $byLogin = $this->findEnseignantIdByLogin((string) ($session['login_utilisateur'] ?? ''));
        if ($byLogin !== null && $this->verifierIdEnseignant($byLogin)) {
            return $byLogin;
        }

        $byUserId = $this->findEnseignantIdByUtilisateurId($session['id_utilisateur'] ?? 0);
        if ($byUserId !== null && $this->verifierIdEnseignant($byUserId)) {
            return $byUserId;
        }

        return null;
    }

    /**
     * Résout l'identifiant enseignant à partir de l'identifiant utilisateur.
     *
     * @param int $id_utilisateur
     * @return string|null
     */
    public function resoudreIdEnseignantDepuisUtilisateur($id_utilisateur)
    {
        $idUtilisateur = (int) $id_utilisateur;
        if ($idUtilisateur <= 0) {
            return null;
        }

        // Compatibilité: sur certaines installations, l'id utilisateur peut déjà
        // correspondre à l'id enseignant.
        if ($this->verifierIdEnseignant($idUtilisateur)) {
            return (string) $idUtilisateur;
        }

        $resolvedByUser = $this->findEnseignantIdByUtilisateurId($idUtilisateur);
        if ($resolvedByUser !== null && $resolvedByUser !== '') {
            return $resolvedByUser;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ? LIMIT 1");
            $stmt->execute([$idUtilisateur]);
            $login = (string) ($stmt->fetchColumn() ?: '');
            if ($login === '') {
                return null;
            }

            $stmt = $this->pdo->prepare("SELECT id_enseignant FROM enseignants WHERE mail_enseignant = ? LIMIT 1");
            $stmt->execute([$login]);
            $idEnseignant = $stmt->fetchColumn();

            return $idEnseignant !== false ? trim((string) $idEnseignant) : null;
        } catch (Exception $e) {
            error_log("Erreur résolution id enseignant depuis utilisateur: " . $e->getMessage());
            return null;
        }
    }

    private function resolveEnseignantIdForFinalization(int $idRapport, $preferredIdEnseignant = null): ?string
    {
        $preferred = trim((string) $preferredIdEnseignant);
        if ($preferred !== '' && $this->verifierIdEnseignant($preferred)) {
            return $preferred;
        }

        try {
            $enseignantResolution = $this->enseignantResolutionSubquery('u');
            $stmt = $this->pdo->prepare("
                SELECT ens.id_enseignant
                FROM evaluations_rapports er
                LEFT JOIN utilisateur u ON er.id_evaluateur = u.id_utilisateur
                LEFT JOIN enseignants ens ON ens.id_enseignant = {$enseignantResolution}
                WHERE er.id_rapport = ?
                  AND ens.id_enseignant IS NOT NULL
                ORDER BY COALESCE(er.date_modification, er.date_evaluation) DESC, er.id_evaluation DESC
                LIMIT 1
            ");
            $stmt->execute([$idRapport]);
            $value = $stmt->fetchColumn();
            return $value !== false ? trim((string) $value) : null;
        } catch (Exception $e) {
            error_log("Erreur résolution enseignant pour finalisation du rapport {$idRapport}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Finalise la décision pour un rapport
     *
     * @param int $id_rapport
     * @param string $id_enseignant
     * @param string|null $commentaire
     * @return array{success: bool, message: string}
     */
    public function finaliserRapport($id_rapport, $id_enseignant, $commentaire = null)
    {
        try {
            $idRapport = (int) $id_rapport;
            if ($idRapport <= 0) {
                return [
                    'success' => false,
                    'message' => 'Rapport non spécifié.'
                ];
            }

            $idEnseignantFinaliseur = $this->resolveEnseignantIdForFinalization($idRapport, $id_enseignant);
            if ($idEnseignantFinaliseur === null || $idEnseignantFinaliseur === '') {
                return [
                    'success' => false,
                    'message' => 'Impossible de résoudre un enseignant finalisateur pour ce rapport.'
                ];
            }

            $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $this->getRapportYearId($idRapport), 'une finalisation de rapport');
            if (!$writeGuard['success']) {
                return [
                    'success' => false,
                    'message' => $writeGuard['message']
                ];
            }

            // Compter le nombre de validations 'valider'
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM evaluations_rapports WHERE id_rapport = ? AND decision_evaluation = 'valider'");
            $stmt->execute([$idRapport]);
            $row = $stmt->fetch();
            $totalValide = $row['total'];

            // Décision
            $decision = ($totalValide >= 4) ? 'valider' : 'rejeter';

            // Préparer le commentaire
            $commentaireFinal = $commentaire ? trim($commentaire) : 'Décision finale automatique';
            if (empty($commentaireFinal)) {
                $commentaireFinal = 'Décision finale automatique';
            }

            // Insérer/metre à jour la décision finale pour éviter les blocages en cas de doublon.
            $stmtInsert = $this->pdo->prepare("INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation) VALUES (?, ?, NOW(), ?, ?) ON DUPLICATE KEY UPDATE date_validation = NOW(), commentaire_validation = VALUES(commentaire_validation), decision_validation = VALUES(decision_validation)");
            $stmtInsert->execute([$idEnseignantFinaliseur, $idRapport, $commentaireFinal, $decision]);

            // Mettre à jour le statut du rapport et l'étape de validation
            if ($this->columnExists('rapport_etudiants', 'etape_validation')) {
                $etapeValidation = ($decision === 'valider') ? 'valide' : 'desapprouve_commission';
                $stmtUpdate = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ?, etape_validation = ? WHERE id_rapport = ?");
                $stmtUpdate->execute([$decision, $etapeValidation, $idRapport]);
            } else {
                $stmtUpdate = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ? WHERE id_rapport = ?");
                $stmtUpdate->execute([$decision, $idRapport]);
            }

            // Générer le PV de commission si la décision est favorable et qu'un compte-rendu existe
            if ($decision === 'valider') {
                // Vérifier s'il existe un compte-rendu lié à ce rapport
                $compteRenduExist = $this->pdo->prepare("
                    SELECT cr.id_CR 
                    FROM compte_rendu_rapport crr
                    JOIN compte_rendu cr ON crr.id_CR = cr.id_CR
                    WHERE crr.id_rapport = ?
                    LIMIT 1
                ");
                $compteRenduExist->execute([$idRapport]);
                $compteRendu = $compteRenduExist->fetch(PDO::FETCH_ASSOC);

                if ($compteRendu && !empty($compteRendu['id_CR'])) {
                    // Générer le PV de commission
                    try {
                        require_once __DIR__ . '/../Services/Document/PvCommissionGeneratorService.php';
                        require_once __DIR__ . '/../Support/Database.php';
                        require_once __DIR__ . '/../Utils/PlanningDataUtils.php';

                        $pdfGenerator = new \App\Services\Document\PdfGeneratorService(
                            __DIR__ . '/../../storage/documents',
                            __DIR__ . '/../../public/image/logo_ufhb.png'
                        );
                        $dbWrapper = new \App\Support\Database();
                        $dataUtils = new \App\Utils\PlanningDataUtils($dbWrapper);
                        $pvService = new \App\Services\Document\PvCommissionGeneratorService($pdfGenerator, $dataUtils, $dbWrapper);

                        $pvResult = $pvService->generate((int) $compteRendu['id_CR'], $idEnseignantFinaliseur);

                        if (!$pvResult['success']) {
                            error_log("Erreur génération PV commission: " . ($pvResult['error'] ?? 'Erreur inconnue'));
                            // On ne bloque pas la finalisation si la génération du PV échoue
                        }
                    } catch (Exception $e) {
                        error_log("Exception lors de la génération du PV commission: " . $e->getMessage());
                        // On ne bloque pas la finalisation si la génération du PV échoue
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Rapport finalisé avec succès. Décision : ' . ($decision === 'valider' ? 'Validé' : 'Rejeté') . ' (' . $totalValide . '/4 votes favorables)'
            ];

        } catch (Exception $e) {
            error_log("Erreur lors de la finalisation: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la finalisation : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Progression du workflow de validation pour un rapport (P2.4)
     *
     * 5 etapes : Depot -> Evaluation -> Validation -> Soutenance -> PV
     *
     * @param int $idRapport
     * @return array{etapes: array, progression: int, id_rapport: int}
     */
    public function getWorkflowProgress(int $idRapport): array
    {
        $etapes = [
            [
                'code' => 'depot',
                'label' => 'Depot du rapport',
                'icone' => 'fa-upload',
                'statut' => 'en_attente',
                'date' => null,
                'acteur' => null,
                'detail' => null,
            ],
            [
                'code' => 'evaluation',
                'label' => 'Evaluation commission',
                'icone' => 'fa-check-double',
                'statut' => 'en_attente',
                'date' => null,
                'acteur' => null,
                'detail' => null,
            ],
            [
                'code' => 'validation',
                'label' => 'Validation',
                'icone' => 'fa-gavel',
                'statut' => 'en_attente',
                'date' => null,
                'acteur' => null,
                'detail' => null,
            ],
            [
                'code' => 'soutenance',
                'label' => 'Soutenance',
                'icone' => 'fa-chalkboard-user',
                'statut' => 'en_attente',
                'date' => null,
                'acteur' => null,
                'detail' => null,
            ],
            [
                'code' => 'pv',
                'label' => 'PV final',
                'icone' => 'fa-file-pen',
                'statut' => 'en_attente',
                'date' => null,
                'acteur' => null,
                'detail' => null,
            ],
        ];

        $progression = 0;

        try {
            // Etape 1: Depot
            $stmt = $this->pdo->prepare("
                SELECT d.date_depot, e.nom_etu, e.prenom_etu
                FROM deposer d
                JOIN etudiants e ON (d.num_etu = e.num_carte_etud OR d.num_etu = e.num_ident_etud)
                WHERE d.id_rapport = ?
                LIMIT 1
            ");
            $stmt->execute([$idRapport]);
            $depot = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($depot) {
                $etapes[0]['statut'] = 'termine';
                $etapes[0]['date'] = $depot['date_depot'];
                $etapes[0]['acteur'] = trim(($depot['nom_etu'] ?? '') . ' ' . ($depot['prenom_etu'] ?? ''));
                $etapes[0]['detail'] = 'Rapport depose par l etudiant';
                $progression = 1;
            } else {
                // Fallback: verifier si le rapport existe au moins
                $stmt = $this->pdo->prepare("SELECT date_redaction_rapport, num_etu FROM rapport_etudiants WHERE id_rapport = ? LIMIT 1");
                $stmt->execute([$idRapport]);
                $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($rapport) {
                    $etapes[0]['statut'] = 'termine';
                    $etapes[0]['date'] = $rapport['date_redaction_rapport'];
                    $progression = 1;
                }
            }

            // Etape 2: Evaluation
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS nb_evaluations,
                       MAX(date_evaluation) AS derniere_date
                FROM evaluations_rapports
                WHERE id_rapport = ?
            ");
            $stmt->execute([$idRapport]);
            $eval = $stmt->fetch(PDO::FETCH_ASSOC);
            $nbEval = (int) ($eval['nb_evaluations'] ?? 0);
            if ($nbEval > 0) {
                $etapes[1]['statut'] = $nbEval >= 4 ? 'termine' : 'en_cours';
                $etapes[1]['date'] = $eval['derniere_date'];
                $etapes[1]['detail'] = $nbEval . '/4 votes exprimes';
                if ($progression === 1) $progression = 2;
            }

            // Etape 3: Validation (decision finale dans table valider)
            $stmt = $this->pdo->prepare("
                SELECT v.date_validation, v.decision_validation, v.commentaire_validation,
                       e.nom_enseignant, e.prenom_enseignant
                FROM valider v
                LEFT JOIN enseignants e ON v.id_enseignant = e.id_enseignant
                WHERE v.id_rapport = ?
                ORDER BY v.date_validation DESC
                LIMIT 1
            ");
            $stmt->execute([$idRapport]);
            $validation = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($validation) {
                $etapes[2]['statut'] = 'termine';
                $etapes[2]['date'] = $validation['date_validation'];
                $etapes[2]['acteur'] = trim(($validation['nom_enseignant'] ?? '') . ' ' . ($validation['prenom_enseignant'] ?? ''));
                $decLabel = $validation['decision_validation'] === 'valider' ? 'Valide' : 'Rejete';
                $etapes[2]['detail'] = 'Decision: ' . $decLabel;
                if ($progression === 2) $progression = 3;
            }

            // Etape 4: Soutenance (programmation)
            $stmt = $this->pdo->prepare("
                SELECT ps.date_soutenance, ps.heure_soutenance, ps.theme_soutenance,
                       s.lib_salle
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                WHERE ps.num_soutenance IN (
                    SELECT num_soutenance FROM enseignant_jury ej
                    WHERE ej.num_soutenance = ps.num_soutenance
                )
                LIMIT 1
            ");
            // Fallback: chercher par rapport
            $stmt2 = $this->pdo->prepare("
                SELECT ps.date_soutenance, ps.heure_soutenance, ps.theme_soutenance,
                       s.lib_salle
                FROM programmer_soutenance ps
                JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                JOIN rapport_etudiants r ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                WHERE r.id_rapport = ?
                LIMIT 1
            ");
            $stmt2->execute([$idRapport]);
            $soutenance = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($soutenance && !empty($soutenance['date_soutenance'])) {
                $etapes[3]['statut'] = strtotime((string) $soutenance['date_soutenance']) < time() ? 'termine' : 'en_cours';
                $etapes[3]['date'] = $soutenance['date_soutenance'] . ($soutenance['heure_soutenance'] ? ' ' . substr((string) $soutenance['heure_soutenance'], 0, 5) : '');
                $etapes[3]['detail'] = 'Salle: ' . ($soutenance['lib_salle'] ?? 'Non definie');
                if ($progression === 3) $progression = 4;
            }

            // Etape 5: PV final (compte rendu)
            $stmt = $this->pdo->prepare("
                SELECT cr.id_CR, cr.date_CR, cr.nom_CR
                FROM compte_rendu_rapport crr
                JOIN compte_rendu cr ON crr.id_CR = cr.id_CR
                WHERE crr.id_rapport = ?
                LIMIT 1
            ");
            $stmt->execute([$idRapport]);
            $cr = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cr) {
                $etapes[4]['statut'] = 'termine';
                $etapes[4]['date'] = $cr['date_CR'];
                $etapes[4]['detail'] = $cr['nom_CR'];
                $progression = 5;
            } elseif ($soutenance && !empty($soutenance['date_soutenance'])) {
                // Soutenance passee mais pas encore de PV
                $etapes[4]['statut'] = 'en_attente';
            }

        } catch (Exception $e) {
            error_log('ProcessusValidationService::getWorkflowProgress - ' . $e->getMessage());
        }

        return [
            'id_rapport' => $idRapport,
            'progression' => $progression,
            'total_etapes' => 5,
            'etapes' => $etapes,
        ];
    }
}
