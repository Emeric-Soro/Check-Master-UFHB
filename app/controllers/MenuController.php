<?php

require_once __DIR__ . '/../Services/MenuService.php';

use CheckMaster\Services\MenuService;

class MenuController
{
    private $service;

    public function __construct()
    {
        $this->service = new MenuService(Database::getConnection());
    }

    /**
     * Générer le menu hiérarchique avec catégories et fonctionnalités
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Structure hiérarchique [categorie => [fonctionnalites]]
     */
    public function genererMenuHierarchique($idGroupe)
    {
        return $this->service->genererMenuHierarchique($idGroupe);
    }

    /**
     * Générer le menu à plat (pour compatibilité avec ancien système)
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Liste plate de fonctionnalités
     */
    public function genererMenuPlat($idGroupe)
    {
        return $this->service->genererMenuPlat($idGroupe);
    }

    /**
     * Ancien générateur de menu (compatibilité rétroactive)
     * @deprecated Utiliser genererMenuHierarchique() à la place
     */
    public function genererMenu($idGroupe)
    {
        return $this->service->genererMenu($idGroupe);
    }

    /**
     * Vérifier si un utilisateur a accès à une fonctionnalité
     * @param int $idGroupe - ID du groupe utilisateur
     * @param string $codeFonctionnalite - Code de la fonctionnalité
     * @param string $typePermission - Type de permission (peut_voir, peut_creer, peut_modifier, peut_supprimer)
     * @return bool
     */
    public function verifierAcces($idGroupe, $codeFonctionnalite, $typePermission = 'peut_voir')
    {
        return $this->service->verifierAcces($idGroupe, $codeFonctionnalite, $typePermission);
    }

    /**
     * Récupérer toutes les permissions d'un groupe
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Liste des permissions
     */
    public function getPermissionsGroupe($idGroupe)
    {
        return $this->service->getPermissionsGroupe($idGroupe);
    }
}
