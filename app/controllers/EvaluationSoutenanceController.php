<?php

namespace App\Controllers;

use PDO;
use App\Models\EvaluationSoutenance;
use App\Models\AnneeAcademique;
use App\Models\CritereEvaluation;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use App\Utils\DocumentGeneratorService;
use App\Utils\FormattingUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * EvaluationSoutenanceController - Gestion de l'évaluation des soutenances
 * 
 * @package App\Controllers
 */
class EvaluationSoutenanceController
{
    private PDO $pdo;
    private EvaluationSoutenance $model;
    private AnneeAcademique $anneeAcadModel;
    private CritereEvaluation $critereModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private DocumentGeneratorService $documentService;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        EvaluationSoutenance $model,
        AnneeAcademique $anneeAcadModel,
        CritereEvaluation $critereModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        DocumentGeneratorService $documentService,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->model = $model;
        $this->anneeAcadModel = $anneeAcadModel;
        $this->critereModel = $critereModel;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->documentService = $documentService;
        $this->logger = $logger;
    }

    /**
     * Vérification des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'evaluation_soutenance', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur evaluation_soutenance"
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
     * Action : Afficher la liste des soutenances programmees (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $GLOBALS['soutenances'] = $this->model->getSoutenancesProgrammees();
            $GLOBALS['anneeCourante'] = $this->getAnneeAcademiqueCourante();
            $GLOBALS['criteres'] = $this->getCriteresEvaluation();
            $GLOBALS['annees'] = $this->anneeAcadModel->getAllAnneeAcademiques();
        } catch (Exception $e) {
            $this->logger->error("Erreur index EvaluationSoutenance: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des données.";
        }
    }

    /**
     * Récupère l'année académique courante
     */
    private function getAnneeAcademiqueCourante(): ?array
    {
        try {
            $dateActuelle = date('Y-m-d');
            $sql = "SELECT id_annee_acad, date_deb, date_fin 
                    FROM annee_academique 
                    WHERE ? BETWEEN date_deb AND date_fin 
                    ORDER BY date_deb DESC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$dateActuelle]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // Fallback: plus récente
                $sql = "SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC LIMIT 1";
                $stmt = $this->pdo->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Erreur getAnneeAcademiqueCourante: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les critères d'évaluation pour l'année courante
     */
    private function getCriteresEvaluation(): array
    {
        try {
            $annee = $this->getAnneeAcademiqueCourante();
            if (!$annee) return [];

            $sql = "SELECT DISTINCT c.id_critere, c.lib_critere, cor.bareme as bareme_max
                    FROM critere_evaluation c
                    INNER JOIN correspondre cor ON c.id_critere = cor.id_critere AND cor.id_annee_acad = ?
                    ORDER BY c.id_critere";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$annee['id_annee_acad']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getCriteresEvaluation: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Action : Enregistrer une évaluation (CREATE)
     */
    public function enregistrerEvaluation(): void
    {
        header('Content-Type: application/json');

        if (!$this->checkPermission('create')) {
            return;
        }

        try {
            $numEtu = $this->security->sanitizeInput($_POST['num_etu'] ?? '');
            $criteres = $_POST['criteres'] ?? []; // Associative array: id_critere => note
            $idAnneeAcad = $this->security->sanitizeInput($_POST['id_annee_acad'] ?? '');
            $commentaireGeneral = $this->security->sanitizeInput($_POST['commentaire_general'] ?? '');

            if (empty($numEtu)) throw new Exception('Numéro étudiant requis');
            if (empty($criteres)) throw new Exception('Au moins un critère requis');

            if (empty($idAnneeAcad)) {
                $annee = $this->getAnneeAcademiqueCourante();
                $idAnneeAcad = $annee['id_annee_acad'] ?? null;
            }
            if (!$idAnneeAcad) throw new Exception('Année académique requise');

            $numJury = $this->model->getNumJury($numEtu);
            if (!$numJury) throw new Exception('Aucun jury trouvé pour cet étudiant');

            $this->pdo->beginTransaction();

            // Supprimer l'ancienne évaluation
            $this->model->deleteEvaluations($numEtu, $numJury);

            $dateEval = date('Y-m-d');
            foreach ($criteres as $idCritere => $note) {
                if (is_numeric($note)) {
                    // Valider note vs barème
                    $stmtB = $this->pdo->prepare("SELECT bareme FROM correspondre WHERE id_critere = ? AND id_annee_acad = ?");
                    $stmtB->execute([$idCritere, $idAnneeAcad]);
                    $bareme = $stmtB->fetchColumn();
                    
                    if ($note > $bareme) {
                        throw new Exception("Note (" . $note . ") dépasse le barème (" . $bareme . ")");
                    }

                    $this->model->saveNote($numEtu, $numJury, (int)$idCritere, $dateEval, (float)$note);
                }
            }

            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'evaluer', 'Succès');
            $this->pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Évaluation enregistrée avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur enregistrerEvaluation: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Action : Supprimer une évaluation (DELETE)
     */
    public function supprimerEvaluation(): void
    {
        header('Content-Type: application/json');

        if (!$this->checkPermission('delete')) {
            return;
        }

        try {
            $numEtu = $this->security->sanitizeInput($_POST['num_etu'] ?? '');
            if (empty($numEtu)) throw new Exception('Numéro étudiant requis');

            if ($this->model->deleteEvaluations($numEtu)) {
                $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'evaluer', 'Succès');
                echo json_encode(['success' => true, 'message' => 'Évaluation supprimée avec succès']);
            } else {
                throw new Exception('Erreur lors de la suppression');
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur supprimerEvaluation: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Action : Imprimer le PV (PRINT/READ)
     */
    public function imprimerPV(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $numEtu = $this->security->sanitizeInput($_GET['num_etu'] ?? '');
            if (empty($numEtu)) throw new Exception('Numéro étudiant requis');

            // Récupérer données soutenance
            $sql = "SELECT p.*, e.nom_etu, e.prenom_etu, e.promotion_etu, ist.encadrant_entreprise as maitre_stage
                    FROM programmer p
                    JOIN etudiants e ON p.num_etud = e.num_etu
                    LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                    WHERE e.num_etu = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$numEtu]);
            $soutenance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$soutenance) throw new Exception('Soutenance non trouvée');

            // Récupérer rôles jury
            $roles = ['President du jury' => 'president', 'Examinateur' => 'examinateur', 'Directeur de mémoire' => 'directeur', 'Encadrant' => 'encadreur'];
            $jury = [];
            foreach ($roles as $role => $key) {
                $sqlJ = "SELECT CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant) as nom
                         FROM composer_jury cj
                         JOIN enseignants ens ON cj.id_enseignant = ens.id_enseignant
                         JOIN roles_jury r ON cj.id_qualite_jury = r.id_role_jury
                         WHERE cj.num_jury = ? AND r.lib_role = ? LIMIT 1";
                $stmtJ = $this->pdo->prepare($sqlJ);
                $stmtJ->execute([$soutenance['num_jury'], $role]);
                $jury[$key] = $stmtJ->fetchColumn() ?: '';
            }

            // Récupérer évaluations
            $evaluations = $this->model->getEvaluations($numEtu);
            $criteresPourTemplate = [];
            $sommeNotes = 0; $sommeBaremes = 0;

            foreach ($evaluations as $eval) {
                // Get bareme for specific critere
                // Need year of defense
                $yearId = $this->getYearFromDate($soutenance['date_soutenance']);
                $stmtB = $this->pdo->prepare("SELECT bareme FROM correspondre WHERE id_critere = ? AND id_annee_acad = ?");
                $stmtB->execute([$eval['id_critere'], $yearId]);
                $bareme = $stmtB->fetchColumn() ?: 0;

                $sommeNotes += (float)$eval['note'];
                $sommeBaremes += (float)$bareme;

                $criteresPourTemplate[] = [
                    'lib_critere' => FormattingUtils::sanitizeText($eval['lib_critere']),
                    'note' => FormattingUtils::formatDecimal($eval['note'], 2),
                    'bareme' => FormattingUtils::formatDecimal($bareme, 1)
                ];
            }

            $moyennes = $this->calculerMoyennesPourAnnexe2($numEtu);
            
            // Logic coefficients
            $noteFinalePV = ($moyennes['moyenne_master1'] * 2 + $moyennes['moyenne_s1_master2'] * 3 + $sommeNotes * 3) / 8;
            $noteFinaleFC = ($moyennes['moyenne_master1'] * 1 + $sommeNotes * 2) / 3;

            $templateData = [
                'nom_etudiant' => $soutenance['prenom_etu'] . ' ' . $soutenance['nom_etu'],
                'promotion' => $soutenance['promotion_etu'],
                'theme' => $soutenance['theme_soutenance'],
                'date_soutenance' => FormattingUtils::formatDate($soutenance['date_soutenance']),
                'president' => $jury['president'],
                'examinateur' => $jury['examinateur'],
                'directeur' => $jury['directeur'],
                'encadreur' => $jury['encadreur'],
                'maitre_stage' => $soutenance['maitre_stage'] ?: '',
                'note_finale' => FormattingUtils::formatDecimal($sommeNotes, 2),
                'total_bareme' => FormattingUtils::formatDecimal($sommeBaremes, 2),
                'criteres' => $criteresPourTemplate,
                'note_finale_pv' => FormattingUtils::formatDecimal($noteFinalePV, 2),
                'mention' => $this->calculerMention($noteFinalePV),
                'note_finale_fc' => FormattingUtils::formatDecimal($noteFinaleFC, 2),
                'mention_fc' => $this->calculerMention($noteFinaleFC),
                'moyenne_master1' => FormattingUtils::formatDecimal($moyennes['moyenne_master1'], 2),
                'moyenne_s1_master2' => FormattingUtils::formatDecimal($moyennes['moyenne_s1_master2'], 2)
            ];

            $pdfPath = $this->documentService->generateFromTemplate('pv_soutenance', $templateData);
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="PV_' . $numEtu . '.pdf"');
            readfile($pdfPath);
            $this->documentService->cleanupTempFile($pdfPath);
            exit;
        } catch (Exception $e) {
            $this->logger->error("Erreur imprimerPV: " . $e->getMessage());
            echo "Erreur lors de la génération du PV : " . $e->getMessage();
        }
    }

    private function getYearFromDate(string $date): ?string
    {
        $stmt = $this->pdo->prepare("SELECT id_annee_acad FROM annee_academique WHERE ? BETWEEN date_deb AND date_fin LIMIT 1");
        $stmt->execute([$date]);
        return $stmt->fetchColumn() ?: null;
    }

    private function calculerMention(float $note): string
    {
        if ($note >= 16) return 'Très Bien';
        if ($note >= 14) return 'Bien';
        if ($note >= 12) return 'Assez Bien';
        if ($note >= 10) return 'Passable';
        return 'Insuffisant';
    }

    private function calculerMoyennesPourAnnexe2(string $numEtu): array
    {
        try {
            // Moyenne M1
            $stmt = $this->pdo->prepare("SELECT resume_json FROM resume_candidature WHERE num_etu = ? ORDER BY date_enregistrement DESC LIMIT 1");
            $stmt->execute([$numEtu]);
            $resume = $stmt->fetchColumn();
            $moyM1 = 0;
            if ($resume) {
                $data = json_decode($resume, true);
                if (isset($data['semestre']['moyenne'])) {
                    $moyM1 = (float)str_replace('/20', '', $data['semestre']['moyenne']);
                }
            }

            // Moyenne S1 M2
            $stmt = $this->pdo->prepare("SELECT SUM(n.moyenne * u.credit) / SUM(u.credit) FROM notes n JOIN ue u ON n.id_ue = u.id_ue WHERE n.num_etu = ? AND u.id_niveau_etude = 10 AND u.id_semestre = 20 AND n.moyenne IS NOT NULL");
            $stmt->execute([$numEtu]);
            $moyM2 = (float)$stmt->fetchColumn();

            return ['moyenne_master1' => $moyM1, 'moyenne_s1_master2' => $moyM2];
        } catch (Exception $e) {
            $this->logger->error("Erreur calculerMoyennes: " . $e->getMessage());
            return ['moyenne_master1' => 0, 'moyenne_s1_master2' => 0];
        }
    }

    /**
     * AJAX : Get criteres per year
     */
    public function getCriteresParAnnee(): void
    {
        header('Content-Type: application/json');
        try {
            $idAnnee = $this->security->sanitizeInput($_GET['id_annee_acad'] ?? '');
            if (!$idAnnee) throw new Exception('ID année requis');

            $sql = "SELECT DISTINCT c.id_critere, c.lib_critere, cor.bareme as bareme_max
                    FROM critere_evaluation c
                    INNER JOIN correspondre cor ON c.id_critere = cor.id_critere AND cor.id_annee_acad = ?
                    ORDER BY c.id_critere";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idAnnee]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

?>