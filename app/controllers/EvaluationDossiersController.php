<?php

namespace App\Controllers;

use PDO;
use App\Models\RapportEtudiant;
use App\Models\Valider;
use App\Models\EvaluationRapport;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;
use Error;

/**
 * EvaluationDossiersController - Gestion de l'évaluation des dossiers par la commission
 * 
 * @package App\Controllers
 */
class EvaluationDossiersController
{
    private PDO $pdo;
    private RapportEtudiant $rapportEtudiant;
    private Valider $validerModel;
    private EvaluationRapport $evaluationRapport;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        RapportEtudiant $rapportEtudiant,
        Valider $validerModel,
        EvaluationRapport $evaluationRapport,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->rapportEtudiant = $rapportEtudiant;
        $this->validerModel = $validerModel;
        $this->evaluationRapport = $evaluationRapport;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'evaluation_dossiers', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur evaluation_dossiers"
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
     * Action : Afficher la liste des dossiers à évaluer (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $GLOBALS['stats'] = $this->getStatistiques();
            $GLOBALS['dossiers'] = $this->evaluationRapport->getRapportsAvecStatutVote();
        } catch (Exception $e) {
            $this->logger->error("Erreur index EvaluationDossiers: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des données.";
        }
    }

    /**
     * Récupère les statistiques pour le tableau de bord
     */
    private function getStatistiques(): array
    {
        try {
            // Dossiers à évaluer
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM rapport_etudiants r
                JOIN deposer d ON r.id_rapport = d.id_rapport
                WHERE r.etape_validation IN ('approuve_communication', 'en_attente_commission')
            ");
            $stmt->execute();
            $aEvaluer = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Dossiers validés
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM rapport_etudiants WHERE etape_validation = 'valide'");
            $stmt->execute();
            $valides = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Dossiers à corriger
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM rapport_etudiants WHERE etape_validation = 'desapprouve_commission'");
            $stmt->execute();
            $aCorriger = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            return [
                'a_evaluer' => $aEvaluer,
                'valides' => $valides,
                'a_corriger' => $aCorriger,
                'moyenne' => '14.5/20' // À dynamiser plus tard si nécessaire
            ];
        } catch (Exception $e) {
            $this->logger->error("Erreur getStatistiques: " . $e->getMessage());
            return ['a_evaluer' => 0, 'valides' => 0, 'a_corriger' => 0, 'moyenne' => '0/20'];
        }
    }

    /**
     * Action : Traitement des requêtes AJAX (POST)
     */
    public function traiterAction(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        try {
            $action = $this->security->sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');
            
            switch ($action) {
                case 'valider_dossier':
                    if (!$this->checkPermission('update')) return;
                    $this->validerDossier((int)$_POST['id_rapport']);
                    break;
                case 'rejeter_dossier':
                    if (!$this->checkPermission('update')) return;
                    $this->rejeterDossier((int)$_POST['id_rapport'], $this->security->sanitizeInput($_POST['commentaire'] ?? ''));
                    break;
                case 'traiter_decision':
                    if (!$this->checkPermission('create')) return;
                    $this->traiterDecisionCommission(
                        (int)$_POST['id_rapport'], 
                        $this->security->sanitizeInput($_POST['decision'] ?? ''), 
                        $this->security->sanitizeInput($_POST['commentaire'] ?? '')
                    );
                    break;
                case 'finaliser_decision':
                    if (!$this->checkPermission('update')) return;
                    $this->finaliserDecisionCommission((int)$_POST['id_rapport']);
                    break;
                case 'get_statistiques':
                    if (!$this->checkPermission('read')) return;
                    echo json_encode($this->getStatistiques());
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            }
        } catch (Exception $e) {
            $this->logger->error("Exception traiterAction: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        } catch (Error $e) {
            $this->logger->error("Error traiterAction: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur système.']);
        }
    }

    /**
     * Récupère l'ID enseignant associé à l'utilisateur admin/commission
     */
    private function getEnseignantIdFromAdmin(int $id_utilisateur): ?int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$id_utilisateur]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$utilisateur) return null;
            
            $stmt = $this->pdo->prepare("SELECT id_enseignant FROM enseignants WHERE mail_enseignant = ?");
            $stmt->execute([$utilisateur['login_utilisateur']]);
            $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($enseignant) return (int)$enseignant['id_enseignant'];
            
