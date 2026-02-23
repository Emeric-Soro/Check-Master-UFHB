<?php

namespace CheckMaster\Services;

require_once __DIR__ . '/../models/Traitement.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Fonctionnalite.php';
require_once __DIR__ . '/../models/Permission.php';

use Traitement;
use Categorie;
use Fonctionnalite;
use Permission;

class MenuService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Générer le menu hiérarchique avec catégories et fonctionnalités
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Structure hiérarchique [categorie => [fonctionnalites]]
     */
    public function genererMenuHierarchique($idGroupe)
    {
        $categorieModel = new Categorie($this->db);
        $fonctionnaliteModel = new Fonctionnalite($this->db);

        // Récupérer les catégories accessibles par ce groupe
        $categories = $categorieModel->getCategoriesForGroupe($idGroupe);

        $menuHierarchique = [];

        foreach ($categories as $categorie) {
            // Récupérer les fonctionnalités de cette catégorie accessibles par le groupe
            $fonctionnalites = $fonctionnaliteModel->getFonctionnalitesForGroupeAndCategorie($idGroupe, $categorie->id_categorie);

            // Construire un 2e niveau "sous-menus" via (est_sous_page, page_parente)
            $parentsByCode = [];
            $childrenByParent = [];
            $orphans = [];

            foreach ($fonctionnalites as $f) {
                $isSousPage = !empty($f->est_sous_page);
                $parentCode = isset($f->page_parente) ? (string) $f->page_parente : '';

                // Sous-page sans parent = écran "action" (pas dans le menu)
                if ($isSousPage && $parentCode === '') {
                    continue;
                }

                if ($isSousPage && $parentCode !== '') {
                    if (!isset($childrenByParent[$parentCode])) {
                        $childrenByParent[$parentCode] = [];
                    }
                    $childrenByParent[$parentCode][] = $f;
                    continue;
                }

                if (!$isSousPage && !empty($f->code_fonctionnalite)) {
                    $parentsByCode[(string)$f->code_fonctionnalite] = $f;
                } else {
                    $orphans[] = $f;
                }
            }

            // Attacher les enfants aux parents
            foreach ($parentsByCode as $code => $parent) {
                $children = $childrenByParent[$code] ?? [];
                usort($children, function ($a, $b) {
                    return ((int)($a->ordre_fonctionnalite ?? 0)) <=> ((int)($b->ordre_fonctionnalite ?? 0));
                });
                $parent->children = array_values($children);
            }

            // Si des enfants existent sans parent visible, créer un parent "virtuel"
            foreach ($childrenByParent as $pcode => $children) {
                if (isset($parentsByCode[$pcode])) {
                    continue;
                }
                if (empty($children)) {
                    continue;
                }
                usort($children, function ($a, $b) {
                    return ((int)($a->ordre_fonctionnalite ?? 0)) <=> ((int)($b->ordre_fonctionnalite ?? 0));
                });
                $first = $children[0];
                $hub = new \stdClass();
                $hub->id_fonctionnalite = 0;
                $hub->id_categorie = $categorie->id_categorie;
                $hub->code_fonctionnalite = (string) $pcode;
                $hub->lib_fonctionnalite = (string) $pcode;
                $hub->label_fonctionnalite = (string) $pcode;
                $hub->url_fonctionnalite = (string)($first->url_fonctionnalite ?? '#');
                $hub->icone_fonctionnalite = 'fa-folder';
                $hub->ordre_fonctionnalite = (int)($first->ordre_fonctionnalite ?? 0);
                $hub->est_sous_page = 0;
                $hub->page_parente = null;
                $hub->children = array_values($children);
                $hub->is_virtual = true;
                $parentsByCode[$pcode] = $hub;
            }

            $fonctionnalitesPrincipales = array_values($parentsByCode);
            usort($fonctionnalitesPrincipales, function ($a, $b) {
                return ((int)($a->ordre_fonctionnalite ?? 0)) <=> ((int)($b->ordre_fonctionnalite ?? 0));
            });
            // Ajouter aussi les orphelins (en fin)
            foreach ($orphans as $o) {
                $o->children = [];
                $fonctionnalitesPrincipales[] = $o;
            }

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
        $fonctionnaliteModel = new Fonctionnalite($this->db);

        return $fonctionnaliteModel->getFonctionnalitesForGroupe($idGroupe);
    }

    /**
     * Ancien générateur de menu (compatibilité rétroactive)
     * @deprecated Utiliser genererMenuHierarchique() à la place
     */
    public function genererMenu($idGroupe)
    {
        $traitement = new Traitement($this->db);
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
        $permissionModel = new Permission($this->db);

        return $permissionModel->checkPermissionByCode($idGroupe, $codeFonctionnalite, $typePermission);
    }

    /**
     * Récupérer toutes les permissions d'un groupe
     * @param int $idGroupe - ID du groupe utilisateur
     * @return array - Liste des permissions
     */
    public function getPermissionsGroupe($idGroupe)
    {
        $permissionModel = new Permission($this->db);

        return $permissionModel->getAllPermissionsForGroupe($idGroupe);
    }
}