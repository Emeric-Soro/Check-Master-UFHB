<?php
/**
 * CheckMaster Premium - Search Bar Component
 * 
 * Barre de recherche avec icône et options avancées.
 */

/**
 * Simple search bar
 * 
 * @param string $placeholder - Texte placeholder
 * @param string $name - Nom du champ (défaut: 'search')
 * @param string $value - Valeur actuelle
 * @param array $config - Configuration additionnelle
 */
function renderSearchBar(
    string $placeholder = 'Rechercher...',
    string $name = 'search',
    string $value = '',
    array $config = []
): string {
    $defaults = [
        'id' => 'search-input',
        'class' => '',
        'onSubmit' => '',
        'showButton' => false,
        'buttonText' => 'Rechercher',
        'attr' => '',
        'icon' => 'fa-search'
    ];
    
    $opts = array_merge($defaults, $config);
    
    $onSubmitAttr = $opts['onSubmit'] ? ' onsubmit="' . htmlspecialchars($opts['onSubmit']) . '"' : '';
    
    $buttonHtml = '';
    if ($opts['showButton']) {
        $buttonHtml = sprintf(
            '<button type="submit" class="btn btn-primary">
                <i class="fas %s"></i> %s
            </button>',
            htmlspecialchars($opts['icon']),
            htmlspecialchars($opts['buttonText'])
        );
    }
    
    return sprintf(
        '<form class="search-form%s"%s>
            <div class="search-wrapper">
                <i class="fas %s search-icon"></i>
                <input 
                    type="text" 
                    id="%s" 
                    name="%s" 
                    value="%s" 
                    placeholder="%s" 
                    class="search-input"
                    %s
                >
                %s
            </div>
        </form>',
        $opts['class'] ? ' ' . htmlspecialchars($opts['class']) : '',
        $onSubmitAttr,
        htmlspecialchars($opts['icon']),
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        htmlspecialchars($value),
        htmlspecialchars($placeholder),
        $opts['attr'],
        $buttonHtml
    );
}

/**
 * Search bar with filters
 * 
 * @param string $placeholder - Texte placeholder
 * @param array $filters - Filtres disponibles ['name' => ['label' => '', 'options' => []]]
 * @param array $config - Configuration additionnelle
 */
function renderSearchBarWithFilters(
    string $placeholder = 'Rechercher...',
    array $filters = [],
    array $config = []
): string {
    $defaults = [
        'name' => 'search',
        'value' => '',
        'id' => 'search-input',
        'class' => '',
        'onSubmit' => ''
    ];
    
    $opts = array_merge($defaults, $config);
    
    $onSubmitAttr = $opts['onSubmit'] ? ' onsubmit="' . htmlspecialchars($opts['onSubmit']) . '"' : '';
    
    // Construction des filtres
    $filtersHtml = '';
    if (!empty($filters)) {
        $filtersHtml = '<div class="search-filters flex gap-sm mt-sm">';
        
        foreach ($filters as $filterName => $filterData) {
            require_once __DIR__ . '/select.php';
            $filtersHtml .= renderSelect(
                $filterName,
                $filterData['label'] ?? '',
                $filterData['options'] ?? [],
                $filterData['selected'] ?? '',
                [
                    'placeholder' => $filterData['placeholder'] ?? 'Tous',
                    'class' => 'filter-select'
                ]
            );
        }
        
        $filtersHtml .= '</div>';
    }
    
    return sprintf(
        '<form class="search-form-advanced%s"%s>
            <div class="flex gap-sm">
                <div class="search-wrapper flex-grow">
                    <i class="fas fa-search search-icon"></i>
                    <input 
                        type="text" 
                        id="%s" 
                        name="%s" 
                        value="%s" 
                        placeholder="%s" 
                        class="search-input"
                    >
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </div>
            %s
        </form>',
        $opts['class'] ? ' ' . htmlspecialchars($opts['class']) : '',
        $onSubmitAttr,
        htmlspecialchars($opts['id']),
        htmlspecialchars($opts['name']),
        htmlspecialchars($opts['value']),
        htmlspecialchars($placeholder),
        $filtersHtml
    );
}

/**
 * Quick search (inline, sans formulaire)
 * 
 * @param string $placeholder - Texte placeholder
 * @param string $onInput - Fonction JS à appeler sur input
 * @param array $config - Configuration additionnelle
 */
function renderQuickSearch(
    string $placeholder = 'Rechercher...',
    string $onInput = '',
    array $config = []
): string {
    $defaults = [
        'id' => 'quick-search-' . uniqid(),
        'name' => 'q',
        'class' => '',
        'icon' => 'fa-search'
    ];
    
    $opts = array_merge($defaults, $config);
    
    $onInputAttr = $onInput ? ' oninput="' . htmlspecialchars($onInput) . '"' : '';
    
    return sprintf(
        '<div class="search-wrapper%s">
            <i class="fas %s search-icon"></i>
            <input 
                type="text" 
                id="%s" 
                name="%s" 
                placeholder="%s" 
                class="search-input"
                %s
            >
        </div>',
        $opts['class'] ? ' ' . htmlspecialchars($opts['class']) : '',
        htmlspecialchars($opts['icon']),
        htmlspecialchars($opts['id']),
        htmlspecialchars($opts['name']),
        htmlspecialchars($placeholder),
        $onInputAttr
    );
}
