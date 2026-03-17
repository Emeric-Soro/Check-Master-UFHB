<?php
if (!function_exists('canView')) {
    require_once __DIR__ . '/../../app/utils/permissions_helper.php';
}

$cards = isset($cardPGeneraux) && is_array($cardPGeneraux) ? $cardPGeneraux : [];

// Remove "Années Académiques" — it has its own dedicated page
$cards = array_values(array_filter($cards, static function ($card): bool {
    $title = strtolower(trim((string) ($card['title'] ?? '')));
    return strpos($title, 'années académiques') === false
        && strpos($title, 'annees academiques') === false
        && strpos($title, 'année académique') === false;
}));

$iconByTitle = [
    'Années Académiques' => 'fa-calendar-alt',
    'App Settings' => 'fa-sliders',
    'Niveaux d\'Étude' => 'fa-layer-group',
    'Semestres' => 'fa-calendar-check',
    'Genre' => 'fa-venus-mars',
    'Décisions Jury' => 'fa-gavel',
    'Établissement Origine' => 'fa-school',
    'Session' => 'fa-clock',
    'Mode Paiement' => 'fa-credit-card',
    'Statut Réclamation' => 'fa-triangle-exclamation',
    'Domaine' => 'fa-diagram-project',
    'Mentions' => 'fa-award',
    'Filières' => 'fa-graduation-cap',
    'Fonctions Utilisateurs' => 'fa-users-cog',
    'Fonction' => 'fa-briefcase',
    'Grades' => 'fa-medal',
    'Niveaux d\'Accès' => 'fa-lock',
    'Niveaux d\'Approbation' => 'fa-sitemap',
    'Qualité Jury' => 'fa-user-shield',
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

    return 'fa-sliders-h';
};

ob_start();
foreach ($cards as $card) {
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
