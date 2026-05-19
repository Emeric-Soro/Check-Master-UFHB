<?php
/**
 * Hub Commissions & Archives — Navigation par onglets
 * Regroupe : Archives documents, Archives étudiants, Archives comptes rendus,
 *            Fiche commission, Workflow
 */

$hubTabs = [
    ['id' => 'archives_documents',      'label' => 'Archives documents'],
    ['id' => 'archives_etudiants',      'label' => 'Archives étudiants'],
    ['id' => 'archive_comptes_rendus',  'label' => 'Archives comptes rendus'],
    ['id' => 'fiche_commission',        'label' => 'Fiche commission'],
    ['id' => 'workflow_validation',     'label' => 'Workflow'],
];

$requestedTab = (string) ($_GET['tab'] ?? 'archives_documents');
$allowedIds = array_column($hubTabs, 'id');
$currentTab = in_array($requestedTab, $allowedIds, true) ? $requestedTab : 'archives_documents';
$baseUrl = '?page=commissions_archives';
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
        case 'archives_documents':
            include $partialsPath . 'v2/archives/archives_documents.php';
            break;
        case 'archives_etudiants':
            include $partialsPath . 'v2/archives/archives_etudiants.php';
            break;
        case 'archive_comptes_rendus':
            // La vue archive_comptes_rendus utilise le chemin redaction_compte_rendu
            include $partialsPath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
            break;
        case 'fiche_commission':
            include $partialsPath . 'fiche_commission_content.php';
            break;
        case 'workflow_validation':
            include $partialsPath . 'workflow_validation_content.php';
            break;
    }
    ?>
</section>
