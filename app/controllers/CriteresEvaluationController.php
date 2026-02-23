<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/CriteresEvaluationService.php';

use CheckMaster\Services\CriteresEvaluationService;

class CriteresEvaluationController
{
    private $service;

    public function __construct()
    {
        $this->service = new CriteresEvaluationService(Database::getConnection());
    }

    /**
     * Afficher la page principale (pas d'action spécifique)
     */
    public function index()
    {
        // La vue sera incluse par le layout principal
        // Pas de logique particulière nécessaire ici
    }

    /**
     * Récupérer toutes les années académiques
     */
    public function getAnneesAcademiques()
    {
        try {
            $annees = $this->service->getAnneesAcademiques();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $annees
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des années académiques : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer tous les critères d'évaluation avec leurs barèmes
     */
    public function getCriteres()
    {
        try {
            $criteresArray = $this->service->getCriteres();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $criteresArray
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des critères : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Créer un nouveau critère d'évaluation
     */
    public function createCritere()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Debug : log des données reçues
            error_log("Données reçues pour création critère: " . print_r($input, true));

            $critereId = $this->service->createCritere($input);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Critère créé avec succès',
                'data' => ['id' => $critereId]
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mettre à jour un critère d'évaluation
     */
    public function updateCritere()
    {
        try {
            // Lire les données depuis POST ou JSON selon le Content-Type
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                $input = $_POST;
            }

            $this->service->updateCritere($input);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Critère modifié avec succès'
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la modification : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Supprimer un critère d'évaluation
     */
    public function deleteCritere()
    {
        try {
            // Lire les données depuis POST ou JSON selon le Content-Type
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                $input = $_POST;
            }

            $this->service->deleteCritere($input);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Critère supprimé avec succès'
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ]);
        }
    }
}
?>
