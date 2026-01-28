<?php
/**
 * CheckMaster Premium - Breadcrumb Component
 * 
 * Fil d'Ariane pour la navigation.
 */

/**
 * Render a breadcrumb navigation
 * 
 * @param array $items - Array of breadcrumb items
 *   Can be simple strings: ['Accueil', 'Scolarité', 'Inscriptions']
 *   Or arrays with links: [['label' => 'Accueil', 'url' => '/'], ['label' => 'Scolarité', 'url' => '?page=scolarite'], ...]
 */
function renderBreadcrumb(array $items): string {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb" aria-label="Fil d\'Ariane">';
    
    $lastIndex = count($items) - 1;
    
    foreach ($items as $index => $item) {
        $isLast = ($index === $lastIndex);
        
        // Handle both string and array formats
        if (is_string($item)) {
            $label = $item;
            $url = null;
        } else {
            $label = $item['label'] ?? '';
            $url = $item['url'] ?? null;
        }
        
        // Separator (except for first item)
        if ($index > 0) {
            $html .= '<span class="breadcrumb-separator"><i class="fas fa-chevron-right text-xs"></i></span>';
        }
        
        // Item
        if ($isLast || !$url) {
            $html .= sprintf(
                '<span class="breadcrumb-item%s">%s</span>',
                $isLast ? ' active' : '',
                htmlspecialchars($label)
            );
        } else {
            $html .= sprintf(
                '<a href="%s" class="breadcrumb-item">%s</a>',
                htmlspecialchars($url),
                htmlspecialchars($label)
            );
        }
    }
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Auto-generate breadcrumb from current URL
 */
function renderAutoBreadcrumb(): string {
    $items = [['label' => 'Accueil', 'url' => '?page=dashboard']];
    
    // Get current page
    $currentPage = $_GET['page'] ?? '';
    $currentAction = $_GET['action'] ?? '';
    
    if (empty($currentPage)) {
        return renderBreadcrumb($items);
    }
    
    // Page mapping
    $pageLabels = [
        'dashboard' => 'Tableau de bord',
        'parametres_generaux' => 'Paramètres Généraux',
        'parametres_specifiques' => 'Paramètres Spécifiques',
        'gestion_utilisateurs' => 'Gestion Utilisateurs',
        'gestion_scolarite' => 'Scolarité',
        'gestion_etudiants' => 'Gestion Étudiants',
        'gestion_rapports' => 'Gestion Rapports',
        'gestion_notes_evaluations' => 'Notes et Évaluations',
        'gestion_reclamations' => 'Réclamations',
        'gestion_rh' => 'Ressources Humaines',
        'candidature_soutenance' => 'Candidature Soutenance',
        'gestion_candidatures_soutenance' => 'Gestion Candidatures',
        'evaluation_soutenance' => 'Évaluation Soutenance',
        'admin_historique' => 'Historique',
        'piste_audit' => 'Piste d\'Audit',
        'sauvegarde_restauration' => 'Sauvegarde/Restauration'
    ];
    
    $actionLabels = [
        'annees_academiques' => 'Années Académiques',
        'grades' => 'Grades',
        'fonctions' => 'Fonctions',
        'specialites' => 'Spécialités',
        'niveaux_etude' => 'Niveaux d\'Étude',
        'ue' => 'Unités d\'Enseignement',
        'ecue' => 'ECUE',
        'entreprises' => 'Entreprises',
        'salles' => 'Salles',
        'gestion_menus' => 'Gestion Menus',
        'gestion_attribution' => 'Habilitations',
        'soumettre_reclamation' => 'Soumettre',
        'suivi_historique_reclamation' => 'Suivi et Historique'
    ];
    
    // Add current page
    $pageLabel = $pageLabels[$currentPage] ?? ucfirst(str_replace('_', ' ', $currentPage));
    
    if ($currentAction) {
        // Page is a parent, add link
        $items[] = [
            'label' => $pageLabel,
            'url' => '?page=' . urlencode($currentPage)
        ];
        
        // Add action as current
        $actionLabel = $actionLabels[$currentAction] ?? ucfirst(str_replace('_', ' ', $currentAction));
        $items[] = ['label' => $actionLabel];
    } else {
        // Page is current (no link)
        $items[] = ['label' => $pageLabel];
    }
    
    return renderBreadcrumb($items);
}

/**
 * Simple text-only breadcrumb
 */
function renderBreadcrumbText(array $items): string {
    return '<span class="text-sm text-muted">' . implode(' / ', array_map('htmlspecialchars', $items)) . '</span>';
}
