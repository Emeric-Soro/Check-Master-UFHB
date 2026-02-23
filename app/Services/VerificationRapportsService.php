<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;

/**
 * Service métier de la vérification des rapports
 *
 * Contient toute la logique métier extraite du VerificationRapportsController :
 * - Récupération et statistiques des rapports déposés
 * - Validation (approbation) d'un rapport
 * - Rejet (désapprobation) d'un rapport
 * - Détails et décisions d'évaluation
 */
class VerificationRapportsService
{
    /** @var RapportEtudiant */
    private $rapportModel;

    /** @var Approuver */
    private $approbationModel;

    /** @var PersAdmin */
    private $persAdminModel;

    /** @var AuditLog */
    private $auditLog;

    /** @var \PDO */
    private $pdo;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    /**
     * @param \PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->pdo = $db;
        $this->rapportModel = new RapportEtudiant($db);
        $this->approbationModel = new Approuver($db);
        $this->persAdminModel = new PersAdmin($db);
        $this->auditLog = new AuditLog($db);
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function updateRapportEtape($idRapport, $etape, $fallbackStatut)
    {
        if ($this->columnExists('rapport_etudiants', 'etape_validation')) {
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = ?, statut_rapport = ? WHERE id_rapport = ?");
            $stmt->execute([$etape, $fallbackStatut, $idRapport]);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ? WHERE id_rapport = ?");
        $stmt->execute([$fallbackStatut, $idRapport]);
    }

    // ========================= LISTE & STATISTIQUES =========================

    /**
     * Récupère les rapports déposés et les statistiques associées
     *
     * @return array ['rapports' => array, 'nbRapports' => int, 'statsRapports' => array]
     */
    public function getIndexData()
    {
        $rapports = $this->rapportModel->getRapportsDeposes();
        $stats = $this->getStatsRapports();

        return [
            'rapports' => $rapports,
            'nbRapports' => count($rapports),
            'statsRapports' => $stats,
        ];
    }

    /**
     * Récupère les statistiques des rapports par statut
     *
     * @return array
     */
    public function getStatsRapports()
    {
        try {
            $rapports = $this->rapportModel->getRapportsDeposes();
            $stats = [
                'total' => 0, // en_attente_communication
                'approuves' => 0, // approuve_communication
                'desapprouves' => 0 // desapprouve_communication
            ];
            foreach ($rapports as $rapport) {
                if ($this->columnExists('rapport_etudiants', 'etape_validation')) {
                    $etape = $rapport->etape_validation ?? '';
                    if ($etape === 'en_attente_communication') {
                        $stats['total']++;
                    } elseif ($etape === 'approuve_communication') {
                        $stats['approuves']++;
                    } elseif ($etape === 'desapprouve_communication') {
                        $stats['desapprouves']++;
                    }
                } else {
                    $statut = strtolower((string) ($rapport->statut_rapport ?? ''));
                    if ($statut === 'valider' || $statut === 'valide') {
                        $stats['approuves']++;
                    } elseif ($statut === 'rejeter' || $statut === 'desapprouve_communication') {
                        $stats['desapprouves']++;
                    } else {
                        $stats['total']++;
                    }
                }
            }
            return $stats;
        } catch (\Exception $e) {
            error_log("Erreur lors du calcul des statistiques: " . $e->getMessage());
            return [
                'total' => 0,
                'approuves' => 0,
                'desapprouves' => 0
            ];
        }
    }

    // ========================= VALIDATION =========================

