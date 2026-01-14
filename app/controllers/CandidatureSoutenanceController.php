<?php

namespace App\Controllers;

use PDO;
use App\Models\Etudiant;
use App\Models\Entreprise;
use App\Models\InfoStage;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * CandidatureSoutenanceController - Gestion des demandes de candidature
 * 
 * @package App\Controllers
 */
class CandidatureSoutenanceController
{
    private PDO $pdo;
    private Etudiant $etudiant;
    private Entreprise $entreprise;
    private InfoStage $stage;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Etudiant $etudiant,
        Entreprise $entreprise,
        InfoStage $stage,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->etudiant = $etudiant;
        $this->entreprise = $entreprise;
        $this->stage = $stage;
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
        
        if (!$this->security->can($idGroupe, 'candidature_soutenance', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur candidature_soutenance"
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
     * Action : Afficher le dashboard de candidature (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $numEtu = $_SESSION['num_etu'] ?? null;
            if (!$numEtu) throw new Exception("Session étudiant invalide.");

            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'demande_candidature':
                        $this->demande_candidature();
                        break;
                    case 'compte_rendu_etudiant':
                        $this->compteRenduRapport();
                        break;
                    case 'info_stage':
                        $this->infoStage();
                        break;
                }
            }

            // Récupérer les informations du stage
            $GLOBALS['stage_info'] = $this->stage->getStageInfo($numEtu);

            // Vérifier si l'étudiant a un compte rendu
            $GLOBALS['compte_rendu'] = $this->etudiant->getCompteRendu($numEtu);

            // Vérifier si l'étudiant a déjà soumis une candidature
            $candidature = $this->etudiant->getCandidature($numEtu);
            $GLOBALS['has_candidature'] = !empty($candidature);

            // Charger toutes les candidatures de l'étudiant
            $GLOBALS['candidatures_etudiant'] = $this->etudiant->getCandidatures($numEtu);

        } catch (Exception $e) {
            $this->logger->error("Erreur CandidatureSoutenanceController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des données.";
        }
    }

    /**
     * Action : Soumettre une demande de candidature (CREATE)
     */
    public function demande_candidature(): void
    {
        if (!$this->checkPermission('create')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $numEtu = $_SESSION['num_etu'] ?? null;
            if (!$numEtu) throw new Exception("Session expirée.");

            // Vérifier si candidature existante bloquante
            $existing = $this->etudiant->getCandidature($numEtu);
            $status = $existing ? $existing['statut_candidature'] : null;

            if ($existing && ($status === 'En attente' || $status === 'Validée')) {
                $GLOBALS['messageErreur'] = "Vous avez déjà une candidature active.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur: Candidature existante");
                return;
            }

            // Vérifier infos stage
            $stage_info = $this->stage->getStageInfo($numEtu);
            if (!$stage_info) {
                $GLOBALS['messageErreur'] = "Veuillez d'abord remplir les informations de stage.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur: Stage manquant");
                return;
            }

            // Créer la candidature
            if ($this->etudiant->createCandidature($numEtu)) {
                $GLOBALS['messageSuccess'] = "Votre candidature a été soumise avec succès.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Succès");
            } else {
                throw new Exception("Erreur lors de l'insertion en base.");
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur demande_candidature: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors de la soumission.";
            $this->auditLog->logCreation($_SESSION['id_utilisateur'] ?? 0, "candidature_soutenance", "Erreur: " . $e->getMessage());
        }
    }

    /**
     * Action : Consulter le compte rendu (READ)
     */
    public function compteRenduRapport(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $numEtu = $_SESSION['num_etu'] ?? null;
            $compte_rendu = $this->etudiant->getCompteRendu($numEtu);
            $GLOBALS['compte_rendu'] = $compte_rendu;

            if (!$compte_rendu) {
                $GLOBALS['messageErreur'] = "Aucun compte rendu disponible pour le moment.";
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur compteRenduRapport: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors de la récupération du compte rendu.";
        }
    }

    /**
     * Action : Enregistrer/Modifier infos stage (UPDATE)
     */
    public function infoStage(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $numEtu = $_SESSION['num_etu'] ?? null;
            $nomEntreprise = $this->security->sanitizeInput($_POST['entreprise'] ?? '');

            if (empty($nomEntreprise)) throw new Exception("Le nom de l'entreprise est requis.");

            // Gérer l'entreprise
            $entreprise = $this->entreprise->getEntrepriseByLibelle($nomEntreprise);
            if (!$entreprise) {
                $this->entreprise->ajouterEntreprise($nomEntreprise);
                $idEntreprise = $this->pdo->lastInsertId();
            } else {
                $idEntreprise = $entreprise->id_entreprise;
            }

            $stageData = [
                'nom_entreprise' => $idEntreprise,
                'date_debut_stage' => $this->security->sanitizeInput($_POST['date_debut'] ?? ''),
                'date_fin_stage' => $this->security->sanitizeInput($_POST['date_fin'] ?? ''),
                'sujet_stage' => $this->security->sanitizeInput($_POST['sujet'] ?? ''),
                'description_stage' => $this->security->sanitizeInput($_POST['description'] ?? ''),
                'encadrant_entreprise' => $this->security->sanitizeInput($_POST['encadrant'] ?? ''),
                'email_encadrant' => $this->security->sanitizeInput($_POST['email_encadrant'] ?? ''),
                'telephone_encadrant' => $this->security->sanitizeInput($_POST['telephone_encadrant'] ?? '')
            ];

            $existing = $this->stage->getStageInfo($numEtu);
            if ($existing) {
                $result = $this->stage->updateStageInfo($numEtu, $stageData);
            } else {
                $result = $this->stage->createStageInfo($numEtu, $stageData);
            }

            if ($result) {
                $GLOBALS['messageSuccess'] = "Informations de stage enregistrées avec succès.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'], "informations_stage", "Succès");
            } else {
                throw new Exception("Erreur lors de l'enregistrement des infos de stage.");
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur infoStage: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors de l'enregistrement.";
            $this->auditLog->logModification($_SESSION['id_utilisateur'] ?? 0, "informations_stage", "Erreur: " . $e->getMessage());
        }
    }
}