<?php
/**
 * CheckMaster Premium - DataTable Component
 * 
 * Composant tableau de données avec pagination, tri et actions CRUD.
 * Remplace tous les foreach répétitifs par une logique centralisée.
 */

/**
 * Render a complete data table
 * 
 * @param array $headers - Configuration des colonnes ['key' => 'Label', ...]
 * @param array $data - Données à afficher
 * @param array $options - Options de configuration
 */
function renderDataTable(
    array $headers, 
    array $data, 
    array $options = []
): string {
    // Options par défaut
    $defaults = [
        'id' => 'data-table-' . uniqid(),
        'actions' => [],           // ['view', 'edit', 'delete'] ou custom
        'actionCallback' => null,  // Fonction pour générer les URLs des actions
        'idField' => 'id',         // Champ ID pour les actions
        'emptyMessage' => 'Aucune donnée disponible',
        'emptyIcon' => 'fa-inbox',
        'selectable' => false,     // Cases à cocher
        'searchable' => false,     // Barre de recherche
        'sortable' => false,       // Colonnes triables
        'class' => '',
        'rowClass' => '',
        'permissions' => [         // Permissions CRUD
            'view' => true,
            'edit' => true,
            'delete' => true
        ]
    ];
    
    $opts = array_merge($defaults, $options);
    
    // Si pas de données, afficher empty state
    if (empty($data)) {
        return renderEmptyState($opts['emptyMessage'], $opts['emptyIcon']);
    }
    
    $html = '';
    
    // Barre de recherche
    if ($opts['searchable']) {
        $html .= sprintf(
            '<div class="mb-md">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Rechercher..." data-table-search="%s">
                </div>
            </div>',
            $opts['id']
        );
    }
    
    // Actions de masse
    if ($opts['selectable']) {
        $html .= '<div class="bulk-actions mb-md" style="display: none;">
            <span class="text-sm text-muted"><span class="selected-count">0</span> élément(s) sélectionné(s)</span>
            <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>';
    }
    
    // Table wrapper
    $html .= '<div class="table-wrapper ' . htmlspecialchars($opts['class']) . '">';
    $html .= '<table class="data-table" id="' . htmlspecialchars($opts['id']) . '">';
    
    // Thead
    $html .= '<thead><tr>';
    
    // Checkbox header
    if ($opts['selectable']) {
        $html .= '<th style="width: 40px;">
            <input type="checkbox" class="form-checkbox" onchange="CM.DataTable.toggleSelectAll(this)">
        </th>';
    }
    
    // Column headers
    foreach ($headers as $key => $label) {
        $sortable = $opts['sortable'] ? ' style="cursor: pointer;" onclick="sortTable(this, \'' . $key . '\')"' : '';
        $html .= '<th' . $sortable . '>' . htmlspecialchars($label);
        if ($opts['sortable']) {
            $html .= ' <i class="fas fa-sort text-muted"></i>';
        }
        $html .= '</th>';
    }
    
    // Actions header
    if (!empty($opts['actions'])) {
        $html .= '<th class="text-right">Actions</th>';
    }
    
    $html .= '</tr></thead>';
    
    // Tbody
    $html .= '<tbody>';
    
    foreach ($data as $row) {
        $rowId = $row[$opts['idField']] ?? '';
        $rowClassVal = is_callable($opts['rowClass']) 
            ? call_user_func($opts['rowClass'], $row) 
            : $opts['rowClass'];
        
        $html .= '<tr class="' . htmlspecialchars($rowClassVal) . '">';
        
        // Checkbox cell
        if ($opts['selectable']) {
            $html .= '<td>
                <input type="checkbox" class="form-checkbox row-checkbox" value="' . htmlspecialchars($rowId) . '">
            </td>';
        }
        
        // Data cells
        foreach ($headers as $key => $label) {
            $value = $row[$key] ?? '';
            
            // Support for nested keys (e.g., 'user.name')
            if (strpos($key, '.') !== false) {
                $keys = explode('.', $key);
                $value = $row;
                foreach ($keys as $k) {
                    $value = $value[$k] ?? '';
                }
            }
            
            // Check for special rendering (badge, date, etc.)
            if (isset($row['_render'][$key])) {
                $value = $row['_render'][$key];
            } else {
                $value = htmlspecialchars((string) $value);
            }
            
            $html .= '<td>' . $value . '</td>';
        }
        
        // Actions cell
        if (!empty($opts['actions'])) {
            $html .= '<td><div class="table-actions justify-end">';
            $html .= renderTableActions($rowId, $opts['actions'], $opts['actionCallback'], $opts['permissions']);
            $html .= '</div></td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    
    return $html;
}

/**
 * Render table action buttons
 */
function renderTableActions($id, array $actions, $callback = null, array $permissions = []): string {
    $html = '';
    
    $defaultActions = [
        'view' => [
            'icon' => 'fa-eye',
            'class' => 'view',
            'title' => 'Voir',
            'permission' => 'view'
        ],
        'edit' => [
            'icon' => 'fa-edit',
            'class' => 'edit',
            'title' => 'Modifier',
            'permission' => 'edit'
        ],
        'delete' => [
            'icon' => 'fa-trash',
            'class' => 'delete',
            'title' => 'Supprimer',
            'permission' => 'delete'
        ]
    ];
    
    foreach ($actions as $action) {
        if (is_string($action) && isset($defaultActions[$action])) {
            $config = $defaultActions[$action];
            
            // Check permission
            $permKey = $config['permission'];
            if (isset($permissions[$permKey]) && !$permissions[$permKey]) {
                continue;
            }
            
            $url = is_callable($callback) 
                ? call_user_func($callback, $action, $id) 
                : '#';
            
            $onclick = '';
            if ($action === 'delete') {
                $onclick = sprintf(
                    "onclick=\"CM.Modal.confirm({title:'Confirmation', message:'Êtes-vous sûr de vouloir supprimer cet élément ?', type:'danger'}).then(ok => { if(ok) window.location.href='%s'; })\"",
                    htmlspecialchars($url)
                );
                $url = 'javascript:void(0)';
            }
            
            $html .= sprintf(
                '<a href="%s" class="table-action %s" title="%s" %s>
                    <i class="fas %s"></i>
                </a>',
                htmlspecialchars($url),
                $config['class'],
                $config['title'],
                $onclick,
                $config['icon']
            );
        } elseif (is_array($action)) {
            // Custom action
            $html .= sprintf(
                '<a href="%s" class="table-action" title="%s" %s>
                    <i class="fas %s"></i>
                </a>',
                htmlspecialchars($action['url'] ?? '#'),
                htmlspecialchars($action['title'] ?? ''),
                $action['onclick'] ?? '',
                htmlspecialchars($action['icon'] ?? 'fa-cog')
            );
        }
    }
    
    return $html;
}

/**
 * Simple table without actions
 */
function renderSimpleTable(array $headers, array $data, string $class = ''): string {
    if (empty($data)) {
        return renderEmptyState('Aucune donnée disponible');
    }
    
    $html = '<div class="table-wrapper ' . htmlspecialchars($class) . '">';
    $html .= '<table class="data-table">';
    
    // Thead
    $html .= '<thead><tr>';
    foreach ($headers as $label) {
        $html .= '<th>' . htmlspecialchars($label) . '</th>';
    }
    $html .= '</tr></thead>';
    
    // Tbody
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    
    return $html;
}

/**
 * Empty State component
 */
function renderEmptyState(string $message = 'Aucune donnée', string $icon = 'fa-inbox', string $action = ''): string {
    return sprintf(
        '<div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas %s"></i>
            </div>
            <h4 class="empty-state-title">%s</h4>
            %s
        </div>',
        htmlspecialchars($icon),
        htmlspecialchars($message),
        $action
    );
}