    /**
     * Valider un rapport (approuver)
     *
     * @param int    $id_rapport  ID du rapport
     * @param string $commentaire Commentaire de l'approbation
     * @return array ['success' => bool, 'message' => string]
     */
    public function validerRapport($id_rapport, $commentaire)
    {
        try {
            $id_approb = 4; // Niveau 2 (id_approb=4 dans la table niveau_approbation)

            $id_admin = $this->resolveCurrentAdminId();

            if (!$id_rapport || !$commentaire || !$id_admin) {
                return ['success' => false, 'message' => 'Paramètres manquants ou administrateur non reconnu'];
            }

            if ($this->tableExists('approuver')) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO approuver (id_rapport, id_pers_admin, commentaire_approv, decision, date_approv, id_approb)
                    VALUES (?, ?, ?, 'approuve', NOW(), ?)
                ");

                if (!$stmt->execute([$id_rapport, $id_admin, $commentaire, $id_approb])) {
                    $errorInfo = $stmt->errorInfo();
                    error_log('APPROBATION SQL ERROR: ' . ($errorInfo[2] ?? ''));
                    return ['success' => false, 'message' => "Erreur lors de l'approbation : " . ($errorInfo[2] ?? 'inconnue')];
                }
            }

            $this->updateRapportEtape($id_rapport, 'approuve_communication', 'valider');
            return ['success' => true, 'message' => 'Rapport approuve avec succes'];
        } catch (\Exception $e) {
            error_log("Erreur approbation rapport: " . $e->getMessage());
            return ['success' => false, 'message' => "Exception : " . $e->getMessage()];
        }
    }

    // ========================= REJET =========================

    /**
     * Rejeter un rapport (désapprouver)
     *
     * @param int    $id_rapport  ID du rapport
     * @param string $commentaire Commentaire du rejet
     * @return array ['success' => bool, 'message' => string]
     */
    public function rejeterRapport($id_rapport, $commentaire)
    {
        try {
            $id_approb = 4; // Niveau 2 (id_approb=4 dans la table niveau_approbation)

            $id_admin = $this->resolveCurrentAdminId();

            if (!$id_rapport || !$commentaire || !$id_admin) {
                return ['success' => false, 'message' => 'Paramètres manquants ou administrateur non reconnu'];
            }

            if ($this->tableExists('approuver')) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO approuver (id_rapport, id_pers_admin, commentaire_approv, decision, date_approv, id_approb)
                    VALUES (?, ?, ?, 'desapprouve', NOW(), ?)
                ");

                if (!$stmt->execute([$id_rapport, $id_admin, $commentaire, $id_approb])) {
                    $errorInfo = $stmt->errorInfo();
                    error_log('APPROBATION SQL ERROR: ' . ($errorInfo[2] ?? ''));
                    return ['success' => false, 'message' => "Erreur lors de la desapprobation : " . ($errorInfo[2] ?? 'inconnue')];
                }
            }

            $this->updateRapportEtape($id_rapport, 'desapprouve_communication', 'rejeter');
            return ['success' => true, 'message' => 'Rapport rejete avec succes'];
        } catch (\Exception $e) {
            error_log("Erreur désapprobation rapport: " . $e->getMessage());
            return ['success' => false, 'message' => "Exception : " . $e->getMessage()];
        }
    }

    // ========================= DÉTAILS =========================

    /**
     * Récupérer les détails d'un rapport
     *
     * @param int $id_rapport
     * @return object|null
     */
    public function getRapportDetail($id_rapport)
    {
        try {
            return $this->rapportModel->getRapportDetail($id_rapport);
        } catch (\Exception $e) {
            error_log("Erreur récupération détail rapport: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les décisions d'évaluation d'un rapport
     *
     * @param int $id_rapport
     * @return array
     */
    public function getDecisionsEvaluation($id_rapport)
    {
        try {
            return $this->rapportModel->getDecisionsEvaluation($id_rapport);
        } catch (\Exception $e) {
            error_log("Erreur récupération décisions évaluation: " . $e->getMessage());
            return [];
        }
    }

    // ========================= HELPERS PRIVÉS =========================

    /**
     * Résout l'ID de l'administrateur courant à partir de la session
     *
     * @return int|null
     */
    private function resolveCurrentAdminId()
    {
        $id_admin = null;
        Session::start();
        if (!empty($_SESSION['id_utilisateur'])) {
            $pers = $this->persAdminModel->getByUserId($_SESSION['id_utilisateur']);
            if ($pers) {
                if (is_object($pers) && isset($pers->id_pers_admin)) {
                    $id_admin = $pers->id_pers_admin;
                } elseif (is_array($pers) && isset($pers['id_pers_admin'])) {
                    $id_admin = $pers['id_pers_admin'];
                }
            }
        }
        return $id_admin;
    }
}
