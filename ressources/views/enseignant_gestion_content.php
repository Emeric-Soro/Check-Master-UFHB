<?php
/**
 * Hub Gestion des Enseignants — Navigation par onglets
 * Regroupe : Répertoire Document, Fiche enseignante, Planning jurys,
 *            Stats encadrement, Portfolio, Annuaire
 */

$hubTabs = [
    ['id' => 'repertoire_enseignant',       'label' => 'Répertoire Document'],
    ['id' => 'fiche_enseignante',           'label' => 'Fiche enseignante'],
    ['id' => 'planning_jurys_enseignant',   'label' => 'Planning jurys'],
    ['id' => 'stats_encadrement_enseignant','label' => 'Stats encadrement'],
    ['id' => 'portfolio_enseignant',        'label' => 'Portfolio'],
    ['id' => 'annuaire_enseignants',        'label' => 'Annuaire'],
];

$requestedTab = (string) ($_GET['tab'] ?? 'repertoire_enseignant');
$allowedIds = array_column($hubTabs, 'id');
$currentTab = in_array($requestedTab, $allowedIds, true) ? $requestedTab : 'repertoire_enseignant';
$baseUrl = '?page=enseignant_gestion';
$partialsPath = __DIR__ . DIRECTORY_SEPARATOR;
?>
<section class="cm-prd3-screen">
    <!-- Barre d'onglets (navigation par URL) -->
    <nav class="cm-tab-nav" role="tablist" style="flex-wrap:wrap;">
        <?php foreach ($hubTabs as $tab): ?>
            <?php $isActive = $tab['id'] === $currentTab; ?>
            <a href="<?= $baseUrl ?>&tab=<?= urlencode($tab['id']) ?>"
               class="cm-tab-nav__item <?= $isActive ? 'is-active' : '' ?>"
               role="tab"
               aria-selected="<?= $isActive ? 'true' : 'false' ?>"
               style="text-decoration:none;">
                <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Contenu de l'onglet actif -->
    <?php
    switch ($currentTab) {
        case 'repertoire_enseignant':
            include $partialsPath . 'repertoire_enseignant_content.php';
            break;
        case 'fiche_enseignante':
            include $partialsPath . 'fiche_enseignante_content.php';
            break;
        case 'planning_jurys_enseignant':
            include $partialsPath . 'planning_jurys_enseignant_content.php';
            break;
        case 'stats_encadrement_enseignant':
            include $partialsPath . 'stats_encadrement_enseignant_content.php';
            break;
        case 'portfolio_enseignant':
            include $partialsPath . 'portfolio_enseignant_content.php';
            break;
        case 'annuaire_enseignants':
            include $partialsPath . 'annuaire_enseignants_content.php';
            break;
    }
    ?>
</section>
