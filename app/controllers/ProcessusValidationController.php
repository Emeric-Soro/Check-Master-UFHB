<?php

namespace App\Controllers;

use PDO;
use App\Models\EvaluationRapport;
use App\Models\Valider;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * ProcessusValidationController - Synthèse des évaluations et autorisation de soutenance
 * 
 * Ce contrôleur gère le processus de validation :
 * - Synthèse des évaluations des rapporteurs
 * - Décision finale (validation/rejet)
 * - Autorisation de soutenance
 * 
 * @package App\Controllers
 */
class ProcessusValidationController
{
    private PDO $pdo;
    private EvaluationRapport $evaluationRapport;
    private Valider $valider;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        EvaluationRapport $evaluationRapport,
        Valider $valider,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->evaluationRapport = $evaluationRapport;
        $this->valider = $valider;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'processus_validation', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur processus_validation"
            );
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Accès refusé."]);
            } else {
                $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires.";
                if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                    http_response_code(403);
                    require __DIR__ . '/../../ressources/views/errors/403.php';
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Action : Afficher la page de validation (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $GLOBALS['rapports_a_valider'] = $this->getRapportsAValider();
            $GLOBALS['rapports_valides'] = $this->getRapportsValides();
            $GLOBALS['rapports_rejetes'] = $this->getRapportsRejetes();
        } catch (Exception $e) {
            $this->logger->error("Erreur index ProcessusValidation: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des données.";
        }
    }

    /**
     * Récupère les rapports en attente de validation (approuvés mais pas encore validés)
     */
    private function getRapportsAValider(): array
    {
        try {
            $sql = "
                SELECT DISTINCT
                    r.id_rapport,
                    r.nom_rapport,
                    r.date_rapport,
                    e.num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    a.date_approbation,
                    (SELECT AVG(er.note_ecrite) 
                     FROM evaluations_rapports er 
                     WHERE er.id_rapport = r.id_rapport) as moyenne_notes,
                    (SELECT COUNT(*) 
                     FROM evaluations_rapports er 
                     WHERE er.id_rapport = r.id_rapport) as nb_evaluations
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_etu
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.id_rapport IS NULL
                ORDER BY a.date_approbation ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportsAValider: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les rapports déjà validés
     */
    private function getRapportsValides(): array
    {
        try {
            $sql = "
                SELECT 
                    r.id_rapport,
                    r.nom_rapport,
                    e.num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    v.date_validation,
                    v.decision_validation,
                    v.commentaire_validation,
                    ens.nom_enseignant as validateur_nom,
                    ens.prenom_enseignant as validateur_prenom
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_etu
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                WHERE v.decision_validation = 'valider'
                ORDER BY v.date_validation DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportsValides: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les rapports rejetés
     */
    private function getRapportsRejetes(): array
    {
        try {
            $sql = "
                SELECT 
                    r.id_rapport,
                    r.nom_rapport,
                    e.num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    v.date_validation,
                    v.decision_validation,
                    v.commentaire_validation,
                    ens.nom_enseignant as validateur_nom,
                    ens.prenom_enseignant as validateur_prenom
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_etu
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                WHERE v.decision_validation = 'rejeter'
                ORDER BY v.date_validation DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportsRejetes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails d'un rapport pour la validation (READ)
     */
    public function getDetailsRapport(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $idRapport = (int)($_GET['id_rapport'] ?? 0);

            if ($idRapport === 0) {
                throw new Exception('ID rapport manquant');
            }

            $details = $this->getRapportDetails($idRapport);
            $evaluations = $this->getEvaluationsRapport($idRapport);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'rapport' => $details,
                'evaluations' => $evaluations
            ]);

        } catch (Exception $e) {
            $this->logger->error("Erreur getDetailsRapport: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère les détails d'un rapport
     */
    private function getRapportDetails(int $idRapport): ?array
    {
        $sql = "
            SELECT 
                r.*,
                e.nom_etu,
                e.prenom_etu,
                e.email_etu
            FROM rapport_etudiants r
            INNER JOIN etudiants e ON r.num_etu = e.num_etu
            WHERE r.id_rapport = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idRapport]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupère les évaluations d'un rapport
     */
    private function getEvaluationsRapport(int $idRapport): array
    {
        $sql = "
            SELECT 
                er.*,
                ens.nom_enseignant,
                ens.prenom_enseignant
            FROM evaluations_rapports er
            INNER JOIN enseignants ens ON er.id_evaluateur = ens.id_enseignant
            WHERE er.id_rapport = ?
            ORDER BY er.date_evaluation DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idRapport]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Action : Valider ou rejeter un rapport (UPDATE)
     */
    public function validerRapport(): array
    {
        if (!$this->checkPermission('update')) {
            return ['success' => false, 'message' => 'Accès refusé'];
        }

        try {
            $idRapport = (int)($_POST['id_rapport'] ?? 0);
            $decision = $this->security->sanitizeInput($_POST['decision'] ?? '');
            $commentaire = $_POST['commentaire'] ?? '';
            $idEnseignant = $this->getIdEnseignantConnecte();

            if ($idRapport === 0) {
                throw new Exception('ID rapport manquant');
            }

            if (!in_array($decision, ['valider', 'rejeter'])) {
                throw new Exception('Décision invalide');
            }

            // Vérifier que le rapport n'est pas déjà validé
            $checkSql = "SELECT COUNT(*) as count FROM valider WHERE id_rapport = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$idRapport]);
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                throw new Exception('Ce rapport a déjà été traité');
            }

            // Enregistrer la validation
            $sql = "
                INSERT INTO valider (id_rapport, id_enseignant, decision_validation, commentaire_validation, date_validation)
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idRapport, $idEnseignant, $decision, $commentaire]);

            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'valider', 'Succès');
            $this->logger->info("Rapport {$idRapport} " . ($decision === 'valider' ? 'validé' : 'rejeté'));

            return [
                'success' => true,
                'message' => 'Rapport ' . ($decision === 'valider' ? 'validé' : 'rejeté') . ' avec succès'
            ];

        } catch (Exception $e) {
            $this->logger->error("Erreur validerRapport: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère l'ID de l'enseignant connecté
     */
    private function getIdEnseignantConnecte(): ?int
    {
        try {
            $sql = "SELECT id_enseignant FROM enseignants WHERE mail_enseignant = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$_SESSION['login_utilisateur'] ?? '']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id_enseignant'] : null;
        } catch (Exception $e) {
            $this->logger->error("Erreur getIdEnseignantConnecte: " . $e->getMessage());
            return null;
        }
    }
}
