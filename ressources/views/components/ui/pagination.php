<?php
/**
 * CheckMaster Premium - Pagination Component
 * 
 * Composant de pagination réutilisable.
 */

/**
 * Render pagination
 * 
 * @param int $currentPage - Page actuelle
 * @param int $totalPages - Nombre total de pages
 * @param string $baseUrl - URL de base avec placeholder {page}
 * @param int $visiblePages - Nombre de pages visibles autour de la page actuelle
 */
function renderPagination(
    int $currentPage, 
    int $totalPages, 
    string $baseUrl = '?page={page}', 
    int $visiblePages = 2
): string {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination">';
    
    // Previous button
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('{page}', (string)($currentPage - 1), $baseUrl);
    $html .= sprintf(
        '<a href="%s" class="pagination-item%s" title="Précédent">
            <i class="fas fa-chevron-left"></i>
        </a>',
        $currentPage > 1 ? htmlspecialchars($prevUrl) : '#',
        $prevDisabled
    );
    
    // First page
    if ($currentPage > $visiblePages + 1) {
        $firstUrl = str_replace('{page}', '1', $baseUrl);
        $html .= sprintf(
            '<a href="%s" class="pagination-item">1</a>',
            htmlspecialchars($firstUrl)
        );
        
        if ($currentPage > $visiblePages + 2) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
    }
    
    // Visible pages
    $start = max(1, $currentPage - $visiblePages);
    $end = min($totalPages, $currentPage + $visiblePages);
    
    for ($i = $start; $i <= $end; $i++) {
        $isActive = $i === $currentPage ? ' active' : '';
        $pageUrl = str_replace('{page}', (string)$i, $baseUrl);
        
        if ($i === $currentPage) {
            $html .= sprintf(
                '<span class="pagination-item active">%d</span>',
                $i
            );
        } else {
            $html .= sprintf(
                '<a href="%s" class="pagination-item">%d</a>',
                htmlspecialchars($pageUrl),
                $i
            );
        }
    }
    
    // Last page
    if ($currentPage < $totalPages - $visiblePages) {
        if ($currentPage < $totalPages - $visiblePages - 1) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
        
        $lastUrl = str_replace('{page}', (string)$totalPages, $baseUrl);
        $html .= sprintf(
            '<a href="%s" class="pagination-item">%d</a>',
            htmlspecialchars($lastUrl),
            $totalPages
        );
    }
    
    // Next button
    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $nextUrl = str_replace('{page}', (string)($currentPage + 1), $baseUrl);
    $html .= sprintf(
        '<a href="%s" class="pagination-item%s" title="Suivant">
            <i class="fas fa-chevron-right"></i>
        </a>',
        $currentPage < $totalPages ? htmlspecialchars($nextUrl) : '#',
        $nextDisabled
    );
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Pagination avec informations
 */
function renderPaginationInfo(
    int $currentPage,
    int $totalPages,
    int $totalItems,
    int $perPage,
    string $baseUrl = '?page={page}'
): string {
    $start = (($currentPage - 1) * $perPage) + 1;
    $end = min($currentPage * $perPage, $totalItems);
    
    $html = '<div class="flex items-center justify-between mt-md">';
    
    // Info
    $html .= sprintf(
        '<p class="text-sm text-muted">Affichage de %d à %d sur %d résultats</p>',
        $start,
        $end,
        $totalItems
    );
    
    // Pagination
    $html .= renderPagination($currentPage, $totalPages, $baseUrl);
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Simple pagination (Previous/Next only)
 */
function renderSimplePagination(int $currentPage, int $totalPages, string $baseUrl = '?page={page}'): string {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="flex items-center justify-center gap-md mt-md">';
    
    // Previous
    if ($currentPage > 1) {
        $prevUrl = str_replace('{page}', (string)($currentPage - 1), $baseUrl);
        $html .= sprintf(
            '<a href="%s" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Précédent
            </a>',
            htmlspecialchars($prevUrl)
        );
    }
    
    // Current info
    $html .= sprintf(
        '<span class="text-muted">Page %d sur %d</span>',
        $currentPage,
        $totalPages
    );
    
    // Next
    if ($currentPage < $totalPages) {
        $nextUrl = str_replace('{page}', (string)($currentPage + 1), $baseUrl);
        $html .= sprintf(
            '<a href="%s" class="btn btn-outline">
                Suivant <i class="fas fa-arrow-right"></i>
            </a>',
            htmlspecialchars($nextUrl)
        );
    }
    
    $html .= '</div>';
    
    return $html;
}
