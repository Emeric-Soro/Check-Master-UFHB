<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Salle.php';

use Salle;
use Database;
use PDO;
use Exception;

/**
 * Service métier pour la gestion des salles.
 *
 * Contient toute la logique métier extraite de GestionSallesController :
 * - Ajout / modification d'une salle (avec validation et dédoublonnage)
 * - Suppression multiple avec vérification d'usage dans les programmations
 * - Recherche et pagination
 * - Récupération d'une salle par ID
 */
class GestionSallesService
{
    private $db;
    private $salleModel;
    private $tableExistsCache = [];

    public function __construct($db)
    {
        $this->db = $db;
        $this->salleModel = new Salle($this->db);
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function getProgrammationTable()
    {
        if ($this->tableExists('programmer')) {
            return 'programmer';
        }
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
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
                    $usageStmt = $this->db->prepare("SELECT COUNT(*) FROM {$progTable} WHERE id_salle = ?");
                    $usageStmt->execute([$id]);
                    if ($usageStmt->fetchColumn() > 0) {
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
            // Récupération des salles avec recherche
            if (!empty($search)) {
                $stmt = $this->db->prepare("SELECT * FROM salles WHERE lib_salle LIKE ? ORDER BY lib_salle ASC");
                $stmt->execute(['%' . $search . '%']);
                $listeSalles = $stmt->fetchAll(PDO::FETCH_OBJ);
            } else {
                $stmt = $this->db->query("SELECT * FROM salles ORDER BY lib_salle ASC");
                $listeSalles = $stmt->fetchAll(PDO::FETCH_OBJ);
            }

            // Pagination
            $total_items = count($listeSalles);
            $total_pages = ceil($total_items / $limit);
            $offset = ($page - 1) * $limit;
            $listeSalles = array_slice($listeSalles, $offset, $limit);

            return [
                'data' => $listeSalles,
                'totalPages' => $total_pages,
                'totalItems' => $total_items
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
