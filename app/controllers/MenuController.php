<?php

require_once __DIR__ . '/../models/Traitement.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Fonctionnalite.php';
require_once __DIR__ . '/../models/Permission.php';

class MenuController
{

    /**
     * Générer le menu hiérarchique avec catégories et fonctionnalités
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Structure hiérarchique [categorie => [fonctionnalites]]
     */
    public function genererMenuHierarchique($idGroupe)
    {
        $pdo = Database::getConnection();

        $categorieModel = new Categorie($pdo);
        $fonctionnaliteModel = new Fonctionnalite($pdo);

        // Récupérer les catégories accessibles par ce groupe
        $categories = $categorieModel->getCategoriesForGroupe($idGroupe);

        $menuHierarchique = [];

        foreach ($categories as $categorie) {
            // Récupérer les fonctionnalités de cette catégorie accessibles par le groupe
            $fonctionnalites = $fonctionnaliteModel->getFonctionnalitesForGroupeAndCategorie($idGroupe, $categorie->id_categorie);

            // Ne garder que les pages principales (pas les sous-pages pour le menu)
            $fonctionnalitesPrincipales = array_filter($fonctionnalites, function ($f) {
                return !$f->est_sous_page;
            });

            // Ajouter la catégorie seulement si elle a des fonctionnalités visibles
            if (!empty($fonctionnalitesPrincipales)) {
                $menuHierarchique[] = [
                    'categorie' => $categorie,
                    'fonctionnalites' => array_values($fonctionnalitesPrincipales)
                ];
            }
        }

        return $menuHierarchique;
    }

    /**
     * Générer le menu à plat (pour compatibilité avec ancien système)
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Liste plate de fonctionnalités
     */
    public function genererMenuPlat($idGroupe)
    {
        $pdo = Database::getConnection();
        $fonctionnaliteModel = new Fonctionnalite($pdo);

        return $fonctionnaliteModel->getFonctionnalitesForGroupe($idGroupe);
    }

    /**
     * Ancien générateur de menu (compatibilité rétroactive)
     * @deprecated Utiliser genererMenuHierarchique() à la place
     */
    public function genererMenu($idGroupe)
    {
        $traitement = new Traitement(Database::getConnection());
        return $traitement->getTraitementByGU($idGroupe);
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
        $pdo = Database::getConnection();
        $permissionModel = new Permission($pdo);

        return $permissionModel->checkPermissionByCode($idGroupe, $codeFonctionnalite, $typePermission);
    }

    /**
     * Récupérer toutes les permissions d'un groupe
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Liste des permissions
     */
    public function getPermissionsGroupe($idGroupe)
    {
        $pdo = Database::getConnection();
        $permissionModel = new Permission($pdo);

        return $permissionModel->getAllPermissionsForGroupe($idGroupe);
    }
}