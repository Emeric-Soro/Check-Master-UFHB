<?php

/**
 * PaginationHelper - Classe utilitaire pour la gestion de la pagination
 */
class PaginationHelper {
    
    /**
     * Calcule les paramètres de pagination
     * 
     * @param int $currentPage Page actuelle
     * @param int $totalItems Nombre total d'éléments
     * @param int $itemsPerPage Nombre d'éléments par page
     * @return array Paramètres de pagination
     */
    public static function calculate($currentPage, $totalItems, $itemsPerPage = 10) {
        $currentPage = max(1, (int)$currentPage);
        $itemsPerPage = max(1, (int)$itemsPerPage);
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        // Valider la page courante
        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }
        
        $offset = ($currentPage - 1) * $itemsPerPage;
        $startIndex = $totalItems > 0 ? $offset + 1 : 0;
        $endIndex = min($offset + $itemsPerPage, $totalItems);
        
        return [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage,
            'offset' => $offset,
            'limit' => $itemsPerPage,
            'startIndex' => $startIndex,
            'endIndex' => $endIndex,
            'hasPrevious' => $currentPage > 1,
            'hasNext' => $currentPage < $totalPages
        ];
    }
    
    /**
     * Génère les numéros de pages à afficher
     * 
     * @param int $currentPage Page actuelle
     * @param int $totalPages Nombre total de pages
     * @param int $range Nombre de pages à afficher de chaque côté
     * @return array Liste des numéros de pages
     */
    public static function getPageNumbers($currentPage, $totalPages, $range = 2) {
        $pages = [];
        
        // Toujours afficher la première page
        $pages[] = 1;
        
        // Calculer la plage autour de la page actuelle
        $start = max(2, $currentPage - $range);
        $end = min($totalPages - 1, $currentPage + $range);
        
        // Ajouter "..." si nécessaire avant la plage
        if ($start > 2) {
            $pages[] = '...';
        }
        
        // Ajouter les pages de la plage
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }
        
        // Ajouter "..." si nécessaire après la plage
        if ($end < $totalPages - 1) {
            $pages[] = '...';
        }
        
        // Toujours afficher la dernière page (si > 1)
        if ($totalPages > 1) {
            $pages[] = $totalPages;
        }
        
        return $pages;
    }
    
    /**
     * Construit l'URL pour une page donnée
     * 
     * @param int $page Numéro de page
     * @param array $params Paramètres GET supplémentaires
     * @return string URL
     */
    public static function buildUrl($page, $params = []) {
        $params['p'] = $page;
        
        $queryString = http_build_query($params);
        $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
        
        return $currentUrl . '?' . $queryString;
    }
}
