<?php
/**
 * Hub Outils & Direction — Navigation par onglets
 * Regroupe : Mes documents, Histo. modifs, Export ZIP, Sécurité,
 *            Comparaison docs, Fiche agent, KPI Direction
 */

$hubTabs = [
    ['id' => 'documents',                      'label' => 'Mes documents'],
    ['id' => 'historique_modifications',       'label' => 'Histo. modifs'],
    ['id' => 'export_masse_documents',         'label' => 'Export ZIP'],
    ['id' => 'dashboard_securite',             'label' => 'Sécurité'],
    ['id' => 'comparaison_versions_document',  'label' => 'Comparaison docs'],
    ['id' => 'fiche_personnel_admin',          'label' => 'Fiche agent'],
    ['id' => 'dashboard_direction',            'label' => 'KPI Direction'],
];

$requestedTab = (string) ($_GET['tab'] ?? 'documents');
$allowedIds = array_column($hubTabs, 'id');
$currentTab = in_array($requestedTab, $allowedIds, true) ? $requestedTab : 'documents';
$baseUrl = '?page=outils_direction';
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
        case 'documents':
            include $partialsPath . 'documents_content.php';
            break;
        case 'historique_modifications':
            include $partialsPath . 'historique_modifications_content.php';
            break;
        case 'export_masse_documents':
            include $partialsPath . 'export_masse_documents_content.php';
            break;
        case 'dashboard_securite':
            include $partialsPath . 'dashboard_securite_content.php';
            break;
        case 'comparaison_versions_document':
            include $partialsPath . 'comparaison_versions_document_content.php';
            break;
        case 'fiche_personnel_admin':
            include $partialsPath . 'fiche_pers_admin_content.php';
            break;
        case 'dashboard_direction':
            include $partialsPath . 'dashboard_direction_content.php';
            break;
    }
    ?>
</section>
