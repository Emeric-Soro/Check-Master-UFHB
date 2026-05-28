<?php
if (!function_exists('canView')) {
    require_once __DIR__ . '/../../app/utils/permissions_helper.php';
}

$cards = isset($cardPSpecifiques) && is_array($cardPSpecifiques) ? $cardPSpecifiques : [];

$filteredCards = array_values(array_filter($cards, static function ($card): bool {
    $title = strtolower((string) ($card['title'] ?? ''));
    $link = (string) ($card['link'] ?? '');
    $action = '';

    $query = parse_url($link, PHP_URL_QUERY);
    if (is_string($query) && $query !== '') {
        $params = [];
        parse_str($query, $params);
        $action = strtolower((string) ($params['action'] ?? ''));
    }

    if ($action === 'ue' || $action === 'ecue') {
        return false;
    }

    return strpos($title, '(ue)') === false && strpos($title, 'ecue') === false;
}));

$iconByTitle = [
    'Critères' => 'fa-list-check',
    'Barème Critère' => 'fa-scale-balanced',
    'Critères + Barème' => 'fa-list-check',
    'Critères et Barème' => 'fa-list-check',
    'Critères Barème' => 'fa-list-check',
    'Salles' => 'fa-door-open',
    'Programmation sessions' => 'fa-calendar-days',
    'Entreprises' => 'fa-building',
    'Spécialités' => 'fa-user-graduate',
    'Maître de stage' => 'fa-user-tie',
    'Type Enseignant' => 'fa-chalkboard-user',
    'Gestion des Menus' => 'fa-sitemap',
    'Habilitations' => 'fa-key',
    'Traitements' => 'fa-clipboard-list',
    'Messages Système' => 'fa-envelope',
    'Structure BD' => 'fa-database',
];

$resolveIcon = static function (array $card) use ($iconByTitle): string {
    $title = (string) ($card['title'] ?? '');
    $rawIcon = trim((string) ($card['icon'] ?? ''));

    if ($rawIcon !== '' && stripos($rawIcon, 'fa-') !== false) {
        $parts = preg_split('/\s+/', $rawIcon) ?: [];
        foreach ($parts as $part) {
            if (strpos($part, 'fa-') === 0) {
                return $part;
            }
        }
    }

    if (isset($iconByTitle[$title])) {
        return $iconByTitle[$title];
    }

    return 'fa-cog';
};

ob_start();
foreach ($filteredCards as $card) {
    // Check if user can view this section
    if (!canView()) {
        continue;  // Skip this card if user cannot view
    }

    $title = (string) ($card['title'] ?? 'Paramètre');
    $desc = (string) ($card['description'] ?? '');
    $href = (string) ($card['link'] ?? '#');
    $count = isset($card['count']) ? (string) $card['count'] : '';

    cm_component('hub/hub-tile', [
        'title' => $title,
        'desc' => $desc,
        'href' => $href,
        'icon' => $resolveIcon((array) $card),
        'count' => $count,
    ]);
}
$tilesHtml = (string) ob_get_clean();
?>
<section class="cm-prd6-hub-screen">
    <div class="cm-prd6-hub-grid-wrap">
        <?php cm_component('hub/hub-grid', ['cols' => 4, 'content' => $tilesHtml]); ?>
    </div>
</section>