<?php

namespace App\Controllers;

use PDO;
use App\Models\CritereEvaluation;
use App\Models\AnneeAcademique;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * CriteresEvaluationController - Configuration des barèmes de notation
 * 
 * @package App\Controllers
 */
class CriteresEvaluationController
{
    private PDO $pdo;
    private CritereEvaluation $critereModel;
    private AnneeAcademique $anneeAcadModel;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        CritereEvaluation $critereModel,
        AnneeAcademique $anneeAcadModel,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->critereModel = $critereModel;
        $this->anneeAcadModel = $anneeAcadModel;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'parametres_generaux', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur parametres_generaux"
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
     * Action : Afficher la page principale (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }
    }

    /**
     * Action : Récupérer toutes les années académiques (API/READ)
     */
    public function getAnneesAcademiques(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $annees = $this->anneeAcadModel->getAllAnneeAcademiques();
            $data = array_map(function($a) {
                return [
                    'id' => $a['id_annee_acad'],
                    'lib' => $a['date_deb'] . ' - ' . $a['date_fin']
                ];
            }, $annees);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->logger->error("Erreur getAnneesAcademiques: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors du chargement.']);
        }
    }

    /**
     * Action : Récupérer tous les critères d'évaluation (API/READ)
     */
    public function getCriteres(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $results = $this->critereModel->getAllCriteres();

            $criteresData = [];
            foreach ($results as $row) {
                $critereId = $row['id'];
                if (!isset($criteresData[$critereId])) {
                    $criteresData[$critereId] = [
                        'id' => $critereId,
                        'libelle' => $row['libelle'],
                        'baremes' => []
                    ];
                }
                if ($row['annee_id'] && $row['bareme'] !== null) {
                    $criteresData[$critereId]['baremes'][] = [
                        'annee_id' => $row['annee_id'],
                        'annee_lib' => $row['annee_lib'],
                        'bareme' => (int) $row['bareme']
                    ];
                }
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => array_values($criteresData)]);
        } catch (Exception $e) {
            $this->logger->error("Erreur getCriteres: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors du chargement.']);
        }
    }

    /**
     * Action : Créer un critère (CREATE)
     */
    public function createCritere(): void
    {
        if (!$this->checkPermission('create')) {
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
                throw new Exception('Libellé requis');
            }
            if (!isset($input['baremes']) || empty($input['baremes'])) {
                throw new Exception('Au moins un barème est requis');
            }

            $this->validateBaremesTotaux($input['baremes'], null);

            $this->pdo->beginTransaction();
            $critereId = $this->critereModel->createCritere($this->security->sanitizeInput($input['libelle']));

            foreach ($input['baremes'] as $bareme) {
                if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                    $this->critereModel->addBareme($critereId, $bareme['annee_id'], (int)$bareme['bareme']);
                }
            }
            $this->pdo->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Critère créé avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur createCritere: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Action : Mettre à jour un critère (UPDATE)
     */
    public function updateCritere(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            if (empty($input['id']) || empty($input['libelle'])) {
                throw new Exception('Données manquantes');
            }

            $this->validateBaremesTotaux($input['baremes'], $input['id']);

            $this->pdo->beginTransaction();
            $this->critereModel->updateCritere((int)$input['id'], $this->security->sanitizeInput($input['libelle']));
            $this->critereModel->deleteBaremesByCritere((int)$input['id']);

            foreach ($input['baremes'] as $bareme) {
                if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                    $this->critereModel->addBareme((int)$input['id'], $bareme['annee_id'], (int)$bareme['bareme']);
                }
            }
            $this->pdo->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Critère modifié avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur updateCritere: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Action : Supprimer un critère (DELETE)
     */
    public function deleteCritere(): void
    {
        if (!$this->checkPermission('delete')) {
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (empty($input['id'])) throw new Exception('ID manquant');

            if (!$this->critereModel->exists((int)$input['id'])) {
                throw new Exception('Critère non trouvé');
            }

            $this->pdo->beginTransaction();
            $this->critereModel->deleteBaremesByCritere((int)$input['id']);
            $this->critereModel->deleteCritere((int)$input['id']);
            $this->pdo->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Critère supprimé avec succès']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error("Erreur deleteCritere: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Valide que le total des points par année ne dépasse pas 20.
     */
    private function validateBaremesTotaux(array $nouveauxBaremes, ?int $critereIdExclure = null): void
    {
        $totauxActuels = $this->critereModel->getTotauxParAnnee($critereIdExclure);
        $totauxParAnnee = [];
        foreach ($totauxActuels as $t) {
            $totauxParAnnee[$t['id_annee_acad']] = [
                'total' => (float) $t['total_actuel'],
                'lib_annee' => $t['lib_annee']
            ];
        }

        foreach ($nouveauxBaremes as $b) {
            if (!empty($b['annee_id']) && !empty($b['bareme'])) {
                $aid = $b['annee_id'];
                if (!isset($totauxParAnnee[$aid])) {
                    $totauxParAnnee[$aid] = ['total' => 0, 'lib_annee' => $aid];
                }
                $totauxParAnnee[$aid]['total'] += (float)$b['bareme'];
            }
        }

        foreach ($totauxParAnnee as $data) {
            if ($data['total'] > 20) {
                throw new Exception("Le total des points pour l'année {$data['lib_annee']} dépasse 20 points ({$data['total']}).");
            }
        }
    }
}

?>