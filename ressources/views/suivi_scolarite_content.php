<?php
/**
 * Hub Suivi & Scolarité — Navigation par onglets
 * Regroupe : Finances année, Histo. inscriptions, Sans rapport, Non inscrits,
 *            Sans compte, Échéancier, Fiche insc., Fiche 10 onglets, Timeline
 */

$hubTabs = [
    ['id' => 'fiche_financiere_annee',        'label' => 'Finances année'],
    ['id' => 'historique_inscriptions',       'label' => 'Histo. inscriptions'],
    ['id' => 'etudiants_sans_rapport',        'label' => 'Sans rapport'],
    ['id' => 'etudiants_non_inscrits',        'label' => 'Non inscrits'],
    ['id' => 'etudiants_sans_compte',         'label' => 'Sans compte'],
    ['id' => 'echeancier_etudiant',           'label' => 'Échéancier'],
    ['id' => 'visualisation_fiche_inscription','label' => 'Fiche insc.'],
    ['id' => 'fiche_etudiant_complete',       'label' => 'Fiche 10 onglets'],
    ['id' => 'timeline_parcours_etudiant',    'label' => 'Timeline'],
];

$requestedTab = (string) ($_GET['tab'] ?? 'fiche_financiere_annee');
$allowedIds = array_column($hubTabs, 'id');
$currentTab = in_array($requestedTab, $allowedIds, true) ? $requestedTab : 'fiche_financiere_annee';
$baseUrl = '?page=suivi_scolarite';
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
        case 'fiche_financiere_annee':
            include $partialsPath . 'fiche_financiere_content.php';
            break;
        case 'historique_inscriptions':
            include $partialsPath . 'historique_inscriptions_content.php';
            break;
        case 'etudiants_sans_rapport':
            include $partialsPath . 'etudiants_sans_rapport_content.php';
            break;
        case 'etudiants_non_inscrits':
            include $partialsPath . 'etudiants_non_inscrits_content.php';
            break;
        case 'etudiants_sans_compte':
            include $partialsPath . 'etudiants_sans_compte_content.php';
            break;
        case 'echeancier_etudiant':
            include $partialsPath . 'echeancier_etudiant_content.php';
            break;
        case 'visualisation_fiche_inscription':
            include $partialsPath . 'visualisation_fiche_inscription_content.php';
            break;
        case 'fiche_etudiant_complete':
            include $partialsPath . 'v2/archives/fiche_etudiant_complete.php';
            break;
        case 'timeline_parcours_etudiant':
            include $partialsPath . 'v2/archives/timeline_interactive.php';
            break;
    }
    ?>
</section>
