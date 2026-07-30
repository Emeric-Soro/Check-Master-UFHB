<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CommissionValidationMembre.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class CommissionValidationMembresController
{
    private CommissionValidationMembre $model;

    public function __construct()
    {
        $this->model = new CommissionValidationMembre(Database::getConnection());
    }

    public function index(): array
    {
        $idUtilisateur = (int) ($_SESSION['id_utilisateur'] ?? 0);
        if (!$this->model->peutGerer($idUtilisateur)) {
            http_response_code(403);
            return ['forbidden' => true, 'membres' => []];
        }
        return [
            'forbidden' => false,
            'membres' => $this->model->getMembres(),
            'nombre_actifs' => $this->model->getNombreActifs(),
        ];
    }

    public function sauvegarder(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $idUtilisateur = (int) ($_SESSION['id_utilisateur'] ?? 0);
        if (!$this->model->peutGerer($idUtilisateur)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Vous n'avez pas le droit de gérer les membres votants."]);
            return;
        }

        $ids = $_POST['membres_actifs'] ?? [];
        if (!is_array($ids)) $ids = [$ids];
        try {
            $this->model->synchroniserSelection($ids, $idUtilisateur);
            echo json_encode(['success' => true, 'message' => 'La composition des membres votants a été enregistrée.']);
        } catch (Throwable $e) {
            error_log('CommissionValidationMembresController::sauvegarder: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Impossible d’enregistrer la composition.']);
        }
    }
}
