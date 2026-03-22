<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EvaluationRapport.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use PDO;
use Exception;

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

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function yearCondition(string $alias = 'e'): array
    {
        $selectedYearId = $this->getSelectedYearId();
        if ($selectedYearId === null || $selectedYearId <= 0) {
            return ['sql' => '', 'params' => []];
        }

        $studentCarteExpr = $this->studentCarteExpr($alias);
        if ($this->columnExists('inscriptions', 'num_carte_etud') && $this->columnExists('inscriptions', 'id_annee_acad')) {
            return [
                'sql' => " AND EXISTS (SELECT 1 FROM inscriptions i WHERE i.num_carte_etud = {$studentCarteExpr} AND i.id_annee_acad = :id_annee_acad)",
                'params' => [':id_annee_acad' => $selectedYearId],
            ];
        }

        if ($this->columnExists('etudiants', 'id_annee_acad')) {
            return [
                'sql' => " AND {$alias}.id_annee_acad = :id_annee_acad",
                'params' => [':id_annee_acad' => $selectedYearId],
            ];
        }

        return [
            'sql' => '',
            'params' => [],
        ];
    }

    private function getRapportYearId(int $idRapport): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.id_annee_acad
                FROM rapport_etudiants r
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                JOIN inscriptions i ON i.id_inscription = (
                    SELECT i2.id_inscription FROM inscriptions i2
                    WHERE i2.num_carte_etud = " . $this->studentCarteExpr('e') . "
                    ORDER BY i2.date_inscription DESC, i2.id_inscription DESC LIMIT 1
                )
                WHERE r.id_rapport = ?
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
            $yearFilter = $this->yearCondition('e');
            if ($this->tableExists('approuver')) {
                // Total des rapports approuvés par la chargée de communication
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(DISTINCT a.id_rapport) as total_rapports
                    FROM approuver a
                    JOIN rapport_etudiants r ON a.id_rapport = r.id_rapport
                    JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    WHERE a.decision = 'approuve'" . $yearFilter['sql'] . "
                ");
                $stmt->execute($yearFilter['params']);
                $totalRapports = $stmt->fetch(PDO::FETCH_ASSOC)['total_rapports'];

                // Rapports en cours d'évaluation (approuvés mais pas encore finalisés)
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(DISTINCT a.id_rapport) as en_cours
                    FROM approuver a
                    JOIN rapport_etudiants r ON a.id_rapport = r.id_rapport
                    JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN valider v ON a.id_rapport = v.id_rapport
                    WHERE a.decision = 'approuve' AND v.id_rapport IS NULL" . $yearFilter['sql'] . "
                ");
                $stmt->execute($yearFilter['params']);
                $enCours = $stmt->fetch(PDO::FETCH_ASSOC)['en_cours'];
            } else {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as total_rapports FROM rapport_etudiants");
                $stmt->execute();
                $totalRapports = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_rapports'] ?? 0);

                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as en_cours
                    FROM rapport_etudiants r
                    LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                    WHERE v.id_rapport IS NULL
                ");
                $stmt->execute();
                $enCours = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['en_cours'] ?? 0);
            }

            // Rapports validés par la commission
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT v.id_rapport) as valides
                FROM valider v
                JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                WHERE v.decision_validation = 'valider'" . $yearFilter['sql'] . "
            ");
            $stmt->execute($yearFilter['params']);
            $valides = $stmt->fetch(PDO::FETCH_ASSOC)['valides'];

            // Rapports rejetés par la commission
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT v.id_rapport) as rejetes
                FROM valider v
                JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                WHERE v.decision_validation = 'rejeter'" . $yearFilter['sql'] . "
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
            $yearFilter = $this->yearCondition('e');

            if ($this->tableExists('approuver')) {
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
                        a.date_approv,
                        a.commentaire_approv,
                        pa.nom_pers_admin,
                        pa.prenom_pers_admin
                    FROM approuver a
                    JOIN rapport_etudiants r ON a.id_rapport = r.id_rapport
                    JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN personnel_admin pa ON a.id_pers_admin = pa.id_pers_admin
                    WHERE a.decision = 'approuve'
                    " . $yearFilter['sql'] . "
                    ORDER BY a.date_approv DESC
                ";
            } else {
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
                        v.date_validation AS date_approv,
                        v.commentaire_validation AS commentaire_approv,
                        ens.nom_enseignant AS nom_pers_admin,
                        ens.prenom_enseignant AS prenom_pers_admin
                    FROM rapport_etudiants r
                    JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN (
                        SELECT vv.id_rapport, MAX(vv.date_validation) AS max_date
                        FROM valider vv
                        GROUP BY vv.id_rapport
                    ) lv ON lv.id_rapport = r.id_rapport
                    LEFT JOIN valider v ON v.id_rapport = lv.id_rapport AND v.date_validation = lv.max_date
                    LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                    WHERE 1=1" . $yearFilter['sql'] . "
                    ORDER BY COALESCE(v.date_validation, $dateExpr) DESC
                ";
            }
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
            $stmt = $this->pdo->prepare("
                SELECT 
                    er.id_evaluation,
                    er.id_evaluateur,
                    er.decision_evaluation,
                    er.commentaire,
                    er.date_evaluation,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    e.mail_enseignant
                FROM evaluations_rapports er
                JOIN enseignants e ON er.id_evaluateur = e.id_enseignant
                WHERE er.id_rapport = ?
                ORDER BY er.date_evaluation DESC
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
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    e.mail_enseignant
                FROM enseignants e
                JOIN utilisateur u ON e.mail_enseignant = u.login_utilisateur
                WHERE u.id_GU = 11 or u.id_GU = 5
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erreur récupération membres commission: " . $e->getMessage());
            return [];
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

    /**
     * Résout l'identifiant enseignant à partir de l'identifiant utilisateur.
     *
     * @param int $id_utilisateur
     * @return int|null
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
            return $idUtilisateur;
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

            return is_numeric($idEnseignant) ? (int) $idEnseignant : null;
        } catch (Exception $e) {
            error_log("Erreur résolution id enseignant depuis utilisateur: " . $e->getMessage());
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
            $id_rapport = (int) $id_rapport;
            $id_enseignant = trim((string) $id_enseignant);
            if ($id_enseignant === '' || !$this->verifierIdEnseignant($id_enseignant)) {
                $fallbackEvaluateur = $this->findFallbackEvaluateurIdForRapport($id_rapport);
                if ($fallbackEvaluateur !== null && $this->verifierIdEnseignant($fallbackEvaluateur)) {
                    $id_enseignant = $fallbackEvaluateur;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Impossible de finaliser: identifiant enseignant introuvable.'
                    ];
                }
            }

            $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $this->getRapportYearId($id_rapport), 'une finalisation de rapport');
            if (!$writeGuard['success']) {
                return [
                    'success' => false,
                    'message' => $writeGuard['message']
                ];
            }

            // Compter le nombre de validations 'valider'
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM evaluations_rapports WHERE id_rapport = ? AND decision_evaluation = 'valider'");
            $stmt->execute([$id_rapport]);
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
            $stmtInsert->execute([$id_enseignant, $id_rapport, $commentaireFinal, $decision]);

            // Mettre à jour le statut du rapport et l'étape de validation
            if ($this->columnExists('rapport_etudiants', 'etape_validation')) {
                $etapeValidation = ($decision === 'valider') ? 'valide' : 'desapprouve_commission';
                $stmtUpdate = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ?, etape_validation = ? WHERE id_rapport = ?");
                $stmtUpdate->execute([$decision, $etapeValidation, $id_rapport]);
            } else {
                $stmtUpdate = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ? WHERE id_rapport = ?");
                $stmtUpdate->execute([$decision, $id_rapport]);
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
                $compteRenduExist->execute([$id_rapport]);
                $compteRendu = $compteRenduExist->fetch(PDO::FETCH_ASSOC);

                if ($compteRendu && !empty($compteRendu['id_CR'])) {
                    // Générer le PV de commission
                    try {
                        require_once __DIR__ . '/../Services/Document/PvCommissionGeneratorService.php';
                        require_once __DIR__ . '/../Support/Database.php';
                        require_once __DIR__ . '/../Utils/PlanningDataUtils.php';

                        $pdfGenerator = new \App\Services\Document\PdfGeneratorService(
                            __DIR__ . '/../../storage',
                            __DIR__ . '/../../public/assets/img/logo.png'
                        );
                        $dataUtils = new \App\Utils\PlanningDataUtils(new \App\Support\Database($this->pdo));
                        $pvService = new \App\Services\Document\PvCommissionGeneratorService($pdfGenerator, $dataUtils, new \App\Support\Database($this->pdo));

                        $pvResult = $pvService->generate((int) $compteRendu['id_CR'], $id_enseignant);

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
}
