<?php

require_once __DIR__ . '/../Services/ProgrammationSoutenanceService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\ProgrammationSoutenanceService;

class ProgrammationSoutenanceController
{
    private $service;

    public function __construct()
    {
        $this->service = new ProgrammationSoutenanceService();
    }

    /**
     * Récupérer tous les étudiants avec rapport validé (pour affichage et modification)
     */
    public function getEtudiantsForView()
    {
        return $this->service->getEtudiantsForView();
    }

    /**
     * Récupérer les étudiants disponibles pour nouvelle programmation (non encore programmés)
     */
    public function getEtudiantsDisponiblesForView()
    {
        return $this->service->getEtudiantsDisponiblesForView();
    }

    /**
     * Récupérer tous les enseignants pour PHP (sans header JSON)
     */
    public function getEnseignantsForView()
    {
        return $this->service->getEnseignants();
    }

    /**
     * Récupérer toutes les salles pour PHP (sans header JSON)
     */
    public function getSallesForView()
    {
        return $this->service->getSalles();
    }

    /**
     * Récupérer les professeurs titulaires pour PHP (sans header JSON)
     */
    public function getProfesseursTitulairesForView()
    {
        return $this->service->getProfesseursTitulaires();
    }

    /**
     * Récupérer toutes les attributions pour PHP (sans header JSON)
     */
    public function getAttributionsForView()
    {
        return $this->service->getAttributions();
    }

    /**
     * Récupérer tous les étudiants disponibles (avec rapports validés par la commission)
     */
    public function getEtudiants()
    {
        try {
            $etudiants = $this->service->getEtudiantsValides();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $etudiants
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des étudiants : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer tous les enseignants disponibles
     */
    public function getEnseignants()
    {
        try {
            $enseignants = $this->service->getEnseignants();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $enseignants
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des enseignants : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer seulement les professeurs titulaires pour le poste de président
     */
    public function getProfesseursTitulaires()
    {
        try {
            $professeurs = $this->service->getProfesseursTitulaires();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $professeurs
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des professeurs titulaires : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer toutes les salles disponibles
     */
    public function getSalles()
    {
        try {
            $salles = $this->service->getSalles();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $salles
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des salles : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer toutes les attributions de jury (utilise composer_jury)
     */
    public function getAttributions()
    {
        try {
            $attributions = $this->service->getAttributions();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $attributions
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des attributions : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Créer une nouvelle attribution de jury
     */
    public function createAttribution()
    {
        try {
            if (!canCreate()) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                    exit;
                }
                $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                $_SESSION['error_type'] = 'permission_denied';
                header('Location: layout.php?page=access_denied');
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input)) {
                $input = $_POST;
            }
            $result = $this->service->createAttribution($input);

            header('Content-Type: application/json');
            echo json_encode($result);
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
     * Mettre à jour une attribution de jury
     */
    public function updateAttribution()
    {
        try {
            if (!canEdit()) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                    exit;
                }
                $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                $_SESSION['error_type'] = 'permission_denied';
                header('Location: layout.php?page=access_denied');
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input)) {
                $input = $_POST;
            }
            $result = $this->service->updateAttribution($input);

            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Supprimer une attribution de jury
     */
    public function deleteAttribution()
    {
        try {
            if (!canDelete()) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                    exit;
                }
                $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                $_SESSION['error_type'] = 'permission_denied';
                header('Location: layout.php?page=access_denied');
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input)) {
                $input = $_POST;
            }
            $result = $this->service->deleteAttribution($input['id'] ?? null);

            header('Content-Type: application/json');
            echo json_encode($result);
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
