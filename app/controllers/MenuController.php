<?php

namespace App\Controllers;

use PDO;
use App\Models\Traitement;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;

/**
 * MenuController - Génération du menu dynamique latéral
 * 
 * Ce contrôleur génère le menu de navigation en fonction des droits
 * de l'utilisateur connecté. Il prend en charge les menus hiérarchiques
 * (parents/enfants) avec vérification des permissions.
 * 
 * @package App\Controllers
 */
class MenuController
{
    private PDO $pdo;
    private ?Traitement $traitement = null;
    private ?SecurityUtils $security = null;
    private ?LoggerInterface $logger = null;

    /**
     * Constructeur avec injection de dépendances
     * 
     * @param PDO $pdo Connexion à la base de données
     * @param Traitement|null $traitement Service de gestion des traitements
     * @param SecurityUtils|null $security Service de sécurité
     * @param LoggerInterface|null $logger Service de logging
     */
    public function __construct(
        PDO $pdo,
        ?Traitement $traitement = null,
        ?SecurityUtils $security = null,
        ?LoggerInterface $logger = null
    ) {
        $this->pdo = $pdo;
        $this->traitement = $traitement;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Récupère les éléments de menu pour un groupe d'utilisateurs
     * 
     * @param int $idGroupe ID du groupe utilisateur
     * @return array Menu structuré avec parents et enfants
     */
    public function getMenuForGroup(int $idGroupe): array
    {
        try {
            // Récupérer tous les traitements parents (ceux qui ont parent_id = NULL)
            $parents = $this->getParentMenuItems($idGroupe);
            
            $menu = [];
            foreach ($parents as $parent) {
                // Récupérer les enfants de ce parent
                $children = $this->getChildMenuItems($parent['id_traitement'], $idGroupe);
                
                // N'ajouter le parent que s'il a des enfants accessibles
                if (!empty($children) || $this->hasDirectAccess($parent['id_traitement'], $idGroupe)) {
                    $menu[] = [
                        'id' => $parent['id_traitement'],
                        'lib' => $parent['lib_traitement'],
                        'label' => $parent['label_traitement'],
                        'icone' => $parent['icone_traitement'] ?? 'fas fa-folder',
                        'ordre' => $parent['ordre_traitement'],
                        'children' => $children
                    ];
                }
            }
            
            return $menu;
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Erreur génération menu: " . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Récupère les éléments de menu parents (sans parent_id)
     * 
     * @param int $idGroupe ID du groupe utilisateur
     * @return array Liste des menus parents
     */
    private function getParentMenuItems(int $idGroupe): array
    {
        $sql = "SELECT DISTINCT t.* 
                FROM traitement t
                WHERE t.parent_id IS NULL 
                ORDER BY t.ordre_traitement ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les éléments de menu enfants pour un parent donné
     * 
     * @param int $parentId ID du traitement parent
     * @param int $idGroupe ID du groupe utilisateur
     * @return array Liste des menus enfants accessibles
     */
    private function getChildMenuItems(int $parentId, int $idGroupe): array
    {
        $sql = "SELECT t.* 
                FROM traitement t
                INNER JOIN droits d ON t.id_traitement = d.id_traitement
                WHERE t.parent_id = :parentId 
                AND d.id_GU = :idGroupe 
                AND d.can_read = 1
                ORDER BY t.ordre_traitement ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':parentId' => $parentId,
            ':idGroupe' => $idGroupe
        ]);
        
        $children = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $child) {
            $children[] = [
                'id' => $child['id_traitement'],
                'lib' => $child['lib_traitement'],
                'label' => $child['label_traitement'],
                'icone' => $child['icone_traitement'] ?? 'fas fa-file',
                'ordre' => $child['ordre_traitement']
            ];
        }
        
        return $children;
    }

    /**
     * Vérifie si un élément de menu a un accès direct (pour les parents cliquables)
     * 
     * @param int $traitementId ID du traitement
     * @param int $idGroupe ID du groupe utilisateur
     * @return bool True si accès direct autorisé
     */
    private function hasDirectAccess(int $traitementId, int $idGroupe): bool
    {
        $sql = "SELECT d.can_read 
                FROM droits d
                WHERE d.id_traitement = :traitementId 
                AND d.id_GU = :idGroupe 
                AND d.can_read = 1
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':traitementId' => $traitementId,
            ':idGroupe' => $idGroupe
        ]);
        
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Génère le HTML du menu pour l'affichage
     * 
     * @param int $idGroupe ID du groupe utilisateur
     * @param string|null $currentPage Page actuelle (pour highlight)
     * @return string HTML du menu
     */
    public function renderMenu(int $idGroupe, ?string $currentPage = null): string
    {
        $menu = $this->getMenuForGroup($idGroupe);
        $html = '<ul class="sidebar-menu">';
        
        foreach ($menu as $item) {
            $hasChildren = !empty($item['children']);
            $isActive = $currentPage === $item['lib'];
            $activeClass = $isActive ? 'active' : '';
            
            if ($hasChildren) {
                // Menu avec sous-menu
                $html .= '<li class="has-submenu ' . $activeClass . '">';
                $html .= '<a href="#" class="menu-toggle">';
                $html .= '<i class="' . htmlspecialchars($item['icone']) . '"></i>';
                $html .= '<span>' . htmlspecialchars($item['label']) . '</span>';
                $html .= '<i class="fas fa-chevron-down submenu-arrow"></i>';
                $html .= '</a>';
                $html .= '<ul class="submenu">';
                
                foreach ($item['children'] as $child) {
                    $childActive = $currentPage === $child['lib'] ? 'active' : '';
                    $html .= '<li class="' . $childActive . '">';
                    $html .= '<a href="?page=' . htmlspecialchars($child['lib']) . '">';
                    $html .= '<i class="' . htmlspecialchars($child['icone']) . '"></i>';
                    $html .= '<span>' . htmlspecialchars($child['label']) . '</span>';
                    $html .= '</a></li>';
                }
                
                $html .= '</ul></li>';
            } else {
                // Menu simple (sans enfants)
                $html .= '<li class="' . $activeClass . '">';
                $html .= '<a href="?page=' . htmlspecialchars($item['lib']) . '">';
                $html .= '<i class="' . htmlspecialchars($item['icone']) . '"></i>';
                $html .= '<span>' . htmlspecialchars($item['label']) . '</span>';
                $html .= '</a></li>';
            }
        }
        
        $html .= '</ul>';
        return $html;
    }

    /**
     * Méthode legacy pour compatibilité avec l'ancien système
     * Récupère les traitements d'un groupe via la table 'rattacher'
     * 
     * @param int $idGroupe ID du groupe utilisateur
     * @return array Liste des traitements
     */
    public function getTraitementsLegacy(int $idGroupe): array
    {
        try {
            $sql = "SELECT t.* 
                    FROM traitement t 
                    INNER JOIN rattacher r ON t.id_traitement = r.id_traitement 
                    WHERE r.id_GU = :idGroupe 
                    ORDER BY t.ordre_traitement ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':idGroupe' => $idGroupe]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Erreur getTraitementsLegacy: " . $e->getMessage());
            }
            return [];
        }
    }
}
