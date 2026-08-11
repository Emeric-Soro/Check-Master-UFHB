<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Salle.php';

use Salle;
use Database;
use App\Support\DatabaseService;
use PDO;
use Exception;

/**
 * Service métier pour la gestion des salles.
 *
 * Contient toute la logique métier extraite de GestionSallesController :
 * - Ajout / modification d'une salle (avec validation et dédoublonnage)
 * - Suppression multiple avec vérification d'usage dans les programmations
 * - Recherche et pagination (via DatabaseService)
 * - Récupération d'une salle par ID
 */
class GestionSallesService
{
    private $db;
    private DatabaseService $dbService;
    private $salleModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->dbService = new DatabaseService($db);
        $this->salleModel = new Salle($this->db);
    }

    private function tableExists($tableName)
    {
        try {
            return (int) $this->dbService->scalar(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t',
                [':t' => $tableName],
                0
            ) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getProgrammationTable()
    {
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
        }
        if ($this->tableExists('programmer')) {
            return 'programmer';
        }
        return null;
    }

    // ──────────────────────────────────────────────────────────────
    //  Ajout / Modification
    // ──────────────────────────────────────────────────────────────

    /**
     * Gérer l'ajout ou la modification d'une salle.
     *
     * @param array $postData Données du formulaire
     * @return array ['success' => bool, 'message' => string]
     */
    public function ajouterOuModifierSalle(array $postData): array
    {
        try {
            $lib_salle = trim($postData['lib_salle'] ?? '');

            if (empty($lib_salle)) {
                return ['success' => false, 'message' => "Le nom de la salle est requis."];
            }

            // Modification
            if (!empty($postData['id_salle'])) {
                $result = $this->salleModel->modifierSalle(
                    $postData['id_salle'],
                    $lib_salle
                );

                if ($result) {
                    return ['success' => true, 'message' => "Salle modifiée avec succès."];
                } else {
                    return ['success' => false, 'message' => "Erreur lors de la modification de la salle."];
                }
            } else {
                // Ajout - Vérifier si la salle existe déjà
                $salleExistante = $this->salleModel->getSalleByName($lib_salle);
                if ($salleExistante) {
                    return ['success' => false, 'message' => "Une salle avec ce nom existe déjà."];
                }

                $result = $this->salleModel->creerSalle($lib_salle);
                if ($result) {
                    return ['success' => true, 'message' => "Salle ajoutée avec succès."];
                } else {
                    return ['success' => false, 'message' => "Erreur lors de l'ajout de la salle."];
                }
            }
        } catch (Exception $e) {
            error_log('Erreur ajouterOuModifierSalle: ' . $e->getMessage());
            return ['success' => false, 'message' => "Une erreur est survenue lors de l'opération."];
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Suppression
    // ──────────────────────────────────────────────────────────────

    /**
     * Supprimer plusieurs salles.
     *
     * @param array $selectedIds IDs des salles à supprimer
     * @return array ['success' => bool, 'message' => string]
     */
    public function supprimerSallesMultiples(array $selectedIds): array
    {
        try {
            if (empty($selectedIds)) {
                return ['success' => false, 'message' => "Aucune salle sélectionnée."];
            }

            $success = true;

            foreach ($selectedIds as $id) {
                // Vérifier si la salle est utilisée dans des programmations
                $progTable = $this->getProgrammationTable();
                if ($progTable !== null) {
                    $usage = (int) $this->dbService->scalar(
                        "SELECT COUNT(*) FROM {$this->dbService->table($progTable)} WHERE id_salle = :id",
                        [':id' => $id],
                        0
                    );
                    if ($usage > 0) {
                        return ['success' => false, 'message' => "Une ou plusieurs salles sont utilisées dans des programmations et ne peuvent pas être supprimées."];
                    }
                }

                $result = $this->salleModel->supprimerSalle($id);
                if (!$result) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                return ['success' => true, 'message' => "Salles supprimées avec succès."];
            } else {
                return ['success' => false, 'message' => "Erreur lors de la suppression des salles."];
            }
        } catch (Exception $e) {
            error_log('Erreur supprimerSallesMultiples: ' . $e->getMessage());
            return ['success' => false, 'message' => "Une erreur est survenue lors de la suppression."];
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Lecture
    // ──────────────────────────────────────────────────────────────

    /**
     * Récupérer une salle pour modification.
     *
     * @param int $idSalle ID de la salle
     * @return object|null Objet salle ou null
     */
    public function getSallePourModification($idSalle)
    {
        return $this->salleModel->getSalleById($idSalle);
    }

    /**
     * Récupérer toutes les salles avec recherche et pagination.
     *
     * @param string $search Terme de recherche
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array ['data' => array, 'totalPages' => int, 'totalItems' => int]
     */
    public function getSallesAvecPagination(string $search = '', int $page = 1, int $limit = 10): array
    {
        try {
            $page = max(1, $page);
            $limit = max(1, min(100, $limit));
            $offset = ($page - 1) * $limit;

            $where = '';
            $params = [];
            if ($search !== '') {
                $where = ' WHERE lib_salle LIKE :search';
                $params[':search'] = '%' . $search . '%';
            }

            $total = (int) $this->dbService->scalar('SELECT COUNT(*) FROM salles' . $where, $params, 0);
            $listeSalles = $this->dbService->select(
                'SELECT * FROM salles' . $where . ' ORDER BY lib_salle ASC LIMIT ' . $limit . ' OFFSET ' . $offset,
                $params
            );

            return [
                'data' => $listeSalles,
                'totalPages' => (int) ceil($total / $limit),
                'totalItems' => $total
            ];
        } catch (Exception $e) {
            error_log('Erreur getSallesAvecPagination: ' . $e->getMessage());
            return [
                'data' => [],
                'totalPages' => 0,
                'totalItems' => 0
            ];
        }
    }
}
