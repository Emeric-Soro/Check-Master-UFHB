<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionSallesService.php';

use CheckMaster\Services\GestionSallesService;

class GestionSallesController
{
    private $service;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new GestionSallesService($db);
    }

    /**
     * Gérer l'ajout ou la modification d'une salle
     * 
     * @param array $postData Données du formulaire
     * @return array ['success' => bool, 'message' => string]
     */
    public function ajouterOuModifierSalle($postData)
    {
        return $this->service->ajouterOuModifierSalle($postData);
    }

    /**
     * Supprimer plusieurs salles
     * 
     * @param array $selectedIds IDs des salles à supprimer
     * @return array ['success' => bool, 'message' => string]
     */
    public function supprimerSallesMultiples($selectedIds)
    {
        return $this->service->supprimerSallesMultiples($selectedIds);
    }

    /**
     * Récupérer une salle pour modification
     * 
     * @param int $idSalle ID de la salle
     * @return object|null Objet salle ou null
     */
    public function getSallePourModification($idSalle)
    {
        return $this->service->getSallePourModification($idSalle);
    }

    /**
     * Récupérer toutes les salles avec recherche et pagination
     * 
     * @param string $search Terme de recherche
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array ['data' => array, 'totalPages' => int, 'totalItems' => int]
     */
    public function getSallesAvecPagination($search = '', $page = 1, $limit = 10)
    {
        return $this->service->getSallesAvecPagination($search, $page, $limit);
    }
}