            // Fallback (pour dev/debug, à retirer en prod si nécessaire)
            $stmt = $this->pdo->query("SELECT id_enseignant FROM enseignants LIMIT 1");
            $fallback = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fallback ? (int)$fallback['id_enseignant'] : null;
        } catch (Exception $e) {
            $this->logger->error("Erreur getEnseignantIdFromAdmin: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Valide un dossier (Statut final direct)
     */
    private function validerDossier(int $id_rapport): void
    {
        try {
            $id_utilisateur = $_SESSION['id_utilisateur'] ?? 0;
            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            
            if (!$id_enseignant) {
                echo json_encode(['success' => false, 'message' => 'Profil enseignant non trouvé']);
                return;
            }

            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = 'valide', statut_rapport = 'valider' WHERE id_rapport = ?");
            $stmt->execute([$id_rapport]);
            
            $this->validerModel->insererDecision($id_enseignant, $id_rapport, 'valider', 'Validé par la commission');
            $this->auditLog->logValidation($id_utilisateur, 'rapport_etudiants', 'Succès');
            
            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Rapport validé avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur validerDossier: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Erreur lors de la validation."]);
        }
    }

    /**
     * Rejette un dossier (Statut final direct)
     */
    private function rejeterDossier(int $id_rapport, string $commentaire): void
    {
        try {
            $id_utilisateur = $_SESSION['id_utilisateur'] ?? 0;
            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            
            if (!$id_enseignant) {
                echo json_encode(['success' => false, 'message' => 'Profil enseignant non trouvé']);
                return;
            }

            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = 'desapprouve_commission', statut_rapport = 'rejeter' WHERE id_rapport = ?");
            $stmt->execute([$id_rapport]);
            
            $this->validerModel->insererDecision($id_enseignant, $id_rapport, 'rejeter', $commentaire);
            $this->auditLog->logRejet($id_utilisateur, 'rapport_etudiants', 'Succès');
            
            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Rapport rejeté avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur rejeterDossier: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Erreur lors du rejet."]);
        }
    }

    /**
     * Enregistre l'avis d'un membre de la commission
     */
    private function traiterDecisionCommission(int $id_rapport, string $decision, string $commentaire = ''): void
    {
        try {
            $id_utilisateur = $_SESSION['id_utilisateur'] ?? 0;
            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            
            if (!$id_enseignant) {
                echo json_encode(['success' => false, 'message' => 'Enseignant non trouvé']);
                return;
            }

            $evaluationExistante = $this->evaluationRapport->evaluationExiste($id_rapport, $id_enseignant);
            
            if ($evaluationExistante) {
                $success = $this->evaluationRapport->mettreAJourEvaluation($evaluationExistante['id_evaluation'], $decision, $commentaire);
            } else {
                $success = $this->evaluationRapport->ajouterEvaluation($id_rapport, $id_enseignant, $decision, $commentaire);
            }
            
            if ($success) {
                $statutVote = $this->evaluationRapport->getStatutVotes($id_rapport);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Votre évaluation a été enregistrée',
                    'statut_vote' => $statutVote
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => "Erreur lors de l'enregistrement."]);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur traiterDecisionCommission: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Finalise la décision de la commission basée sur les votes
     */
    private function finaliserDecisionCommission(int $id_rapport): void
    {
        try {
            $statutVote = $this->evaluationRapport->getStatutVotes($id_rapport);
            
            if (!$statutVote['peut_finaliser']) {
                echo json_encode(['success' => false, 'message' => 'Impossible de finaliser : vote en cours']);
                return;
            }
            
            $id_utilisateur = $_SESSION['id_utilisateur'] ?? 0;
            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            $decision = $statutVote['decision_finale'];
            
            $this->pdo->beginTransaction();
            
            if ($decision === 'valider') {
                $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = 'valide', statut_rapport = 'valider' WHERE id_rapport = ?");
                $stmt->execute([$id_rapport]);
                $this->validerModel->insererDecision($id_enseignant, $id_rapport, 'valider', 'Validé par consensus de la commission');
                $this->auditLog->logValidation($id_utilisateur, 'rapport_etudiants', 'Succès');
            } else {
                $stmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = 'desapprouve_commission', statut_rapport = 'rejeter' WHERE id_rapport = ?");
                $stmt->execute([$id_rapport]);
                $this->validerModel->insererDecision($id_enseignant, $id_rapport, 'rejeter', 'Rejeté par la commission');
                $this->auditLog->logRejet($id_utilisateur, 'rapport_etudiants', 'Succès');
            }
            
            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Décision finalisée avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur finaliserDecisionCommission: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Erreur lors de la finalisation."]);
        }
    }

    /**
     * Voir les détails d'un rapport
     */
    public function detail(int $id_rapport): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $GLOBALS['rapport'] = $this->rapportEtudiant->getRapportById($id_rapport);
            $GLOBALS['decisions'] = $this->validerModel->getByRapport($id_rapport);
        } catch (Exception $e) {
            $this->logger->error("Erreur detail EvaluationDossiers: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement du dossier.";
        }
    }
}
 