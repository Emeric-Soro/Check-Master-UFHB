<?php
$cards = isset($cardPGeneraux) && is_array($cardPGeneraux) ? $cardPGeneraux : [];

$iconByTitle = [
    'Actions Système' => 'fa-bolt',
    'Années Académiques' => 'fa-calendar-alt',
    'Critères Évaluation' => 'fa-list-check',
    'Entreprises' => 'fa-building',
    'Fonctions Personnel' => 'fa-briefcase',
    'Fonctions Utilisateurs' => 'fa-users-cog',
    'Gestion Attributions' => 'fa-key',
    'Grades' => 'fa-medal',
    'Messages Système' => 'fa-envelope',
    'Niveaux d\'Accès' => 'fa-lock',
    'Niveaux d\'Approbation' => 'fa-diagram-project',
    'Niveaux d\'Étude' => 'fa-graduation-cap',
    'Salles' => 'fa-door-open',
    'Semestres' => 'fa-calendar-check',
    'Spécialités' => 'fa-user-graduate',
    'Statuts du Jury' => 'fa-gavel',
    'Traitements Menu' => 'fa-clipboard-list',
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
